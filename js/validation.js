/**
 * Validaciones del lado del cliente. La validacion real y definitiva
 * siempre vive en PHP; esto solo mejora la experiencia dando
 * retroalimentacion inmediata (HU-01, HU-06).
 */

document.addEventListener('DOMContentLoaded', () => {
    initInstitutionalEmailFields();
    initRegisterFormValidation();
    initPublishFormBehavior();
});

/** Misma forma canonica que normalizeEmail() en PHP. */
function normalizeEmail(value) {
    return value.trim().toLowerCase();
}

/**
 * Replica isInstitutionalEmail() de PHP: parte local con [a-z0-9._-] (sin
 * puntos al inicio, al final ni consecutivos) y dominio exactamente igual al
 * institucional, sin subdominios ni dominios parecidos.
 * Devuelve el mensaje de error, o '' si el correo es valido.
 */
function institutionalEmailError(rawValue, domain) {
    const email = normalizeEmail(rawValue);

    if (email === '') {
        return 'Ingresa tu correo institucional.';
    }

    const parts = email.split('@');
    if (parts.length !== 2 || parts[0] === '') {
        return 'El formato del correo electronico no es valido.';
    }

    const [localPart, emailDomain] = parts;
    if (emailDomain !== domain) {
        return `Dominio no permitido. Debes utilizar un correo @${domain}.`;
    }

    const hasValidCharacters = /^[a-z0-9._-]+$/.test(localPart);
    const hasValidDots = !localPart.startsWith('.') && !localPart.endsWith('.') && !localPart.includes('..');
    if (!hasValidCharacters || !hasValidDots || localPart.length > 64) {
        return 'Antes de la @ solo se permiten letras, numeros, puntos, guiones y guiones bajos.';
    }

    return '';
}

/**
 * Valida en tiempo real los campos con data-institutional-email="<dominio>".
 * El mensaje se muestra en el .field-error del mismo grupo y tambien se
 * registra con setCustomValidity para que el navegador bloquee el envio.
 */
function initInstitutionalEmailFields() {
    document.querySelectorAll('input[data-institutional-email]').forEach((input) => {
        const domain = input.dataset.institutionalEmail.toLowerCase();
        const errorElement = input.closest('.field-group')?.querySelector('.field-error');
        let touched = false;

        const validate = () => {
            const message = institutionalEmailError(input.value, domain);
            input.setCustomValidity(message);
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
            if (errorElement) {
                errorElement.textContent = touched ? message : '';
            }
            return message === '';
        };

        input.addEventListener('input', validate);
        input.addEventListener('blur', () => {
            touched = input.value.trim() !== '' || touched;
            input.value = normalizeEmail(input.value);
            validate();
        });

        input.form?.addEventListener('submit', (event) => {
            touched = true;
            input.value = normalizeEmail(input.value);
            if (!validate()) {
                event.preventDefault();
                input.focus();
            }
        });

        validate();
    });
}

function initRegisterFormValidation() {
    const form = document.querySelector('[data-role="register-form"]');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmInput = form.querySelector('#password_confirm');
    const confirmError = form.querySelector('#confirm-error');

    // El correo lo valida initInstitutionalEmailFields(); aqui solo las contrasenas.
    form.addEventListener('submit', (event) => {
        let hasError = false;

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
