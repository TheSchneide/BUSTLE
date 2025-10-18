//css for loading
window.addEventListener("load", () => {
    const loader = document.getElementById("loading-screen");
    setTimeout(() => {
      loader.classList.add("hidden");
    }, 500);
  });

document.querySelectorAll(".navBar a, .logo a").forEach(link => {
    link.addEventListener("click", event => {
    event.preventDefault();
    const loader = document.getElementById("loading-screen");
    loader.classList.remove("hidden"); 

    setTimeout(() => {
    window.location.href = link.href;
    }, 500);
});
});

window.addEventListener("load", () => {
  const loader = document.getElementById("loading-screen");
  setTimeout(() => {
    loader.classList.add("hidden");

    // Once loader fades, start text animations
    animateText();
  }, 500);
});
//animation
function animateText() {
  const elements = document.querySelectorAll(".hidden-text");
  elements.forEach((el, i) => {
    const anim = el.dataset.anim || "fade-up";
    setTimeout(() => {
      el.classList.add(anim);
      el.classList.remove("hidden-text");
    }, i * 200); // delay between each element
  });
}
//loading end-----
