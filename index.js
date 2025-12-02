
window.addEventListener("load", () => {
    const loader = document.getElementById("loading-screen");
    setTimeout(() => {
        if(loader) loader.classList.add("hidden");

        animateText();
    }, 500);
});

document.querySelectorAll(".navBar a, .logo a").forEach(link => {
    link.addEventListener("click", event => {

        if(link.getAttribute('href') && !link.getAttribute('href').startsWith('#')) {
            event.preventDefault();
            const loader = document.getElementById("loading-screen");
            if(loader) loader.classList.remove("hidden");

            setTimeout(() => {
                window.location.href = link.href;
            }, 500);
        }
    });
});


function animateText() {
    const elements = document.querySelectorAll(".hidden-text");
    elements.forEach((el, i) => {
        const anim = el.dataset.anim || "fade-up";
        setTimeout(() => {
            el.classList.add(anim);
            el.classList.remove("hidden-text");
        }, i * 200); 
    });
}

document.addEventListener('click', (e) => {

    const dropBtn = e.target.closest('.dropbtn');
   
    const dropContent = e.target.closest('.dropdown-content');

    if (dropBtn) {

        const dropdown = dropBtn.closest('.dropdown');
        dropdown.classList.toggle('active');
        e.stopPropagation(); 
    } else if (!dropContent) {
        document.querySelectorAll('.dropdown.active').forEach(d => {
            d.classList.remove('active');
        });
    }
});