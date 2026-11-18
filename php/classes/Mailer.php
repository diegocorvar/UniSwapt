<?php
/**
 * Flujo de Trabajo 04: notificaciones por correo (verificacion de cuenta y
 * restablecimiento de contrasena).
 *
 * Modo hibrido, controlado por variables de entorno:
 * - Produccion (MAIL_API_KEY configurada): envia el correo real con la API
 *   REST transaccional de Brevo (https://api.brevo.com/v3/smtp/email) usando
 *   cURL nativo de PHP. Requiere MAIL_FROM_EMAIL (remitente verificado en
 *   Brevo); MAIL_FROM_NAME es opcional (por defecto "UniSwapt ITESA").
 * - Local (sin MAIL_API_KEY): guarda el correo como archivo HTML en
 *   html/emails/ (bandeja simulada) para poder desarrollar sin internet.
 *
 * Los enlaces se construyen con APP_URL (URL publica en Railway) o, si no
 * existe, a partir de la peticion actual respetando X-Forwarded-Proto.
 *
 * Cada metodo devuelve ['sent' => bool, 'inbox_path' => ?string]; inbox_path
 * solo tiene valor en modo local.
 */
class Mailer
{
    private const BREVO_ENDPOINT = 'https://api.brevo.com/v3/smtp/email';
    private const DEFAULT_FROM_NAME = 'UniSwapt ITESA';

    /** @return array{sent: bool, inbox_path: ?string} */
    public static function sendVerificationEmail(string $toEmail, string $fullName, string $token): array
    {
        $subject = 'Confirma tu cuenta en UniSwapt';
        // Todo dato dinamico se escapa: un nombre como "<script>" no debe
        // interpretarse como HTML ni en el cliente de correo ni en la bandeja local.
        $safeName = self::escape($fullName);
        $verificationLink = self::escape(self::buildAppUrl('verify.php?token=' . rawurlencode($token)));
        $ttlHours = self::escape((string) VERIFICATION_TOKEN_TTL_HOURS);

        $body = self::renderTemplate($subject, "
            <p>Hola, {$safeName}:</p>
            <p>Gracias por registrarte en UniSwapt con tu correo institucional.</p>
            <p>Confirma tu cuenta haciendo clic en el siguiente enlace (valido por {$ttlHours} horas):</p>
            <p><a href=\"{$verificationLink}\">{$verificationLink}</a></p>
            <p>Si tu no creaste esta cuenta, ignora este mensaje.</p>
        ");

        return self::send($toEmail, $fullName, $subject, $body);
    }

    /** @return array{sent: bool, inbox_path: ?string} */
    public static function sendPasswordResetEmail(string $toEmail, string $fullName, string $token): array
    {
        $subject = 'Restablece tu contrasena de UniSwapt';
        $safeName = self::escape($fullName);
        $resetLink = self::escape(self::buildAppUrl('reset-password.php?token=' . rawurlencode($token)));
        $ttlHours = self::escape((string) PASSWORD_RESET_TTL_HOURS);

        $body = self::renderTemplate($subject, "
            <p>Hola, {$safeName}:</p>
            <p>Recibimos una solicitud para restablecer tu contrasena. Este enlace es valido por {$ttlHours} hora(s):</p>
            <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
            <p>Si tu no solicitaste esto, ignora este mensaje.</p>
        ");

        return self::send($toEmail, $fullName, $subject, $body);
    }

    /** true cuando no hay MAIL_API_KEY y los correos van a la bandeja simulada. */
    public static function isSimulated(): bool
    {
        return envValue('MAIL_API_KEY') === null;
    }

    /** @return array{sent: bool, inbox_path: ?string} */
    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody): array
    {
        if (self::isSimulated()) {
            return ['sent' => true, 'inbox_path' => self::deliverToSimulatedInbox($toEmail, $htmlBody)];
        }

        return ['sent' => self::deliverWithBrevo($toEmail, $toName, $subject, $htmlBody), 'inbox_path' => null];
    }

    /**
     * Envia el correo con la API HTTP de Brevo. Nunca cae a la bandeja simulada:
     * en produccion esos archivos serian publicos y expondrian los tokens.
     */
    private static function deliverWithBrevo(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $apiKey = envValue('MAIL_API_KEY');
        $fromEmail = envValue('MAIL_FROM_EMAIL');
        $fromName = envValue('MAIL_FROM_NAME', self::DEFAULT_FROM_NAME);

        if ($fromEmail === null) {
            error_log('UniSwapt Mailer: MAIL_FROM_EMAIL no esta configurada; no se envio el correo.');
            return false;
        }

        if (!function_exists('curl_init')) {
            error_log('UniSwapt Mailer: la extension cURL no esta habilitada; no se envio el correo.');
            return false;
        }

        $payload = json_encode([
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($payload === false) {
            error_log('UniSwapt Mailer: no se pudo codificar el correo como JSON: ' . json_last_error_msg());
            return false;
        }

        $curl = curl_init(self::BREVO_ENDPOINT);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey,
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        unset($curl); // curl_close() es innecesario desde PHP 8 y esta obsoleto en 8.5.

        if ($responseBody === false) {
            error_log("UniSwapt Mailer: fallo la conexion con Brevo ({$curlError}).");
            return false;
        }

        if (!in_array($statusCode, [200, 201], true)) {
            // El cuerpo de error de Brevo no incluye la API key; se recorta por si es largo.
            error_log(sprintf(
                'UniSwapt Mailer: Brevo respondio HTTP %d al enviar "%s": %s',
                $statusCode,
                $subject,
                substr((string) $responseBody, 0, 500)
            ));
            return false;
        }

        return true;
    }

    /** Escapa texto para insertarlo en HTML o en atributos entre comillas. */
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** $htmlBody ya debe venir con sus datos dinamicos escapados. */
    private static function renderTemplate(string $subject, string $htmlBody): string
    {
        $safeSubject = self::escape($subject);
        return "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>{$safeSubject}</title></head>"
            . "<body style='font-family: sans-serif; max-width:520px; margin:40px auto; color:#20242c;'>"
            . "<h2 style='color:#8a5a2b;'>UniSwapt</h2>{$htmlBody}"
            . "<hr><small>Correo automatico de UniSwapt, la plataforma de intercambio de material academico de ITESA. No respondas a este mensaje.</small></body></html>";
    }

    private static function deliverToSimulatedInbox(string $toEmail, string $htmlBody): string
    {
        if (!is_dir(SIMULATED_INBOX_DIR)) {
            mkdir(SIMULATED_INBOX_DIR, 0755, true);
        }

        $fileName = 'correo_' . preg_replace('/[^a-zA-Z0-9]/', '_', $toEmail) . '_' . time() . '.html';
        file_put_contents(SIMULATED_INBOX_DIR . $fileName, $htmlBody);

        return 'html/emails/' . $fileName;
    }

    /**
     * URL absoluta hacia una pagina publica de la app. Prioriza APP_URL (p. ej.
     * https://uniswapt.up.railway.app); si no existe, la deduce de la peticion,
     * detectando HTTPS tambien detras del proxy inverso de Railway.
     */
    private static function buildAppUrl(string $relativePath): string
    {
        $appUrl = envValue('APP_URL');
        if ($appUrl !== null) {
            return rtrim($appUrl, '/') . '/' . ltrim($relativePath, '/');
        }

        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $isHttps = $forwardedProto === 'https'
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $isHttps ? 'https' : 'http';

        // Sin APP_URL el host sale de la cabecera Host de la peticion; en produccion
        // conviene definir APP_URL para que los enlaces no dependan de ella.
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        // Los correos se envian desde php/actions/*-action.php; los enlaces deben
        // apuntar a la raiz de la aplicacion (verify.php, reset-password.php...).
        $basePath = rtrim(preg_replace('#/php/actions$#', '', $basePath), '/');

        return "{$scheme}://{$host}{$basePath}/{$relativePath}";
    }
}
