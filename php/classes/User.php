<?php
/**
 * Encapsula todas las operaciones relacionadas al usuario:
 * registro (HU-01), verificacion (HU-02), login (HU-03),
 * perfil (HU-04) y restablecimiento de contrasena (HU-05).
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower($email)]);
        return (bool) $stmt->fetch();
    }

    /**
     * HU-01, Escenario 1: crea la cuenta en estado "pendiente" y genera
     * un token de verificacion de cuenta.
     */
    public function register(string $fullName, string $email, string $password): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (full_name, email, password_hash, account_status)
             VALUES (:full_name, :email, :password_hash, :account_status)'
        );

        $stmt->execute([
            'full_name'      => $fullName,
            'email'          => strtolower($email),
            'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
            'account_status' => 'pendiente',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createToken(int $userId, string $type, int $ttlHours): string
    {
        $token = generateSecureToken();
        $expiresAt = (new DateTime())->modify("+{$ttlHours} hours")->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO tokens (user_id, token, token_type, expires_at) VALUES (:user_id, :token, :type, :expires_at)'
        );
        $stmt->execute(['user_id' => $userId, 'token' => $token, 'type' => $type, 'expires_at' => $expiresAt]);

        return $token;
    }

    /**
     * Busca un token valido (no usado y no expirado) de un tipo especifico.
     * HU-02 Escenario 2 y HU-05 dependen de esta validacion de vigencia.
     */
    public function findValidToken(string $token, string $type): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tokens
             WHERE token = :token AND token_type = :type AND used = 0
             AND datetime(expires_at) >= datetime('now')"
        );
        $stmt->execute(['token' => $token, 'type' => $type]);
        return $stmt->fetch();
    }

    public function markTokenAsUsed(int $tokenId): void
    {
        $stmt = $this->db->prepare('UPDATE tokens SET used = 1 WHERE id = :id');
        $stmt->execute(['id' => $tokenId]);
    }

    /** HU-02 Escenario 1: activa la cuenta tras validar el token. */
    public function activateAccount(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET account_status = 'activo' WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower($email)]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * RF-03 / HU-03: valida credenciales sin revelar si el correo existe,
     * y bloquea el acceso si la cuenta aun no ha sido verificada.
     */
    public function attemptLogin(string $email, string $password): array
    {
        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'reason' => 'credenciales_invalidas'];
        }

        if ($user['account_status'] !== 'activo') {
            return ['success' => false, 'reason' => 'cuenta_no_verificada'];
        }

        return ['success' => true, 'user' => $user];
    }

    /** HU-05: guarda una nueva contrasena hasheada tras validar el token. */
    public function updatePassword(int $userId, string $newPassword): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
    }

    /** HU-04: actualiza carrera, semestre y, opcionalmente, foto de perfil. */
    public function updateProfile(int $userId, string $career, string $semester, ?string $profilePhotoPath): void
    {
        if ($profilePhotoPath !== null) {
            $stmt = $this->db->prepare(
                'UPDATE users SET career = :career, semester = :semester, profile_photo = :photo WHERE id = :id'
            );
            $stmt->execute(['career' => $career, 'semester' => $semester, 'photo' => $profilePhotoPath, 'id' => $userId]);
            return;
        }

        $stmt = $this->db->prepare('UPDATE users SET career = :career, semester = :semester WHERE id = :id');
        $stmt->execute(['career' => $career, 'semester' => $semester, 'id' => $userId]);
    }

    public function updateWhatsAppPhone(int $userId, string $phone): void
    {
        $stmt = $this->db->prepare('UPDATE users SET whatsapp_phone = :phone WHERE id = :id');
        $stmt->execute(['phone' => $phone, 'id' => $userId]);
    }

    /** HU-09: recalcula el promedio de reputacion tras una nueva valoracion. */
    public function recalculateReputation(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT AVG(stars) AS average, COUNT(*) AS total FROM ratings WHERE rated_id = :id');
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch();

        $update = $this->db->prepare(
            'UPDATE users SET reputation_avg = :avg, reputation_count = :count WHERE id = :id'
        );
        $update->execute([
            'avg'   => round((float) ($result['average'] ?? 0), 2),
            'count' => (int) ($result['total'] ?? 0),
            'id'    => $userId,
        ]);
    }
}
