document.addEventListener('DOMContentLoaded', function() {
    // Toggle visibility
    const toggleVisibilityBtn = document.getElementById('toggle-visibility');
    const sensitiveElements = document.querySelectorAll('.sensitive-data');
    let isHidden = false;

    toggleVisibilityBtn?.addEventListener('click', function() {
        isHidden = !isHidden;
        sensitiveElements.forEach(element => {
            if (isHidden) {
                element.classList.add('blur-sm');
                toggleVisibilityBtn.querySelector('svg').classList.add('text-red-500');
            } else {
                element.classList.remove('blur-sm');
                toggleVisibilityBtn.querySelector('svg').classList.remove('text-red-500');
            }
        });
    });

    // Calendar accordion
    const accordionButtons = document.querySelectorAll('[data-accordion-target]');
    accordionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-accordion-target');
            const content = document.querySelector(target);
            const icon = this.querySelector('.accordion-icon');

            // Toggle the accordion
            if (content.classList.contains('open')) {
                content.classList.remove('open');
                icon.classList.remove('rotate');
                this.setAttribute('aria-expanded', 'false');
            } else {
                content.classList.add('open');
                icon.classList.add('rotate');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Ajouter une animation de chargement initial
    window.addEventListener('load', function() {
        document.querySelectorAll('.calendar-day').forEach((day, index) => {
            setTimeout(() => {
                day.classList.add('opacity-100');
                day.classList.remove('opacity-0');
            }, index * 50);
        });
    });
});
