#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# create_issues.sh — Registro automatizado del flujo de QA de UniSwapt en GitHub.
#
# Crea en diegocorvar/UniSwapt las 8 incidencias detectadas en la revision
# integral (CONTEXT.md, seccion 11), les asigna sus etiquetas y las cierra con
# un comentario de resolucion que referencia los commits de la rama de
# correcciones.
#
# Requisitos: GitHub CLI (gh) autenticado con permisos sobre el repositorio
#             (gh auth login) y la rama de correcciones publicada en origin.
#
# Uso:
#   ./create_issues.sh             # crea, etiqueta y cierra las incidencias
#   ./create_issues.sh --dry-run   # solo muestra lo que haria
#
# Es idempotente: si una incidencia con el mismo titulo ya existe, no se
# duplica; si sigue abierta, solo se cierra.
# -----------------------------------------------------------------------------
set -euo pipefail

REPO="diegocorvar/UniSwapt"
FIX_BRANCH="fix/qa-integral"
SIGNATURE="— Diego Alonso Coronel Vargas y Angel Gonzalez Ramirez"
DRY_RUN=false

if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
fi

run() {
    if $DRY_RUN; then
        printf '[dry-run]'; printf ' %q' "$@"; echo
    else
        "$@"
    fi
}

# --- Comprobaciones previas --------------------------------------------------
if ! $DRY_RUN; then
    if ! command -v gh >/dev/null 2>&1; then
        echo "Error: GitHub CLI (gh) no esta instalado. Instalalo desde https://cli.github.com/" >&2
        exit 1
    fi

    if ! gh auth status >/dev/null 2>&1; then
        echo "Error: gh no esta autenticado. Ejecuta 'gh auth login' primero." >&2
        exit 1
    fi

    # Los comentarios de cierre enlazan commits: deben existir en GitHub.
    if ! git ls-remote --exit-code --heads origin "$FIX_BRANCH" >/dev/null 2>&1; then
        echo "Error: la rama '$FIX_BRANCH' no esta publicada en origin." >&2
        echo "Publicala primero con: git push -u origin $FIX_BRANCH" >&2
        exit 1
    fi
fi

# --- Etiquetas ----------------------------------------------------------------
ensure_label() {
    local name="$1" color="$2" description="$3"
    run gh label create "$name" --repo "$REPO" --color "$color" --description "$description" --force
}

ensure_label "bug"      "d73a4a" "Algo no funciona como se espera"
ensure_label "security" "b60205" "Vulnerabilidad o endurecimiento de seguridad"
ensure_label "chore"    "c5def5" "Mantenimiento, infraestructura o control de versiones"
ensure_label "logic"    "fbca04" "Error en reglas de negocio o flujo de estados"

# --- Alta y cierre de cada incidencia ----------------------------------------
# process_issue <titulo> <etiquetas separadas por coma> <cuerpo> <resolucion>
process_issue() {
    local title="$1" labels="$2" body="$3" resolution="$4"
    local number="" state=""

    if ! $DRY_RUN; then
        read -r number state < <(
            gh issue list --repo "$REPO" --state all --limit 200 \
                --search "in:title \"$title\"" --json number,title,state \
                --jq ".[] | select(.title == \"$title\") | \"\(.number) \(.state)\"" | head -n 1
        ) || true
    fi

    if [[ -z "$number" ]]; then
        echo "Creando: $title"
        if $DRY_RUN; then
            run gh issue create --repo "$REPO" --title "$title" --label "$labels" --body "$body"
            number="<nuevo>"
        else
            local url
            url=$(gh issue create --repo "$REPO" --title "$title" --label "$labels" --body "$body")
            number="${url##*/}"
            echo "  -> $url"
        fi
    else
        echo "Ya existe #$number ($state): $title"
        if [[ "$state" == "CLOSED" ]]; then
            echo "  -> ya estaba cerrada, se omite."
            return
        fi
    fi

    local comment
    comment="$(printf '%s\n\n%s\n\nRama: `%s`\n\n%s' \
        "**Resuelto.**" "$resolution" "$FIX_BRANCH" "$SIGNATURE")"
    run gh issue close "$number" --repo "$REPO" --reason completed --comment "$comment"
}

# 1 ---------------------------------------------------------------------------
process_issue \
"Control de versiones: falta .gitignore y js/main.js no esta versionado" \
"chore" \
"$(cat <<'EOF'
## Descripcion
- El commit inicial menciona un `.gitignore`, pero no existe. `database/uniswapt.sqlite`, `assets/uploads/` y `html/emails/` aparecen como archivos sin rastrear y podrian subirse por error (datos personales, hashes de contrasenas, tokens).
- `html/partials/footer.php` carga `js/main.js` en todas las paginas, pero el archivo nunca se agrego al repositorio: un clon limpio pierde el autocierre de alertas y el autoenvio de filtros del catalogo.

## Archivos afectados
`.gitignore`, `js/main.js`, `assets/uploads/`, `html/emails/`
EOF
)" \
"- \`fd1df09\` chore(git): agregar .gitignore para artefactos generados (BD SQLite, uploads y bandeja simulada; estructura conservada con \`.gitkeep\`).
- \`131e5e6\` chore(git): versionar js/main.js."

# 2 ---------------------------------------------------------------------------
process_issue \
"Seguridad: formularios POST sin proteccion CSRF" \
"security" \
"$(cat <<'EOF'
## Descripcion
Ningun formulario POST incluye un token anti-CSRF y ninguna action de `php/actions/` lo valida. Un sitio externo puede hacer que un usuario autenticado acepte propuestas, cambie el estado de sus publicaciones, envie reportes o modifique su perfil sin saberlo (la cookie `SameSite=Lax` no cubre todos los escenarios).

## Archivos afectados
`php/includes/session.php`, todas las vistas con `<form method="post">` y las 14 actions de `php/actions/`.
EOF
)" \
"- \`7d62b94\` fix(security): implementar proteccion CSRF en formularios POST.
  - Token por sesion (\`csrfToken()\`, \`csrfField()\`), comparacion con \`hash_equals()\` en \`requireValidCsrfToken()\` y rotacion al iniciar sesion.
  - Campo oculto \`csrf_token\` en todos los formularios y validacion en todas las actions antes de procesar datos."

# 3 ---------------------------------------------------------------------------
process_issue \
"Seguridad: la validacion MIME de subidas confia en el tipo enviado por el cliente" \
"security" \
"$(cat <<'EOF'
## Descripcion
`publish-action.php` y `update-profile-action.php` validan el tipo de imagen con `$_FILES[...]['type']`, un valor que controla el cliente. Se puede subir cualquier archivo declarando `Content-Type: image/png`. El commit `25e3dc5` menciona `finfo_file()`, pero no esta en el codigo.

## Pasos para reproducir
1. Renombrar un archivo PHP como `foto.png`.
2. Enviarlo en el formulario de publicacion con `Content-Type: image/png`.
3. El archivo se guarda en `assets/uploads/products/`.
EOF
)" \
"- \`0dc2e27\` fix(security): validar el tipo MIME real de las imagenes con finfo_file.
  - \`detectUploadedMimeType()\` usa \`finfo_file(FILEINFO_MIME_TYPE)\` sobre el archivo temporal (con \`is_uploaded_file()\`); la extension se deriva del MIME real.
  - Las imagenes se validan antes de crear la publicacion y se informa cuales son invalidas."

# 4 ---------------------------------------------------------------------------
process_issue \
"Seguridad: XSS almacenado en Mailer y validaciones de entrada faltantes" \
"security,bug" \
"$(cat <<'EOF'
## Descripcion
- `Mailer.php` inserta `$fullName` sin escapar en el HTML del correo, que se sirve desde el mismo origen (`html/emails/`): XSS almacenado en la bandeja simulada.
- Los enlaces de los correos se generan con `dirname(SCRIPT_NAME)` dentro de una action, por lo que apuntan a `php/actions/verify.php` (404).
- El formato del correo no se valida con `filter_var`; solo se comprueba el sufijo (`x@evil.com@itesa.edu.mx` pasaba).
- `report-action.php` no comprueba que el `target_id` exista; se podian registrar reportes sobre IDs inexistentes.
EOF
)" \
"- \`270b728\` fix(security): escapar datos dinamicos en las plantillas de Mailer.
- \`412d4aa\` fix(mailer): generar enlaces de correo desde la raiz de la aplicacion.
- \`d2acda2\` fix(security): validar el formato de correo con filter_var (registro, login y recuperacion).
- \`ca3c373\` fix(security): validar que el target_id de un reporte exista."

# 5 ---------------------------------------------------------------------------
process_issue \
"Bug: redireccion rota despues de enviar un reporte" \
"bug" \
"$(cat <<'EOF'
## Descripcion
`report-action.php` redirige a `product.php?id=...` / `profile.php?id=...` sin el prefijo `../../`. Como la action se ejecuta desde `php/actions/`, el navegador termina en `php/actions/product.php` (404).

## Pasos para reproducir
1. Abrir el detalle de una publicacion ajena.
2. Enviar "Reportar esta publicacion".
3. Se muestra un 404.
EOF
)" \
"- \`bd7c4dc\` fix(actions): corregir ruta de redireccion en report-action.php (\`../../product.php\` y \`../../profile.php\`)."

# 6 ---------------------------------------------------------------------------
process_issue \
"Logica: negociacion sin control de estado y propuestas duplicadas" \
"logic,bug" \
"$(cat <<'EOF'
## Descripcion
- `respond-transaction-action.php` no verifica que la transaccion siga en `Propuesta` ni que la publicacion siga `Disponible`. Un vendedor puede aceptar varias propuestas de la misma publicacion o reaceptar una rechazada o completada reenviando el POST.
- Al aceptar una propuesta, las demas propuestas pendientes de la misma publicacion no se rechazan.
- Un mismo comprador puede enviar varias propuestas activas a la misma publicacion.
EOF
)" \
"- \`0a54a5a\` fix(transactions): validar estado al responder propuestas y rechazar las concurrentes (UPDATE condicionados al estado y transaccion de BD).
- \`4456efe\` fix(transactions): impedir propuestas duplicadas del mismo comprador."

# 7 ---------------------------------------------------------------------------
process_issue \
"Logica: cambio manual de estado sin control y reservas que nunca expiran" \
"logic" \
"$(cat <<'EOF'
## Descripcion
- `update-status-action.php` permite al dueno poner cualquier estado aunque haya una transaccion `Aceptada` en curso (por ejemplo, volver a `Disponible` durante una entrega coordinada).
- `RESERVATION_EXPIRATION_HOURS` (48 h) esta definida pero no se usa: una reserva sin confirmar bloquea la publicacion indefinidamente.
EOF
)" \
"- \`f2800fb\` fix(publications): bloquear el cambio manual de estado con una transaccion activa.
- \`126fc11\` feat(transactions): liberar reservas vencidas segun RESERVATION_EXPIRATION_HOURS (migracion 002 \`accepted_at\`, liberacion automatica y aviso en el panel)."

# 8 ---------------------------------------------------------------------------
process_issue \
"Bug: avatar del vendedor ignorado y sin reenvio del correo de verificacion" \
"bug,logic" \
"$(cat <<'EOF'
## Descripcion
- `html/product-detail.php` siempre muestra `default-avatar.svg`, aunque el vendedor tenga foto de perfil.
- Si el token de verificacion expira, la vista sugiere registrarse de nuevo, pero el correo ya existe y el registro lo rechaza: la cuenta queda bloqueada en estado `pendiente`.
EOF
)" \
"- \`5c405cb\` fix(ui): mostrar la foto de perfil real del vendedor en product-detail.
- \`4ee1888\` feat(auth): permitir reenviar el correo de verificacion de cuenta (\`resend-verification.php\` y su action, con invalidacion de tokens anteriores)."

echo
echo "Listo. Incidencias registradas y cerradas en https://github.com/$REPO/issues?q=is%3Aissue"
