# local_profilephoto — Diseño técnico (Fase 1)

Plugin local para Moodle **5.1.0+** (probado contra la rama `MOODLE_501_STABLE`,
verificada en GitHub el 2026-07-25). Nombre Frankenstyle: `local_profilephoto`.
Carpeta de despliegue: `public/local/profilephoto/` (Moodle ≥5.0 movió el
docroot servible a `public/`; el contenido del plugin en sí no cambia, solo
dónde lo copia el instalador).

> Este documento se entrega como Fase 1 y se actualiza en cada entrega. El
> desarrollo se realiza de forma iterativa (sección 33 del encargo): esta
> primera iteración de código cubre la **Entrega 1** — esqueleto instalable,
> permisos, búsqueda AJAX, actualización real de la foto de perfil vía la API
> oficial de Moodle y una captura manual de prueba (subida de archivo) para
> validar el flujo completo sin depender todavía de la cámara. Las entregas
> 2-5 (cámara en vivo, colas/sesiones, exportación ZIP, batería de pruebas)
> se construyen sobre esta base sin romper la API pública ya fijada aquí.

## 1. Resumen de la solución

Una pantalla operativa (`index.php`) sustituye el flujo de edición de perfil
estándar de Moodle por una experiencia de "sesión de fotografía": buscar/
seleccionar alumno → capturar → confirmar → guardar y pasar al siguiente, sin
recargas de página. Toda la lógica de negocio vive en clases PHP con
autoload PSR-4 (`classes/`), expuesta al cliente mediante la External
Functions API (`core/ajax`), y la interfaz se construye con Mustache + AMD
JS (ES6, sin jQuery ni frameworks externos), tal como exige el encargo.

## 2. Arquitectura propuesta

```
Navegador (AMD JS, Mustache)
   │  core/ajax  (sesskey + login + capability checks en cada llamada)
   ▼
classes/external/*  (External API: parámetros tipados, execute_returns)
   │
   ▼
classes/local/*     (lógica de negocio pura, sin acoplar a HTTP/AJAX)
   ├── access/scope.php     → qué alumnos puede ver/editar el operador
   ├── search/user_search.php → búsqueda indexada y priorizada
   ├── image/processor.php  → validación/saneado de la imagen recibida
   └── image/updater.php    → puente hacia la API oficial de Moodle
   │
   ▼
\core\user::update_picture()   ← API pública de Moodle (ver sección 4)
```

Separar `classes/external/*` (capa de transporte) de `classes/local/*`
(lógica) permite reutilizar la lógica desde PHPUnit sin pasar por el bus de
AJAX, y evita tests que dependan de sesiones HTTP simuladas.

## 3. Flujo UX (resumen operativo)

```
Seleccionar cohorte/grupo/curso  →  cola ordenada de alumnos pendientes
        o
Buscar alumno (2-3 car., debounce 250 ms, AJAX)
        │
        ▼
Seleccionar alumno  →  ficha alumno visible (nombre, foto actual, curso, aviso "ya tiene foto")
        │
        ▼
Hacer foto (Espacio / botón grande)  →  previsualización
        │
   ┌────┴────┐
Repetir (R)   Guardar y siguiente (Enter)
   │              │
   ▼              ▼
vuelve a cámara   guarda con \core\user::update_picture(), registra evento,
                  muestra "Fotografía guardada para <nombre>" y carga
                  automáticamente el siguiente alumno pendiente de la cola
```

En Entrega 1 el "hacer foto" se resuelve con una entrada de archivo (para
poder probar el guardado real contra Moodle sin depender de una cámara);
en Entrega 2 se sustituye por `getUserMedia` sin cambiar el contrato del
external function `save_picture` (que ya recibe un blob de imagen, no un
`<input type=file>` concreto).

## 4. Método exacto para actualizar la fotografía oficial (crítico — prioridad 1)

Investigación contra el código fuente real de `MOODLE_501_STABLE`
(github.com/moodle/moodle, confirmado con fecha de build 2026-07-22, versión
`2025100605.06`, coherente con la fecha actual):

* El plugin **nunca** escribe directamente en `mdl_user.picture` ni en
  `moodledata`. Usa exclusivamente `\core\user::update_picture()`
  (`public/lib/classes/user.php`), que es la misma función que invoca
  `user/editadvanced.php` al guardar el formulario de edición avanzada de
  usuario. Existe un alias de compatibilidad
  `class_alias(user::class, \core_user::class)` en el mismo archivo, pero
  usamos el nombre moderno `\core\user::update_picture()`.
* Firma real:
  `update_picture(stdClass $usernew, $filemanageroptions = [])`, donde
  `$usernew->id` es el usuario destino y `$usernew->imagefile` es el
  **itemid de un área de borrador** (`draft`) que contiene exactamente un
  archivo de imagen.
* Internamente la función llama a
  `file_save_draft_area_files($usernew->imagefile, $context->id, 'user', 'newicon', 0, $filemanageroptions)`,
  localiza el archivo subido, lo copia a un temporal y lo pasa a
  `process_new_icon($context, 'user', 'icon', 0, $iconfile)`
  (`public/lib/gdlib.php`), que genera las variantes oficiales `f1`
  (100×100), `f2` (35×35) y `f3` (512×512) en el área de ficheros del
  usuario y devuelve el nuevo id de "picture". Por último hace
  `$DB->set_field('user', 'picture', $newpicture, ...)`.
* **Por qué no hace falta purgar caché a mano**: el id devuelto por
  `process_new_icon` se incrusta en la propia URL de `pluginfile.php` que
  genera `user_picture`. Al cambiar `picture` cambia la URL, así que
  navegador/perfil/participantes/grupos muestran la imagen nueva de
  inmediato sin intervención adicional — cumple el criterio de aceptación
  10 y 12 del encargo.
* Nuestro código (`classes/local/image/updater.php`) reproduce exactamente
  lo que hace el formulario estándar, pero de forma programática:
  1. Crea un itemid de borrador nuevo (`file_get_unused_draft_itemid()`).
  2. Guarda el JPEG ya validado/saneado como archivo único en el área
     `user`/`draft` del **contexto del operador** (igual que hace el
     `filemanager` del formulario al subir un archivo).
  3. Llama a `\core\user::update_picture((object)['id' => $targetuserid,
     'imagefile' => $draftitemid], $filemanageroptions)` con las mismas
     opciones (`maxbytes`, `accepted_types => 'web_image'`, `maxfiles => 1`)
     que usa `user/editadvanced_form.php`.
  4. Comprueba el valor de retorno (`true`/`false`) y sólo entonces
     registra el evento `picture_updated` y el resultado en auditoría.
* Nota de calidad: `process_new_icon` produce un `f3` de 512×512. La
  resolución final configurable del plugin (por defecto 500×500 según el
  encargo) es ligeramente menor; se documenta como advertencia en
  `settings.php` recomendando ≥512 px si se prioriza nitidez, dejando el
  valor por defecto tal como lo pide el encargo pero configurable.
* Antes de llamar a `update_picture()` se repite la comprobación de
  capacidades sobre el contexto del **alumno destino** (nunca basta con
  haber superado el chequeo al abrir la sesión), tal como exige la sección
  20 del encargo.

## 5. Modelo de permisos

Capacidades (todas `CONTEXT_SYSTEM` en Entrega 1; se añadirá scope por
contexto de curso/categoría cuando se implementen colas por curso en la
Entrega 3, sin romper compatibilidad):

`local/profilephoto:view`, `:searchusers`, `:capture`, `:updatepicture`,
`:replaceexisting`, `:viewidentifiers`, `:viewallusers`, `:exportsession`,
`:exportall`, `:managesessions`, `:configure`, `:viewlogs`,
`:restoreprevious`.

El **ámbito de usuarios visibles** (sección 17) no se resuelve solo con
capacidades: `classes/local/access/scope.php` intersecta:

1. Capacidad `local/profilephoto:viewallusers` (si la tiene, sin más
   restricción) o, si no la tiene, el conjunto de cursos donde el operador
   tiene asignado un rol con `local/profilephoto:capture`.
2. Configuración global de ámbito (`todo el sistema` / `cursos con acceso`
   / `cohortes autorizadas`), leída de `settings.php`.
3. Estado del usuario destino: se excluyen siempre usuarios eliminados
   (`deleted = 1`) y, salvo `viewallusers`, también los suspendidos.

Nunca se usa `is_siteadmin()` como control de acceso; solo se usa como
atajo de conveniencia en checks explícitos de capacidad ya superados.

## 6. Modelo de datos

Entrega 1 **no crea tablas propias**: no hay todavía nada que persistir más
allá de lo que ya gestiona Moodle (usuarios, contexto, archivos). Crear
tablas para sesiones/colas antes de implementarlas violaría el principio de
no diseñar para requisitos hipotéticos. Las tablas `local_profilephoto_session`,
`local_profilephoto_session_user` y `local_profilephoto_log` descritas en la
sección 25 del encargo se añaden en la Entrega 3 (colas y sesiones) mediante
`db/install.xml` + `db/upgrade.php`, exactamente con los campos ahí
especificados. `db/access.php` ya declara el conjunto completo de
capacidades desde ahora porque es un manifiesto, no lógica viva.

## 7. Riesgos técnicos

* **API interna cambia de nombre entre ramas** (`core_user` → `core\user`
  en 5.x): mitigado usando siempre el nombre moderno y comprobando en cada
  entrega contra la rama estable objetivo antes de publicar.
* **`process_new_icon` reescala a 512×512/100×100/35×35 con GD**: si el
  servidor no tiene GD con soporte PNG/JPEG, la función falla; se comprueba
  el valor de retorno y se informa un error explícito al operador en lugar
  de dar un falso éxito.
* **Doble envío / doble clic**: mitigado con deshabilitado temporal del
  botón + `sesskey` + un identificador de operación idempotente comprobado
  en servidor (Entrega 2).
* **Ámbito de usuarios mal configurado** podría exponer alumnos fuera de
  la responsabilidad del operador: se cubre con tests PHPUnit dedicados a
  `scope.php` antes de dar por cerrada cualquier entrega que lo toque.
* **Rendimiento de búsqueda con >10k usuarios**: se evita cargar usuarios en
  JS; toda la búsqueda es SQL paginado con `LIKE` sobre campos indexables y
  coincidencia exacta priorizada primero (ver `user_search.php`).
* Este documento se basa en la rama pública `MOODLE_501_STABLE` tal como
  estaba en GitHub el 2026-07-25; antes de publicar en producción debe
  revalidarse contra el código realmente instalado, por si un parche de
  seguridad posterior cambia la firma de `update_picture()`.

## 8. Estructura de archivos (Entrega 1)

```
local_profilephoto/
├── amd/src/search.js
├── classes/
│   ├── external/{search_users,get_user,save_picture}.php
│   ├── local/access/scope.php
│   ├── local/image/{processor,updater}.php
│   ├── local/search/user_search.php
│   ├── event/picture_updated.php
│   └── privacy/provider.php
├── db/{access,services,upgrade}.php
├── lang/{en,es,ca}/local_profilephoto.php
├── pix/icon.svg
├── templates/{search_results,user_card}.mustache
├── index.php
├── lib.php
├── settings.php
├── styles.css
├── version.php
├── README.md
└── INSTALL.md
```

## 9. Fases de implementación

Fase 1 (este documento) → Fase 2/3/4 parcial = **Entrega 1** (este commit) →
Entrega 2 (cámara real, atajos, doble-envío) → Entrega 3 (cohortes/grupos,
colas, sesiones, auditoría persistente) → Entrega 4 (exportación ZIP) →
Entrega 5 (PHPUnit/Behat completos, revisión de seguridad y accesibilidad,
paquete final).

## 10. Criterios de prueba de esta entrega

* Instalación limpia en Moodle 5.1.x sin errores ni warnings de upgrade.
* Un usuario con `local/profilephoto:capture` pero sin `:viewallusers` solo
  ve alumnos dentro de su ámbito configurado.
* La búsqueda devuelve resultados exactos de `idnumber`/correo/username
  antes que coincidencias parciales.
* Subir una imagen de prueba mediante el flujo manual actualiza
  efectivamente `mdl_user.picture`, y la nueva foto se ve en
  `user/profile.php`, `user/index.php` (participantes) y la página de
  grupos sin purgar caché a mano.
* Un usuario sin `local/profilephoto:updatepicture` recibe
  `require_capability` y la foto no cambia, aunque manipule el `userid` en
  la petición AJAX.
