<?php
/**
 * Simula el "Flujo de Trabajo 04: Notificaciones e Integracion con
 * Servicio de Correo (SMTP)". En un entorno de produccion, send()
 * llamaria a un proveedor real (SendGrid, Nodemailer, etc). Como este
 * es un prototipo 100% local sin servicios en la nube, en su lugar
 * guardamos el correo como un archivo HTML dentro de una "bandeja de
 * entrada simulada", para poder revisarlo desde el navegador.
 *
 * La logica de negocio (generacion y expiracion de tokens) es real;
 * unicamente el transporte del correo esta simulado.
 */
class Mailer
{
    public static function sendVerificationEmail(string $toEmail, string $fullName, string $token): string
    {
        // Todo dato dinamico se escapa: el correo se guarda como HTML y se sirve
        // desde el mismo origen, asi que un nombre como "<script>" seria un XSS almacenado.
        $safeName = self::escape($fullName);
        $verificationLink = self::escape(self::buildAppUrl('verify.php?token=' . rawurlencode($token)));

        $body = self::renderTemplate('Confirma tu cuenta en UniSwapt', "
            <p>Hola, {$safeName}:</p>
            <p>Gracias por registrarte en UniSwapt con tu correo institucional.</p>
            <p>Confirma tu cuenta haciendo clic en el siguiente enlace (valido por " . VERIFICATION_TOKEN_TTL_HOURS . " horas):</p>
            <p><a href=\"{$verificationLink}\">{$verificationLink}</a></p>
        ");

        return self::deliverToSimulatedInbox($toEmail, 'Confirma tu cuenta en UniSwapt', $body);
    }

    public static function sendPasswordResetEmail(string $toEmail, string $fullName, string $token): string
    {
        $safeName = self::escape($fullName);
        $resetLink = self::escape(self::buildAppUrl('reset-password.php?token=' . rawurlencode($token)));

        $body = self::renderTemplate('Restablece tu contrasena', "
            <p>Hola, {$safeName}:</p>
            <p>Recibimos una solicitud para restablecer tu contrasena. Este enlace es valido por " . PASSWORD_RESET_TTL_HOURS . " hora(s):</p>
            <p><a href=\"{$resetLink}\">{$resetLink}</a></p>
            <p>Si tu no solicitaste esto, ignora este mensaje.</p>
        ");

        return self::deliverToSimulatedInbox($toEmail, 'Restablece tu contrasena de UniSwapt', $body);
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
            . "<hr><small>Correo generado por el prototipo local de UniSwapt.</small></body></html>";
    }

    private static function deliverToSimulatedInbox(string $toEmail, string $subject, string $htmlBody): string
    {
        if (!is_dir(SIMULATED_INBOX_DIR)) {
            mkdir(SIMULATED_INBOX_DIR, 0755, true);
        }

        $fileName = 'correo_' . preg_replace('/[^a-zA-Z0-9]/', '_', $toEmail) . '_' . time() . '.html';
        $filePath = SIMULATED_INBOX_DIR . $fileName;
        file_put_contents($filePath, $htmlBody);

        return 'html/emails/' . $fileName;
    }

    private static function buildAppUrl(string $relativePath): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        // Los correos se envian desde php/actions/*-action.php; los enlaces deben
        // apuntar a la raiz de la aplicacion (verify.php, reset-password.php...).
        $basePath = rtrim(preg_replace('#/php/actions$#', '', $basePath), '/');
        return "{$scheme}://{$host}{$basePath}/{$relativePath}";
    }
}
