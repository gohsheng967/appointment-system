import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-loading-form]').forEach((formElement) => {
        if (!(formElement instanceof HTMLFormElement)) {
            return;
        }

        formElement.addEventListener('submit', () => {
            if (formElement.dataset.submitting === 'true') {
                return;
            }

            formElement.dataset.submitting = 'true';

            formElement.querySelectorAll('[data-loading-button]').forEach((buttonElement) => {
                if (!(buttonElement instanceof HTMLButtonElement)) {
                    return;
                }

                buttonElement.disabled = true;
                buttonElement.setAttribute('aria-busy', 'true');
            });

            formElement.querySelectorAll('[data-loading-default]').forEach((element) => {
                element.classList.add('hidden');
            });

            formElement.querySelectorAll('[data-loading-active]').forEach((element) => {
                element.classList.remove('hidden');
                element.classList.add('inline-flex');
            });
        });
    });
});
