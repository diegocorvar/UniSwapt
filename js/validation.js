/**
 * Validaciones del lado del cliente. La validacion real y definitiva
 * siempre vive en PHP; esto solo mejora la experiencia dando
 * retroalimentacion inmediata (HU-01, HU-06).
 */

document.addEventListener('DOMContentLoaded', () => {
    initRegisterFormValidation();
    initPublishFormBehavior();
});

function initRegisterFormValidation() {
    const form = document.querySelector('[data-role="register-form"]');
    if (!form) return;

    const emailInput = form.querySelector('#email');
    const passwordInput = form.querySelector('#password');
    const confirmInput = form.querySelector('#password_confirm');
    const emailError = form.querySelector('#email-error');
    const confirmError = form.querySelector('#confirm-error');

    form.addEventListener('submit', (event) => {
        let hasError = false;

        if (!emailInput.value.trim().toLowerCase().endsWith('@itesa.edu.mx')) {
            emailError.textContent = 'Dominio no permitido. Debes utilizar un correo institucional valido.';
            hasError = true;
        } else {
            emailError.textContent = '';
        }

        if (passwordInput.value !== confirmInput.value) {
            confirmError.textContent = 'Las contrasenas no coinciden.';
            hasError = true;
        } else {
            confirmError.textContent = '';
        }

        if (hasError) {
            event.preventDefault();
        }
    });
}

// HU-06: la modalidad Trueque/Donacion deshabilita el precio, y Trueque
// habilita el campo de "materiales de interes".
function initPublishFormBehavior() {
    const form = document.querySelector('[data-role="publish-form"]');
    if (!form) return;

    const modalityInputs = form.querySelectorAll('input[name="modality"]');
    const priceField = form.querySelector('#price');
    const tradeInterestGroup = form.querySelector('[data-role="trade-interest-group"]');

    function applyModalityRules() {
        const selected = form.querySelector('input[name="modality"]:checked')?.value;
        const isFreeModality = selected === 'Trueque' || selected === 'Donacion';

        priceField.disabled = isFreeModality;
        priceField.value = isFreeModality ? 0 : priceField.value;
        priceField.required = !isFreeModality;

        tradeInterestGroup.style.display = selected === 'Trueque' ? 'block' : 'none';
    }

    modalityInputs.forEach((input) => input.addEventListener('change', applyModalityRules));
    applyModalityRules();
}
