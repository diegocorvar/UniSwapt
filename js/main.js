/**
 * Utilidades compartidas por toda la aplicacion.
 * Cada modulo especifico (validation.js, image-upload.js, star-rating.js)
 * se engancha por si mismo si encuentra los elementos que necesita.
 */

// Cierra automaticamente los mensajes de alerta despues de unos segundos.
document.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.4s ease';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 400);
        }, 6000);
    }

    initAutoSubmitFilters();
});

// El catalogo aplica los filtros automaticamente al cambiar cualquier campo.
function initAutoSubmitFilters() {
    const filterForm = document.querySelector('[data-role="filter-form"]');
    if (!filterForm) return;

    filterForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => filterForm.submit());
    });
}
