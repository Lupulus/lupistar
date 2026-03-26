class ScrollToTopManager {
    constructor() {
        this.button = document.getElementById('scrollToTopBtn');
        this.header = document.querySelector('header, .header, nav, .nav');
        this.isVisible = false;
        this.scrollThreshold = 100;

        this.init();
    }

    init() {
        if (!this.button) return;

        window.addEventListener('scroll', this.handleScroll.bind(this));
        this.button.addEventListener('click', this.scrollToTop.bind(this));
        this.handleScroll();
    }

    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const shouldShow = this.shouldShowButton(scrollTop);

        if (shouldShow && !this.isVisible) {
            this.showButton();
        } else if (!shouldShow && this.isVisible) {
            this.hideButton();
        }
    }

    shouldShowButton(scrollTop) {
        if (scrollTop < this.scrollThreshold) {
            return false;
        }

        if (this.header) {
            const headerRect = this.header.getBoundingClientRect();
            return headerRect.bottom < 0;
        }

        return true;
    }

    showButton() {
        this.button.classList.add('visible');
        this.isVisible = true;

        setTimeout(() => {
            if (this.isVisible) {
                this.button.classList.add('pulse');
            }
        }, 1000);
    }

    hideButton() {
        this.button.classList.remove('visible', 'pulse');
        this.isVisible = false;
    }

    scrollToTop() {
        if ('scrollBehavior' in document.documentElement.style) {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
            return;
        }

        const scrollStep = -window.scrollY / (500 / 15);

        const scrollAnimation = () => {
            if (window.scrollY !== 0) {
                window.scrollBy(0, scrollStep);
                requestAnimationFrame(scrollAnimation);
            }
        };

        requestAnimationFrame(scrollAnimation);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    window.scrollToTopManager = new ScrollToTopManager();
});

function updateScrollToTopButton() {
    if (window.scrollToTopManager) {
        window.scrollToTopManager.handleScroll();
    }
}

