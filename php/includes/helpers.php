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

/**
 * Lee una variable de entorno (Railway la expone por getenv(); algunos
 * servidores PHP-FPM solo la copian a $_ENV o $_SERVER). Devuelve null si no
 * existe o esta vacia.
 */
function envValue(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value === false) {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;
    }

    if (!is_string($value) || trim($value) === '') {
        return $default;
    }

    return trim($value);
}

/** Forma canonica del correo: sin espacios y en minusculas (asi se guarda y se busca). */
function normalizeEmail(string $email): string
{
    $email = trim($email);
    return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
}

/** Valida la sintaxis del correo en el servidor (RFC 5322 simplificado de PHP). */
function isValidEmailFormat(string $email): bool
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/** Dominio institucional sin la arroba inicial (p. ej. "itesa.edu.mx"). */
function institutionalEmailDomain(): string
{
    return strtolower(ltrim(INSTITUTIONAL_EMAIL_DOMAIN, '@'));
}

/**
 * RF-01: correo con formato valido cuyo dominio es exactamente el
 * institucional. Rechaza subdominios ("@sub.itesa.edu.mx") y dominios que
 * solo terminan igual ("@falso-itesa.edu.mx").
 */
function isInstitutionalEmail(string $email): bool
{
    $email = normalizeEmail($email);

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }

    [$localPart, $domain] = $parts;

    return $localPart !== ''
        && preg_match('/^[a-z0-9._-]+$/', $localPart) === 1
        && $domain === institutionalEmailDomain();
}

/**
 * Atributo HTML "pattern" equivalente a isInstitutionalEmail() para validar en
 * el navegador. El dominio admite mayusculas porque el servidor normaliza.
 * Se escapa para el modo "v" de las expresiones regulares de pattern.
 */
function institutionalEmailHtmlPattern(): string
{
    $domainPattern = '';
    foreach (str_split(institutionalEmailDomain()) as $character) {
        $domainPattern .= ctype_alpha($character)
            ? '[' . strtolower($character) . strtoupper($character) . ']'
            : '\\' . $character;
    }

    return '[A-Za-z0-9._\\-]+@' . $domainPattern;
}

/**
 * Atributos comunes de los <input> de correo institucional: pattern para el
 * navegador y data-institutional-email para js/validation.js.
 */
function institutionalEmailInputAttributes(): string
{
    return 'pattern="' . sanitizeText(institutionalEmailHtmlPattern()) . '"'
        . ' title="Usa tu correo institucional ' . sanitizeText(INSTITUTIONAL_EMAIL_DOMAIN) . '"'
        . ' data-institutional-email="' . sanitizeText(institutionalEmailDomain()) . '"'
        . ' autocomplete="email" autocapitalize="none" spellcheck="false"';
}

/**
 * Datos que muestra check-email.php tras enviar un correo. El enlace directo
 * solo existe en modo local (bandeja simulada); con envio real nunca se
 * expone, porque permitiria verificar una cuenta sin acceso al buzon.
 *
 * @param array{sent: bool, inbox_path: ?string}|null $delivery null si no se envio nada.
 */
function pendingEmailAction(string $mode, string $email, ?array $delivery = null, ?string $directLink = null): array
{
    $inboxPath = $delivery['inbox_path'] ?? null;

    return [
        'mode'        => $mode,
        'email'       => $email,
        'inbox_path'  => $inboxPath,
        'direct_link' => $inboxPath !== null ? $directLink : null,
    ];
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

/**
 * URL para el atributo src de una imagen subida. Las guardadas en Supabase
 * Storage ya son URLs absolutas y se devuelven tal cual; las locales son solo
 * el nombre del archivo y se les antepone su carpeta publica.
 *
 * @param string $type 'products' (imagenes de publicaciones) o 'profiles' (fotos de perfil).
 */
function resolveUploadUrl(string $path, string $type = 'products'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $baseUrl = $type === 'profiles' ? UPLOAD_URL_PROFILES : UPLOAD_URL_PRODUCTS;
    return $baseUrl . $path;
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
