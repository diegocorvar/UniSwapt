# UniMarket — Prototipo local

Prototipo funcional de UniMarket, la plataforma de compra, venta, trueque y
donacion de materiales academicos entre estudiantes de un mismo campus,
construido a partir del documento de requerimientos y sus 9 historias de
usuario (HU-01 a HU-09).

## Como ejecutarlo en local

Requisitos: PHP 8.1+ con la extension `pdo_sqlite` (viene activada por
defecto en la mayoria de instalaciones de PHP).

```bash
cd unimarket
php -S localhost:8000
```

Abre `http://localhost:8000` en tu navegador. La base de datos SQLite
(`database/unimarket.sqlite`) se crea automaticamente la primera vez que se
ejecuta cualquier pagina, a partir de `database/schema.sql`. No necesitas
instalar MySQL/PostgreSQL ni configurar nada adicional: todo el prototipo
corre localmente en un solo archivo de base de datos.

Para "reiniciar" el prototipo con datos limpios, simplemente borra
`database/unimarket.sqlite` (se regenerara vacia en la siguiente peticion) y
los archivos dentro de `assets/uploads/*` y `html/emails/*`.

## Sobre el registro/login e inicio de sesion: es real

Aunque es un prototipo local, la autenticacion **no esta simulada**:
- Las contrasenas se guardan con `password_hash()` (bcrypt), nunca en texto plano.
- El inicio de sesion usa sesiones PHP nativas con `session_regenerate_id()`.
- Los tokens de verificacion de cuenta y de restablecimiento de contrasena
  son generados con `random_bytes()`, se guardan en base de datos con fecha
  de expiracion real y se invalidan tras un solo uso.

Lo unico que esta simulado es el **transporte** del correo (no hay un
servidor SMTP conectado en este entorno local). En vez de enviarse a una
bandeja externa, cada "correo" se guarda como un archivo HTML dentro de
`html/emails/` y, para que puedas probar el flujo sin salir del navegador,
la pantalla "Revisa tu correo" te da un enlace directo para abrirlo. En un
despliegue real, la clase `php/classes/Mailer.php` es el unico archivo que
tendrias que modificar para conectar un proveedor real (SendGrid, SMTP,
etc.) — toda la logica de generacion/expiracion de tokens seguiria igual.

## Estructura de carpetas

```
unimarket/
├── index.php, login.php, register.php, ...   Controladores (uno por pagina)
├── assets/
│   ├── images/            Imagenes propias del sitio (logo, avatar por defecto)
│   └── uploads/           Imagenes subidas por los usuarios (productos y perfiles)
├── css/                   Hojas de estilo (variables, base, componentes, paginas)
├── js/                    Comportamiento del lado del cliente (validaciones,
│                          subida de imagenes, selector de estrellas)
├── html/
│   ├── partials/          header.php y footer.php reutilizados en cada pagina
│   ├── emails/            Bandeja simulada de correos (Flujo 04)
│   └── *.php              Vistas (solo HTML + variables ya calculadas)
├── php/
│   ├── config/            Constantes de negocio y conexion a la base de datos
│   ├── classes/           Modelos: User, Publication, Transaction, Rating, Mailer
│   ├── includes/          bootstrap.php, session.php, helpers.php
│   └── actions/           Procesan cada formulario (POST) y redirigen
└── database/
    ├── schema.sql         Definicion de tablas
    └── unimarket.sqlite   Base de datos (se genera sola)
```

Cada pagina en la raiz sigue el mismo patron: obtiene los datos necesarios
usando las clases de `php/classes/`, y despues incluye `html/partials/header.php`
+ la vista correspondiente en `html/` + `html/partials/footer.php`. Los
formularios envian sus datos a `php/actions/`, que validan, actualizan la
base de datos y redirigen de regreso con un mensaje flash.

## Cobertura de requerimientos

| Historia de Usuario | Donde esta implementada |
|---|---|
| HU-01 Registro con correo institucional | `register.php`, `php/actions/register-action.php` |
| HU-02 Verificacion de cuenta | `verify.php`, tokens en tabla `tokens` |
| HU-03 Inicio de sesion seguro | `login.php`, `php/actions/login-action.php` |
| HU-04 Perfil y rol academico | `profile.php`, `php/actions/update-profile-action.php` |
| HU-05 Restablecimiento de contrasena | `forgot-password.php`, `reset-password.php` |
| HU-06 Publicacion de materiales | `publish.php`, `php/actions/publish-action.php` |
| HU-07 Galeria de imagenes | `js/image-upload.js`, tabla `publication_images` |
| HU-08 Filtros por categoria/carrera | `index.php`, `Publication::search()` |
| HU-09 Valoracion y reputacion | `rate.php`, tabla `ratings`, `User::recalculateReputation()` |
| Flujo 01 Negociacion | `php/classes/Transaction.php`, pestaña "Propuestas" en `dashboard.php` |
| Flujo 02 Entrega y cierre | `php/actions/confirm-delivery-action.php` |
| Flujo 03 Reputacion y moderacion | `rate.php`, `php/actions/report-action.php`, `admin.php` |
| Flujo 04 Notificaciones por correo | `php/classes/Mailer.php` (simulado, ver seccion anterior) |
| Flujo 06-A Contacto por WhatsApp | `buildWhatsAppLink()` en `php/includes/helpers.php` |

Los Flujos 05 (push/WebSockets) y 06-B (Cloudinary/S3) descritos en el
documento requieren servicios de terceros en la nube; quedan fuera de este
prototipo local por diseño, pero la arquitectura ya aisla la subida de
imagenes en `Publication::addImage()`, por lo que mas adelante seria
sencillo sustituir el guardado local por una llamada a un proveedor externo.

## Para probar el panel de moderacion (rol Administrador)

Por default, todo usuario nuevo se registra con el rol `estudiante`. Para
probar `admin.php`, registra una cuenta normalmente, verificala, y luego
promuevela manualmente ejecutando esto una sola vez:

```bash
php -r "
\$db = new PDO('sqlite:database/unimarket.sqlite');
\$db->exec(\"UPDATE users SET role = 'administrador' WHERE email = 'TU_CORREO@itesa.edu.mx'\");
echo 'Listo.';
"
```

## Notas de diseño

La identidad visual se aleja deliberadamente de un dashboard de SaaS
generico: usa una paleta tipo "tablon de anuncios de campus" (papel kraft,
tinta oscura, acento ambar) para reforzar que UniMarket es un espacio
informal de intercambio entre estudiantes, no una tienda corporativa.
