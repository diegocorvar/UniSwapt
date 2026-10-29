/**
 * Convierte un grupo de radios ocultos en un selector visual de estrellas.
 * HU-09: calificacion de 1 a 5 estrellas tras concretar una transaccion.
 */
document.addEventListener('DOMContentLoaded', () => {
    const widget = document.querySelector('[data-role="star-rating-widget"]');
    if (!widget) return;

    const stars = Array.from(widget.querySelectorAll('[data-star-value]'));
    const hiddenInput = widget.querySelector('input[name="stars"]');

    function paintStars(value) {
        stars.forEach((star) => {
            const starValue = Number(star.dataset.starValue);
            star.textContent = starValue <= value ? '★' : '☆';
        });
    }

    stars.forEach((star) => {
        star.addEventListener('click', () => {
            const value = Number(star.dataset.starValue);
            hiddenInput.value = String(value);
            paintStars(value);
        });

        star.addEventListener('mouseenter', () => paintStars(Number(star.dataset.starValue)));
    });

    widget.addEventListener('mouseleave', () => paintStars(Number(hiddenInput.value) || 0));

    paintStars(Number(hiddenInput.value) || 0);
});
