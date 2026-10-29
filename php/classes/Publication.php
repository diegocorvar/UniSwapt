<?php
/**
 * Encapsula la logica de publicaciones: alta de item (HU-06),
 * galeria de imagenes (HU-07), filtros por categoria (HU-08)
 * y cambio de estado (RF-10).
 */
class Publication
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** HU-06: crea la publicacion aplicando las reglas de cada modalidad. */
    public function create(int $userId, array $data): int
    {
        $isFreeModality = in_array($data['modality'], ['Trueque', 'Donacion'], true);
        $price = $isFreeModality ? 0 : (float) $data['price'];
        $tradeInterest = $data['modality'] === 'Trueque' ? $data['trade_interest'] : null;

        $stmt = $this->db->prepare(
            'INSERT INTO publications
                (user_id, title, description, item_condition, modality, price, trade_interest,
                 career_category, material_category, publication_status)
             VALUES
                (:user_id, :title, :description, :item_condition, :modality, :price, :trade_interest,
                 :career_category, :material_category, :publication_status)'
        );

        $stmt->execute([
            'user_id'            => $userId,
            'title'              => $data['title'],
            'description'        => $data['description'],
            'item_condition'     => $data['item_condition'],
            'modality'           => $data['modality'],
            'price'              => $price,
            'trade_interest'     => $tradeInterest,
            'career_category'    => $data['career_category'],
            'material_category'  => $data['material_category'],
            'publication_status' => 'Disponible',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** HU-07: asocia una imagen a la publicacion; la primera se marca como portada. */
    public function addImage(int $publicationId, string $imagePath, bool $isCover): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO publication_images (publication_id, image_path, is_cover) VALUES (:pub_id, :path, :is_cover)'
        );
        $stmt->execute(['pub_id' => $publicationId, 'path' => $imagePath, 'is_cover' => $isCover ? 1 : 0]);
    }

    public function getImages(int $publicationId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM publication_images WHERE publication_id = :id ORDER BY is_cover DESC, id ASC'
        );
        $stmt->execute(['id' => $publicationId]);
        return $stmt->fetchAll();
    }

    public function getCoverImage(int $publicationId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT image_path FROM publication_images WHERE publication_id = :id ORDER BY is_cover DESC, id ASC LIMIT 1'
        );
        $stmt->execute(['id' => $publicationId]);
        $row = $stmt->fetch();
        return $row['image_path'] ?? null;
    }

    /** HU-08: catalogo con filtros combinados por carrera, categoria y busqueda de texto. */
    public function search(array $filters): array
    {
        $conditions = ["p.publication_status = 'Disponible'"];
        $params = [];

        if (!empty($filters['career_category'])) {
            $conditions[] = 'p.career_category = :career_category';
            $params['career_category'] = $filters['career_category'];
        }

        if (!empty($filters['material_category'])) {
            $conditions[] = 'p.material_category = :material_category';
            $params['material_category'] = $filters['material_category'];
        }

        if (!empty($filters['modality'])) {
            $conditions[] = 'p.modality = :modality';
            $params['modality'] = $filters['modality'];
        }

        if (!empty($filters['search_term'])) {
            $conditions[] = '(p.title LIKE :search_term OR p.description LIKE :search_term)';
            $params['search_term'] = '%' . $filters['search_term'] . '%';
        }

        $whereClause = implode(' AND ', $conditions);

        $stmt = $this->db->prepare(
            "SELECT p.*, u.full_name AS seller_name, u.reputation_avg
             FROM publications p
             JOIN users u ON u.id = p.user_id
             WHERE {$whereClause}
             ORDER BY p.created_at DESC"
        );
        $stmt->execute($params);
        $publications = $stmt->fetchAll();

        foreach ($publications as &$publication) {
            $publication['cover_image'] = $this->getCoverImage((int) $publication['id']);
        }

        return $publications;
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.full_name AS seller_name, u.whatsapp_phone, u.reputation_avg, u.reputation_count
             FROM publications p JOIN users u ON u.id = p.user_id WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM publications WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        $publications = $stmt->fetchAll();

        foreach ($publications as &$publication) {
            $publication['cover_image'] = $this->getCoverImage((int) $publication['id']);
        }

        return $publications;
    }

    /** RF-10: actualiza el estado de la publicacion, solo permitido al dueno. */
    public function updateStatus(int $publicationId, int $ownerId, string $newStatus): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE publications SET publication_status = :status WHERE id = :id AND user_id = :owner_id'
        );
        $stmt->execute(['status' => $newStatus, 'id' => $publicationId, 'owner_id' => $ownerId]);
        return $stmt->rowCount() > 0;
    }

    public function markAsReserved(int $publicationId): void
    {
        $stmt = $this->db->prepare("UPDATE publications SET publication_status = 'En Proceso de Entrega' WHERE id = :id");
        $stmt->execute(['id' => $publicationId]);
    }

    public function markAsAvailable(int $publicationId): void
    {
        $stmt = $this->db->prepare("UPDATE publications SET publication_status = 'Disponible' WHERE id = :id");
        $stmt->execute(['id' => $publicationId]);
    }

    public function markAsClosed(int $publicationId, string $modality): void
    {
        $finalStatus = $modality === 'Donacion' ? 'Donado' : 'Vendido/Intercambiado';
        $stmt = $this->db->prepare('UPDATE publications SET publication_status = :status WHERE id = :id');
        $stmt->execute(['status' => $finalStatus, 'id' => $publicationId]);
    }
}
