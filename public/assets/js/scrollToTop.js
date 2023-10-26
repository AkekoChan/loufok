let btn = document.querySelector(".scroll__btn");

window.addEventListener("scroll", () => {
  scrollFunction();
});

btn.addEventListener("click", () => {
  topFunction();
});

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    btn.style.display = "flex";
  } else {
    btn.style.display = "none";
  }
}

function topFunction() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}
