/* ==========================================================
   EXAM SYSTEM — shared JS utilities
   ========================================================== */

/** Open a modal by id */
function esOpenModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

/** Close a modal by id */
function esCloseModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}

/** Close modal when clicking the backdrop itself */
document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('es-modal-backdrop')) {
        e.target.classList.remove('show');
    }
});

/** Close modal on Escape key */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.es-modal-backdrop.show').forEach(function (el) {
            el.classList.remove('show');
        });
    }
});

/** Generic confirm-before-submit for dangerous actions */
function esConfirm(message) {
    return window.confirm(message);
}

/** Auto-dismiss flash alert after 6s */
document.addEventListener('DOMContentLoaded', function () {
    const flash = document.getElementById('flashAlert');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity .4s';
            flash.style.opacity = '0';
            setTimeout(function () { flash.remove(); }, 400);
        }, 6000);
    }
});
