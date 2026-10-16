<?php
/**
 * Constantes globales de UniMarket.
 * Centraliza reglas de negocio para evitar "numeros magicos" repetidos en el codigo.
 */

// RF-01: dominio institucional permitido para el registro.
define('INSTITUTIONAL_EMAIL_DOMAIN', '@itesa.edu.mx');

// HU-02: vigencia del token de verificacion de cuenta (en horas).
define('VERIFICATION_TOKEN_TTL_HOURS', 24);

// HU-05: vigencia del token de restablecimiento de contrasena (en horas).
define('PASSWORD_RESET_TTL_HOURS', 1);

// RF-08 / HU-07: reglas de imagenes por publicacion.
define('MAX_IMAGES_PER_PUBLICATION', 5);
define('MAX_IMAGE_SIZE_BYTES', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// HU-04: reglas de la foto de perfil.
define('MAX_PROFILE_PHOTO_SIZE_BYTES', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_PROFILE_PHOTO_MIME_TYPES', ['image/jpeg', 'image/png']);

// Flujo 02: horas antes de liberar automaticamente una reserva sin confirmar.
define('RESERVATION_EXPIRATION_HOURS', 48);

// Catalogo institucional de carreras (RF-09 / HU-04).
define('ACADEMIC_CAREERS', [
    'Ingenieria en Sistemas Computacionales',
    'Ingenieria en Mecatronica',
    'Ingenieria en Industrias Alimentarias',
    'Ingenieria Industrial',
    'Ingenieria en Gestion Empresarial',
]);

// Categorias de material academico (RF-09).
define('MATERIAL_CATEGORIES', [
    'Libros y apuntes',
    'Componentes electronicos',
    'Herramientas',
    'Material de laboratorio',
    'Equipo de computo',
    'Uniformes y equipo de seguridad',
    'Otros',
]);

define('ITEM_CONDITIONS', ['Nuevo', 'Usado - Buen Estado', 'Usado - Detalle']);
define('TRANSACTION_MODALITIES', ['Venta', 'Trueque', 'Donacion']);

define('PUBLICATION_STATUSES', [
    'Disponible',
    'En Proceso de Entrega',
    'Vendido/Intercambiado',
    'Donado',
    'Inactivo',
]);

// Rutas de almacenamiento de archivos subidos por el usuario.
define('UPLOAD_DIR_PRODUCTS', __DIR__ . '/../../assets/uploads/products/');
define('UPLOAD_DIR_PROFILES', __DIR__ . '/../../assets/uploads/profiles/');
define('UPLOAD_URL_PRODUCTS', 'assets/uploads/products/');
define('UPLOAD_URL_PROFILES', 'assets/uploads/profiles/');

// Bandeja de correo simulada (Flujo 04): en este prototipo local no existe
// un servidor SMTP real, asi que los "correos" se guardan aqui para poder
// revisarlos como si fuera la bandeja de entrada del usuario.
define('SIMULATED_INBOX_DIR', __DIR__ . '/../../html/emails/');
