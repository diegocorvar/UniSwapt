/**
 * Gestiona la seleccion de 1 a 5 imagenes con vista previa, marcado de
 * portada y eliminacion individual antes de enviar el formulario.
 * HU-07: limite de 5 imagenes, 5MB cada una, formatos JPG/PNG/WebP.
 */
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.querySelector('[data-role="image-drop-zone"]');
    if (!dropZone) return;

    const fileInput = document.querySelector('[data-role="image-input"]');
    const previewGrid = document.querySelector('[data-role="image-preview-grid"]');
    const errorBox = document.querySelector('[data-role="image-error"]');

    const MAX_FILES = 5;
    const MAX_SIZE_BYTES = 5 * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    let selectedFiles = [];

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.classList.add('is-dragover');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('is-dragover'));

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.classList.remove('is-dragover');
        handleNewFiles(event.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => handleNewFiles(fileInput.files));

    function handleNewFiles(fileList) {
        errorBox.textContent = '';

        for (const file of fileList) {
            if (selectedFiles.length >= MAX_FILES) {
                errorBox.textContent = `Solo se permite un maximo de ${MAX_FILES} imagenes por publicacion.`;
                break;
            }
            if (!ALLOWED_TYPES.includes(file.type)) {
                errorBox.textContent = 'Formato no soportado. Usa JPG, PNG o WebP.';
                continue;
            }
            if (file.size > MAX_SIZE_BYTES) {
                errorBox.textContent = `"${file.name}" supera el limite de 5MB.`;
                continue;
            }
            selectedFiles.push(file);
        }

        syncInputFiles();
        renderPreviews();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        syncInputFiles();
        renderPreviews();
    }

    // El input real solo acepta un FileList, asi que lo reconstruimos con DataTransfer.
    function syncInputFiles() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach((file) => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }

    function renderPreviews() {
        previewGrid.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (event) => {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.innerHTML = `
                    ${index === 0 ? '<span class="cover-tag">Portada</span>' : ''}
                    <img src="${event.target.result}" alt="Vista previa ${index + 1}">
                    <button type="button" aria-label="Quitar imagen">&times;</button>
                `;
                item.querySelector('button').addEventListener('click', () => removeFile(index));
                previewGrid.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }
}
);
