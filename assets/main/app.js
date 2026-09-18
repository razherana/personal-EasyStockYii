/**
 * Progressive enhancements for the application shell.
 *
 * Everything here is optional: navigation, links and forms keep working without JavaScript.
 */
(() => {
    'use strict';

    const body = document.body;
    const navToggle = document.querySelector('[data-nav-toggle]');

    const closeNav = () => {
        body.classList.remove('nav-open');
        navToggle?.setAttribute('aria-expanded', 'false');
    };

    navToggle?.addEventListener('click', () => {
        const isOpen = body.classList.toggle('nav-open');
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.querySelectorAll('[data-nav-close]').forEach((element) => {
        element.addEventListener('click', closeNav);
    });

    const closeDropdowns = (except) => {
        document.querySelectorAll('[data-dropdown].is-open').forEach((dropdown) => {
            if (dropdown === except) {
                return;
            }

            dropdown.classList.remove('is-open');
            dropdown.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    };

    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-toggle]');

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = dropdown.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            closeDropdowns(dropdown);
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-dropdown].is-open').forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('is-open');
                dropdown.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeNav();
        closeDropdowns(null);
    });

    // Success messages fade away on their own, errors stay until the next page.
    document.querySelectorAll('[data-flash-dismiss]').forEach((alert) => {
        window.setTimeout(() => {
            alert.style.transition = 'opacity 300ms ease';
            alert.style.opacity = '0';
            window.setTimeout(() => alert.remove(), 320);
        }, 6000);
    });
})();
