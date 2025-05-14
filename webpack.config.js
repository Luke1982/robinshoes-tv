const path = require("path");
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./src/index.js",
    frontend: "./src/frontend.js",
  },
  output: {
    path: path.resolve(process.cwd(), "build"),
    filename: "[name].js",
  },
  resolve: {
    fallback: {
      path: require.resolve("path-browserify"),
    },
  },
};
