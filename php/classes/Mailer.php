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
        $verificationLink = self::buildAppUrl('verify.php?token=' . $token);

        $body = self::renderTemplate('Confirma tu cuenta en UniMarket', "
            <p>Hola, {$fullName}:</p>
            <p>Gracias por registrarte en UniMarket con tu correo institucional.</p>
            <p>Confirma tu cuenta haciendo clic en el siguiente enlace (valido por " . VERIFICATION_TOKEN_TTL_HOURS . " horas):</p>
            <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
        ");

        return self::deliverToSimulatedInbox($toEmail, 'Confirma tu cuenta en UniMarket', $body);
    }

    public static function sendPasswordResetEmail(string $toEmail, string $fullName, string $token): string
    {
        $resetLink = self::buildAppUrl('reset-password.php?token=' . $token);

        $body = self::renderTemplate('Restablece tu contrasena', "
            <p>Hola, {$fullName}:</p>
            <p>Recibimos una solicitud para restablecer tu contrasena. Este enlace es valido por " . PASSWORD_RESET_TTL_HOURS . " hora(s):</p>
            <p><a href='{$resetLink}'>{$resetLink}</a></p>
            <p>Si tu no solicitaste esto, ignora este mensaje.</p>
        ");

        return self::deliverToSimulatedInbox($toEmail, 'Restablece tu contrasena de UniMarket', $body);
    }

    private static function renderTemplate(string $subject, string $htmlBody): string
    {
        return "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>{$subject}</title></head>"
            . "<body style='font-family: sans-serif; max-width:520px; margin:40px auto; color:#20242c;'>"
            . "<h2 style='color:#8a5a2b;'>UniMarket</h2>{$htmlBody}"
            . "<hr><small>Correo generado por el prototipo local de UniMarket.</small></body></html>";
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
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        return "{$scheme}://{$host}{$basePath}/{$relativePath}";
    }
}
