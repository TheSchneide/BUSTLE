// CSS for loading
window.addEventListener("load", () => {
    const loader = document.getElementById("loading-screen");
    setTimeout(() => {
        if(loader) loader.classList.add("hidden");
        // Once loader fades, start text animations
        animateText();
    }, 500);
});

document.querySelectorAll(".navBar a, .logo a").forEach(link => {
    link.addEventListener("click", event => {
        // Prevent default only if it's a link to another page
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

// Animation
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

// --- NEW DROPDOWN LOGIC (CLICK TO TOGGLE, CLOSE OUTSIDE) ---
document.addEventListener('click', (e) => {
    // Check if clicked inside a dropdown button
    const dropBtn = e.target.closest('.dropbtn');
    
    // Check if clicked inside a dropdown content (to prevent closing when clicking links inside)
    const dropContent = e.target.closest('.dropdown-content');

    if (dropBtn) {
        // Toggle this specific dropdown
        const dropdown = dropBtn.closest('.dropdown');
        dropdown.classList.toggle('active');
        e.stopPropagation(); // Stop bubbling to prevent immediate close
    } else if (!dropContent) {
        // If clicked outside button AND outside content, close all
        document.querySelectorAll('.dropdown.active').forEach(d => {
            d.classList.remove('active');
        });
    }
});