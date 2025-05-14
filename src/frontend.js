import Swiper from "swiper";
import { Autoplay, Pagination } from "swiper/modules";
import "./tv.css";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/autoplay";

// register the modules
Swiper.use([Autoplay, Pagination]);

window.addEventListener("DOMContentLoaded", () => {
  // initialize on your container selector
  new Swiper(".tv-swiper-container", {
    loop: true,
    slidesPerView: 1,
    autoplay: {
      delay: 10000,
      disableOnInteraction: false,
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });

  // ensure full-screen sizing
  const container = document.querySelector(".tv-swiper-container");
  if (container) {
    container.style.width = "100vw";
    container.style.height = "100vh";
    container.querySelectorAll(".swiper-slide").forEach((slide) => {
      slide.style.width = "100%";
      slide.style.height = "100%";
    });
  }
});
