# UniSwapt — Prototipo local

Prototipo funcional de UniSwapt, la plataforma de compra, venta, trueque y
donacion de materiales academicos entre estudiantes de un mismo campus,
construido a partir del documento de requerimientos y sus 9 historias de
usuario (HU-01 a HU-09).

## Como ejecutarlo en local

Requisitos: PHP 8.1+ con la extension `pdo_sqlite` (viene activada por
defecto en la mayoria de instalaciones de PHP).

```bash
cd uniswapt
php -S localhost:8000
```

Abre `http://localhost:8000` en tu navegador. La base de datos SQLite
(`database/uniswapt.sqlite`) se crea automaticamente la primera vez que se
ejecuta cualquier pagina, a partir de `database/schema.sql`. No necesitas
instalar MySQL/PostgreSQL ni configurar nada adicional: todo el prototipo
corre localmente en un solo archivo de base de datos.

Para "reiniciar" el prototipo con datos limpios, simplemente borra
`database/uniswapt.sqlite` (se regenerara vacia en la siguiente peticion) y
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
la pantalla "Revisa tu correo" te da un enlace directo para abrirlo. En
produccion, al definir `MAIL_API_KEY`, `php/classes/Mailer.php` envia los
correos reales con la API de Brevo y esa pantalla ya no muestra el enlace
(ver "Despliegue en produccion").

## Despliegue en produccion (Railway + Supabase)

1. **Base de datos:** en Supabase abre *SQL Editor*, pega el contenido de
   `database/schema_postgres.sql` y ejecutalo. Crea todas las tablas,
   restricciones, indices y habilita RLS; ademas registra las migraciones
   existentes en `schema_migrations` para que la app no las vuelva a aplicar.
   (Si la base esta vacia, la app tambien aplica ese esquema sola al arrancar.)
2. **Variables de entorno en Railway:**

| Variable | Obligatoria | Descripcion |
|---|---|---|
| `DATABASE_URL` | Si | Cadena de conexion de Supabase (`postgresql://usuario:clave@host:puerto/postgres`). Railway no tiene IPv6: usa la del *Session pooler* (puerto 5432); el *Transaction pooler* (6543) tambien funciona. Siempre se conecta con `sslmode=require`. |
| `APP_URL` | Si | URL publica de la app, sin barra final (p. ej. `https://uniswapt.up.railway.app`). Se usa en los enlaces de los correos. |
| `MAIL_API_KEY` | Si | API key de Brevo. Si falta, los correos se guardan en `html/emails/` (solo para desarrollo local). |
| `MAIL_FROM_EMAIL` | Si | Remitente verificado en Brevo. |
| `MAIL_FROM_NAME` | No | Nombre del remitente (por defecto `UniSwapt ITESA`). |
| `SUPABASE_URL` | Si | URL del proyecto de Supabase (p. ej. `https://xxxx.supabase.co`). Junto con la siguiente, activa Supabase Storage para las imagenes. |
| `SUPABASE_SERVICE_ROLE_KEY` | Si | Clave *service_role* de Supabase (secreta, solo en el servidor). Se usa para subir las imagenes. Si falta esta o `SUPABASE_URL`, las imagenes se guardan en `assets/uploads/` (solo para desarrollo local: el disco de Railway se borra en cada despliegue). |
| `SUPABASE_STORAGE_BUCKET` | No | Bucket de las imagenes (por defecto `uniswapt-uploads`). Debe existir y ser **publico**; dentro se usan las carpetas `products/` y `profiles/`. |

3. **Extensiones PHP:** `composer.json` declara `pdo_pgsql`, `curl`,
   `mbstring` y `fileinfo`, que el builder de Railway instala a partir de el.

Sin `DATABASE_URL` la app sigue usando SQLite local, como se describe arriba.

## Estructura de carpetas

```
uniswapt/
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
    ├── schema.sql         Definicion de tablas (SQLite local)
    ├── schema_postgres.sql Esquema completo para Supabase/PostgreSQL
    ├── migrations/        Migraciones aplicadas una sola vez al arrancar
    └── uniswapt.sqlite    Base de datos local (se genera sola)
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
| Flujo 04 Notificaciones por correo | `php/classes/Mailer.php` (Brevo en produccion, bandeja simulada en local) |
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
\$db = new PDO('sqlite:database/uniswapt.sqlite');
\$db->exec(\"UPDATE users SET role = 'administrador' WHERE email = 'TU_CORREO@itesa.edu.mx'\");
echo 'Listo.';
"
```

## Notas de diseño

La identidad visual se aleja deliberadamente de un dashboard de SaaS
generico: usa una paleta tipo "tablon de anuncios de campus" (papel kraft,
tinta oscura, acento ambar) para reforzar que UniSwapt es un espacio
informal de intercambio entre estudiantes, no una tienda corporativa.
