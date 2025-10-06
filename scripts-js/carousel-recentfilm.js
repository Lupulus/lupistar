document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".carousel-container").forEach((carousel) => {
        const filmCarousel = carousel.querySelector(".film-carousel");
        const btnLeft = carousel.querySelector(".left");
        const btnRight = carousel.querySelector(".right");

        let scrollAmount = 0;
        let filmWidth = 170; // Largeur de chaque film + espace (160px + 10px de gap)
        let visibleFilms = Math.floor(carousel.clientWidth / filmWidth);
        let maxScroll = filmCarousel.scrollWidth - carousel.clientWidth;

        function updateButtons() {
            btnLeft.style.display = scrollAmount > 0 ? "block" : "none";
            btnRight.style.display = scrollAmount < maxScroll ? "block" : "none";
        }

        // Initialisation des boutons
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

        // Mise à jour en cas de redimensionnement
        window.addEventListener("resize", () => {
            visibleFilms = Math.floor(carousel.clientWidth / filmWidth);
            maxScroll = filmCarousel.scrollWidth - carousel.clientWidth;
            updateButtons();
        });
    });
});