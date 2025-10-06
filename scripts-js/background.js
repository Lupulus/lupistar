console.log("✅ Le script background.js est bien chargé !");

document.addEventListener("DOMContentLoaded", function () {
    let background = document.querySelector(".background");

    if (!background) {
        console.error("❌ Erreur : L'élément .background n'existe pas !");
        return;
    } else {
        console.log("✅ L'élément .background a bien été trouvé !");
    }

    let manualScrollY = 0; // Variable pour suivre le scroll simulé
    const maxScrollLimit = 2000; // Limite de scroll définie à 2000px

    function updateBlur() {
        let scrollValue = manualScrollY / (maxScrollLimit * 2); // Normalisation sur 2000px max
        let blurValue = Math.min(scrollValue * 12, 15);

        console.log(`📜 Scroll Calculé: ${manualScrollY}, Blur: ${blurValue}px`); // Vérifie les valeurs

        background.style.filter = `blur(${blurValue}px)`;
    }

    // Scroll normal (au cas où il fonctionne)
    window.addEventListener("scroll", function () {
        manualScrollY = Math.min(window.scrollY, maxScrollLimit);
        updateBlur();
    });

    // Scroll via molette
    window.addEventListener("wheel", function (event) {
        console.log("🎡 Scroll détecté via la molette !");
        manualScrollY += event.deltaY; // Ajoute la distance de scroll
        manualScrollY = Math.max(0, Math.min(manualScrollY, maxScrollLimit)); // Limite entre 0 et 2000
        updateBlur();
    });

    // Scroll tactile (mobile, trackpad)
    window.addEventListener("touchmove", function (event) {
        console.log("📱 Scroll détecté via un mouvement tactile !");
        manualScrollY += event.touches[0].clientY / 10; // Ajustement pour le tactile
        manualScrollY = Math.max(0, Math.min(manualScrollY, maxScrollLimit)); // Limite entre 0 et 2000
        updateBlur();
    });
});