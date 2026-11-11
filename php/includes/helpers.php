<?php
/**
 * Funciones auxiliares de proposito general, reutilizadas en toda la app
 * para evitar duplicar logica de saneamiento, formato o redireccion.
 */

function sanitizeText(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function redirectTo(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function generateSecureToken(): string
{
    return bin2hex(random_bytes(32));
}

function isInstitutionalEmail(string $email): bool
{
    return str_ends_with(strtolower(trim($email)), strtolower(INSTITUTIONAL_EMAIL_DOMAIN));
}

/**
 * Devuelve el tipo MIME real de un archivo subido, inspeccionando su contenido
 * en el servidor. Nunca usa $_FILES[...]['type'], porque ese valor lo envia el
 * cliente y puede falsificarse.
 *
 * Usa finfo_file(FILEINFO_MIME_TYPE) (extension "fileinfo"). Si la extension no
 * esta habilitada en php.ini, recurre a getimagesize(), que tambien lee los
 * bytes del archivo, para no aceptar nunca un tipo declarado por el cliente.
 */
function detectUploadedMimeType(string $tmpPath): ?string
{
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return null;
    }

    if (function_exists('finfo_open')) {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo ? finfo_file($fileInfo, $tmpPath) : false;
        return is_string($mimeType) ? $mimeType : null;
    }

    $imageInfo = @getimagesize($tmpPath);
    return is_array($imageInfo) && isset($imageInfo['mime']) ? $imageInfo['mime'] : null;
}

/** Extension de archivo segura derivada del MIME real (no del nombre original). */
function extensionForImageMime(string $mimeType): ?string
{
    return match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => null,
    };
}

function formatPrice(float $price): string
{
    return '$' . number_format($price, 2);
}

function formatDate(string $isoDate): string
{
    $timestamp = strtotime($isoDate);
    return date('d/m/Y H:i', $timestamp);
}

function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function starsMarkup(float $average): string
{
    $fullStars = (int) round($average);
    $markup = '';
    for ($i = 1; $i <= 5; $i++) {
        $markup .= $i <= $fullStars ? '★' : '☆';
    }
    return $markup;
}

function buildWhatsAppLink(string $phone, string $productTitle): string
{
    $digitsOnly = preg_replace('/\D/', '', $phone);
    $message = "Hola, estoy interesado(a) en tu publicacion \"{$productTitle}\" en UniSwapt.";
    return 'https://wa.me/' . $digitsOnly . '?text=' . rawurlencode($message);
}
