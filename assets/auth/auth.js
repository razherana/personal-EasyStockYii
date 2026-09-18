/**
 * Sign in / public pages helpers: reveal the password that was typed.
 */
(() => {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('data-password-toggle') ?? '');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const revealed = input.getAttribute('type') === 'password';
            input.setAttribute('type', revealed ? 'text' : 'password');
            button.setAttribute('aria-pressed', revealed ? 'true' : 'false');
            button.querySelector('i')?.classList.toggle('fa-eye', !revealed);
            button.querySelector('i')?.classList.toggle('fa-eye-slash', revealed);
        });
    });
})();
