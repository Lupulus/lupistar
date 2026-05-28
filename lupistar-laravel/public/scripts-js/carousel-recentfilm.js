document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".carousel-container").forEach((carousel) => {
        const filmCarousel = carousel.querySelector(".film-carousel");
        const btnLeft = carousel.querySelector(".left");
        const btnRight = carousel.querySelector(".right");
        if (!filmCarousel || !btnLeft || !btnRight) return;

        let scrollAmount = 0;
        let filmWidth = 170;
        let visibleFilms = 1;
        let maxScroll = 0;

        function recalc() {
            const first = filmCarousel.querySelector(".recent-film-item, .film-box, *");
            const gap = parseFloat(getComputedStyle(filmCarousel).gap || "0") || 0;
            filmWidth = first ? (first.getBoundingClientRect().width + gap) : 170;
            if (!isFinite(filmWidth) || filmWidth <= 0) filmWidth = 170;
            visibleFilms = Math.max(1, Math.floor(carousel.clientWidth / filmWidth));
            maxScroll = Math.max(0, filmCarousel.scrollWidth - carousel.clientWidth);
            if (scrollAmount > maxScroll) scrollAmount = maxScroll;
            filmCarousel.style.transform = `translateX(-${scrollAmount}px)`;
        }

        function updateButtons() {
            btnLeft.style.display = scrollAmount > 0 ? "block" : "none";
            btnRight.style.display = scrollAmount < maxScroll ? "block" : "none";
        }

        recalc();
        updateButtons();

        btnLeft.addEventListener("click", () => {
            scrollAmount -= filmWidth * visibleFilms;
            if (scrollAmount < 0) scrollAmount = 0;
            filmCarousel.style.transform = `translateX(-${scrollAmount}px)`;
            updateButtons();
        });

        btnRight.addEventListener("click", () => {
            scrollAmount += filmWidth * visibleFilms;
            if (scrollAmount > maxScroll) scrollAmount = maxScroll;
            filmCarousel.style.transform = `translateX(-${scrollAmount}px)`;
            updateButtons();
        });

        window.addEventListener("resize", () => {
            recalc();
            updateButtons();
        });
    });
});
