export function initUiModals() {
    document.querySelectorAll('dialog[data-ui-modal]').forEach((dialog) => {
        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }

        if (dialog.dataset.openOnLoad === '1' && !dialog.open) {
            dialog.showModal();
        }
    });

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const id = trigger.getAttribute('data-modal-open');
            if (!id) {
                return;
            }
            const dialog = document.getElementById(id);
            if (!(dialog instanceof HTMLDialogElement)) {
                return;
            }
            event.preventDefault();
            dialog.showModal();
        });
    });
}
