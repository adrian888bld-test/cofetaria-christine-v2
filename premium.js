// premium.js — active nav + reveal on scroll + header scrolled
(function () {
    // 1) Active link în meniu (desktop + mobile)
    const path = (location.pathname.split("/").pop() || "index.html").toLowerCase();

    document.querySelectorAll('nav a.nav__link').forEach(a => {
        const href = (a.getAttribute("href") || "").toLowerCase();
        if (!href) return;

        // normalize: index.html pentru home
        const normalizedHref = href === "/" ? "index.html" : href;

        if (normalizedHref === path) {
            a.classList.add("is-active");
        }
    });

    // 2) Reveal animations (secțiuni + carduri)
    const revealTargets = [
        ...document.querySelectorAll("section"),
        ...document.querySelectorAll(".category"),
        ...document.querySelectorAll(".product-card"),
        ...document.querySelectorAll(".blog-card"),
        ...document.querySelectorAll(".card")
    ];

    revealTargets.forEach(el => el.classList.add("reveal"));

    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add("is-visible");
        });
    }, { threshold: 0.12 });

    revealTargets.forEach(el => io.observe(el));

    // 3) Header scrolled state (subtil)
    const header = document.querySelector(".header");
    if (header) {
        const onScroll = () => {
            header.classList.toggle("header--scrolled", window.scrollY > 10);
        };
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
    }
})();