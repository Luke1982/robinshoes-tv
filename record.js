const puppeteer = require("puppeteer");
const { spawn } = require("child_process");
const fs = require("fs");
const path = require("path");

(async () => {
  // Path to input flag file
  const flagPath = path.resolve(__dirname, "tv-flag.trigger");
  // Path to output video
  const outputPath = path.resolve(__dirname, "output.mp4");
  // Path to new output flag
  const outputFlagPath = path.resolve(__dirname, "tv-recorded.trigger");
  const recordFlag = path.resolve(__dirname, "tv-recording.flag");

  // Only proceed if the input flag file exists
  if (!fs.existsSync(flagPath)) {
    console.log("Flag file not found, exiting.");
    process.exit(0);
  }

  // Remove any existing output video
  if (fs.existsSync(outputPath)) {
    try {
      fs.unlinkSync(outputPath);
      console.log(`→ Removed old output file: ${outputPath}`);
    } catch (err) {
      console.error(`Error removing old output file: ${err}`);
    }
  }

  // Stop if a recording is already in progress
  if (fs.existsSync(recordFlag)) {
    console.log("Recording already in progress, exiting.");
    process.exit(0);
  }

  // Create a flag file to indicate recording is in progress
  fs.writeFileSync(recordFlag, "", { flag: "wx" }, (err) => {
    if (err) {
      console.error(`Error creating recording flag file: ${err}`);
      process.exit(1);
    }
    console.log(`→ Recording flag file created: ${recordFlag}`);
  });

  // 1) Launch Chrome onto DISPLAY (provided by xvfb-run)
  const browser = await puppeteer.launch({
    headless: false,
    args: [
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--start-fullscreen",
      "--window-size=1920,1080",
      "--hide-scrollbars",
      "--disable-infobars",
      "--disable-features=TranslateUI,ApplicationCache,IsolateOrigins,site-per-process",
      "--disable-application-cache",
      "--disk-cache-dir=/dev/null",
      "--disable-offline-load-stale-cache",
      "--disable-background-networking",
      "--disable-sync",
      "--disable-translate",
      "--disable-browser-side-navigation",
      "--force-device-scale-factor=1",
    ],
    defaultViewport: {
      width: 1920,
      height: 1080,
      deviceScaleFactor: 1,
    },
  });

  const page = await browser.newPage();

  // 1.a) Disable cache and force fresh reload
  await page.setCacheEnabled(false);
  await page.setExtraHTTPHeaders({
    "Cache-Control": "no-cache",
    Pragma: "no-cache",
  });

  // 1.b) Use CDP session to clear cache & cookies
  const client = await page.target().createCDPSession();
  await client.send("Network.clearBrowserCache");
  await client.send("Network.clearBrowserCookies");

  // Intercept requests to prevent caching
  await page.setRequestInterception(true);
  page.on("request", (req) => {
    const headers = Object.assign({}, req.headers(), {
      pragma: "no-cache",
      "cache-control": "no-cache",
    });
    req.continue({ headers });
  });

  // 1.c) Build a URL with a timestamp to force fresh load
  const baseUrl = "https://robinshoes.nl/tv";
  const url = `${baseUrl}?_=${Date.now()}`;
  console.log(`→ Navigating to fresh URL: ${url}`);

  // Navigate to your TV slideshow URL, always fresh
  await page.goto(url, { waitUntil: "networkidle2" });

  // Hide scrollbars, overflow & cursor
  await page.addStyleTag({
    content: `
    html, body { overflow: hidden !important; }
    *::-webkit-scrollbar { display: none !important; }
    * { cursor: none !important; }
  `,
  });

  // 2) Wait for & read the hidden duration input
  await page.waitForSelector('input[type="hidden"][name="duration"]', {
    timeout: 10000,
  });
  const duration = await page.$eval(
    'input[type="hidden"][name="duration"]',
    (el) => parseInt(el.value, 10)
  );
  console.log(`→ Recording for ${duration}s`);

  // 3) Spawn ffmpeg in x11grab mode
  const ffmpegArgs = [
    "-y",
    "-video_size",
    "1920x1080",
    "-framerate",
    "30",
    "-f",
    "x11grab",
    "-i",
    process.env.DISPLAY,
    "-c:v",
    "libx264",
    "-pix_fmt",
    "yuv420p",
    "-t",
    String(duration),
    outputPath,
  ];
  console.log("→ ffmpeg args:", ffmpegArgs.join(" "));
  const ffmpeg = spawn("ffmpeg", ffmpegArgs);

  ffmpeg.stderr.on("data", (data) => {
    // Uncomment to debug ffmpeg:
    // console.error(`ffmpeg: ${data}`);
  });

  await new Promise((resolve) =>
    ffmpeg.on("exit", (code) => {
      console.log(`→ ffmpeg exited with code ${code}`);
      resolve();
    })
  );

  // 4) Cleanup
  await browser.close();
  console.log(`✅ Done. Video saved to: ${outputPath}`);

  // 5) Remove the input flag file
  try {
    fs.unlinkSync(flagPath);
    console.log(`→ Flag file removed: ${flagPath}`);
  } catch (err) {
    console.error(`Error removing flag file: ${err}`);
  }

  // 6) Create a new flag file to signal video is ready
  try {
    fs.writeFileSync(outputFlagPath, "");
    console.log(`→ Output flag file created: ${outputFlagPath}`);
  } catch (err) {
    console.error(`Error creating output flag file: ${err}`);
  }

  // 7) Remove the recording flag file
  try {
    fs.unlinkSync(recordFlag);
    console.log(`→ Recording flag file removed: ${recordFlag}`);
  } catch (err) {
    console.error(`Error removing recording flag file: ${err}`);
  }
  console.log("Recording completed successfully.");
  process.exit(0);
})();
