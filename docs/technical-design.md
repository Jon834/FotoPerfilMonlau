# local_profilephoto — Diseño técnico (Fase 1)

Plugin local para Moodle **5.1.0+** (probado contra la rama `MOODLE_501_STABLE`,
verificada en GitHub el 2026-07-25). Nombre Frankenstyle: `local_profilephoto`.
Carpeta de despliegue: `public/local/profilephoto/` (Moodle ≥5.0 movió el
docroot servible a `public/`; el contenido del plugin en sí no cambia, solo
dónde lo copia el instalador).

> Este documento se entrega como Fase 1 y se actualiza en cada entrega. El
> desarrollo se realiza de forma iterativa (sección 33 del encargo).
> **Entrega 1** (esqueleto instalable, permisos, búsqueda AJAX, actualización
> real de la foto de perfil, captura manual de prueba), **Entrega 2** (cámara
> en directo, atajos de teclado, doble-envío) y **Entregas 3+4+5** (colas y
> sesiones por curso/cohorte, exportación ZIP, Privacy API completa, pruebas
> automatizadas y documentación de cierre — combinadas en una sola entrega a
> petición explícita, ver sección 12) ya están cubiertas por el código de
> este repositorio.

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

En Entrega 1 el "hacer foto" se resolvió con una entrada de archivo (para
poder probar el guardado real contra Moodle sin depender de una cámara).
En Entrega 2 (este commit) se sustituye por `getUserMedia` como camino
principal, sin cambiar el contrato del external function `save_picture`
(que ya recibía un blob de imagen en base64, no un `<input type=file>`
concreto) — la entrada de archivo se conserva como *fallback* automático,
mostrada solo cuando el navegador o el contexto no admiten cámara.

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

## 8. Estructura de archivos

```
local_profilephoto/
├── amd/
│   ├── src/{search,camera,shortcuts,capture}.js
│   └── build/{search,camera,shortcuts,capture}.min.js(.map)
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

Fase 1 (este documento) → Fase 2/3/4 parcial = **Entrega 1** → **Entrega 2**
(cámara real, atajos, doble-envío) → Entrega 3 (cohortes/grupos, colas,
sesiones, auditoría persistente) → Entrega 4 (exportación ZIP) → Entrega 5
(PHPUnit/Behat completos, revisión de seguridad y accesibilidad, paquete
final).

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

## 11. Entrega 2 — cámara en directo, atajos, doble-envío

### 11.1. Arquitectura JS

Se divide la lógica de cliente en cuatro módulos AMD, cada uno con una
responsabilidad única, en lugar de un único script monolítico:

```
local_profilephoto/search     → buscar y elegir un alumno (AJAX; sin noción de cámara ni de guardado)
local_profilephoto/camera     → ciclo de vida de getUserMedia, listado de dispositivos, captura a Blob
local_profilephoto/shortcuts  → atajos de teclado configurables, ignora campos de texto
local_profilephoto/capture    → orquestador: es lo que carga index.php; conecta los tres anteriores
                                 con save_picture y con la regla "nunca asignar la foto al alumno
                                 equivocado" (ver 11.3)
```

`capture.js` sustituye a `search.js` como módulo que llama `index.php`
(`js_call_amd('local_profilephoto/capture', 'init', [...])`); `search.js`
pasa a exponer solo `init(onSelect)` y ya no conoce nada de cámaras ni de
guardado, lo que permite probarlo por separado.

### 11.2. Captura con cámara (encargo sección 7)

* `navigator.mediaDevices.getUserMedia({video: {...}, audio: false})` -
  nunca se pide audio.
* Requiere `window.isSecureContext` (HTTPS, o `localhost`); si no se
  cumple, o si `getUserMedia` no existe, la pantalla pasa automáticamente
  al formulario de subida manual (Entrega 1) sin que el operador tenga que
  hacer nada - `local_profilephoto/camera` expone `isSupported()` para esta
  comprobación previa.
* Si `getUserMedia` existe pero la llamada real falla (permiso denegado,
  sin cámara, cámara en uso), el mismo fallback manual se activa de forma
  permanente para el resto de la sesión (no tiene sentido reintentar la UI
  de cámara si ya falló una vez) - ver el flag `useCameraUi` en
  `capture.js`.
* Resolución solicitada: `{ideal: 1280}` en ambos ejes (dentro del rango
  720-1000+ recomendado por el encargo). La captura se recorta a cuadrado
  centrado en el propio `<canvas>` del cliente (coherente con la guía
  visual superpuesta al vídeo) y se limita a 1600 px de lado para no
  disparar el tamaño del payload; el recorte/compresión *definitivos* los
  sigue haciendo el servidor (`processor.php`), este recorte del cliente
  es solo para que la previsualización coincida con lo que se guardará.
* La cámara elegida se recuerda en `localStorage` (no es un dato personal
  del alumno, es una preferencia de dispositivo del operador en su propio
  navegador, por lo que no aplica la Privacy API de Moodle).
* El stream se libera (`stop()`) en `beforeunload` y `pagehide`, y también
  antes de arrancar uno nuevo (cambio de cámara), para no mantener varias
  cámaras abiertas simultáneamente.
* No se implementa detección facial ni ningún procesamiento biométrico
  (encargo sección 8): el guía visual sobre el vídeo es puramente
  decorativo (CSS), no hay análisis de la imagen en el cliente.

### 11.3. Prevención de asignación incorrecta (prioridad 2 del encargo)

Si el operador ha capturado una foto (todavía sin guardar) y busca/selecciona
a un alumno **distinto** antes de pulsar "Guardar y siguiente", la captura
pendiente se descarta automáticamente (`discardCapture()` se llama desde el
callback `onSelect` de `search.js`). Así una foto nunca puede terminar
guardada contra el alumno equivocado por un cambio de selección a medio
capturar. Si se produce un error al guardar, la pantalla se queda en modo
previsualización (no avanza automáticamente), tal como exige la sección 13.

### 11.4. Atajos de teclado (encargo sección 6)

Implementados: `Espacio` (hacer foto), `Enter` (guardar y siguiente), `R`
(repetir), `B` (ir al buscador), `Esc` (cancelar previsualización) y, desde
que existe una cola real (sección 12), `S` (saltar al siguiente pendiente
dentro de una sesión). `ArrowLeft`/`ArrowRight` (alumno anterior/siguiente,
en el sentido de "navegar libremente por toda la cola") **no** están
implementados: la cola solo ofrece avance automático al siguiente
pendiente más las acciones explícitas saltar/ausente, no una lista
completa navegable con un cursor de posición - ver sección 12.7 para el
detalle de esta limitación consciente. Un disparador USB que emule
Espacio o Enter funciona automáticamente, sin código adicional, siempre
que el foco no esté en un campo de texto (comprobado en `shortcuts.js`).
Activable/desactivable por configuración (`local_profilephoto/enableshortcuts`).

### 11.5. Doble-envío

Se mantiene el identificador de operación (`operationid`) más el
deshabilitado del botón "Guardar y siguiente" introducidos en la Entrega 1
(`classes/external/save_picture.php`, caché de sesión `recentsaves`); no
ha hecho falta ningún cambio de servidor para la Entrega 2, ya que el
contrato de `save_picture` no cambió (sigue recibiendo un blob en base64,
ahora procedente de `canvas.toBlob()` en vez de un `<input type=file>`).

### 11.6. Lección operativa: por qué hacen falta los `.js.map`

Durante las pruebas en el sitio real del centro, la búsqueda dejó de
funcionar con `Uncaught SyntaxError: Cannot use import statement outside a
module` en `search.js`. La causa, confirmada leyendo el código real de
`public/lib/requirejs.php` en `MOODLE_501_STABLE`: cuando `$CFG->cachejs`
está desactivado (típico en un sitio de desarrollo), Moodle sirve los
módulos AMD uno a uno y, para cada uno, comprueba si existe
`amd/build/<módulo>.min.js.map`. Si no existe, asume que es "código fuente
antiguo de un plugin" (pre-ES-modules) y sirve `amd/src/<módulo>.js` tal
cual, sin transpilar - lo cual rompe cualquier módulo escrito con
`import`/`export`. Por eso **todo** módulo de este plugin se distribuye
con su `.map` correspondiente (con `mappings` vacío: solo hace falta que
el archivo exista, Moodle no valida su contenido en esta ruta). Ver
INSTALL.md para el detalle completo y cómo regenerarlos con `grunt amd`
cuando haya toolchain de Node disponible.

### 11.7. Riesgos añadidos en esta entrega

* **Compatibilidad de navegador**: `getUserMedia`, `canvas.toBlob` y
  `mediaDevices.enumerateDevices` están ampliamente soportados en
  navegadores modernos, pero no en IE11 ni WebViews antiguos - el
  fallback manual cubre ese caso sin bloquear la operación.
* **Etiquetas de dispositivo vacías** antes de conceder permiso por
  primera vez: `camera.js` gestiona esto mostrando un texto de respaldo
  (`Cámara 1`, `Cámara 2`...) en el selector.
* **Reproducción automática bloqueada por el navegador**: el `<video>` se
  marca `muted` y `playsinline` precisamente para maximizar que
  `video.play()` no sea bloqueado por las políticas de autoplay.

## 12. Entregas 3+4+5 combinadas — colas/sesiones, exportación y cierre

Entregadas juntas a petición explícita ("pasa directamente a la entrega 5,
haciendo los pasos de la 3 y 4 pero haz solo una entrega"). El alcance es
el de las tres fases, pero con los recortes conscientes que se detallan en
12.7 - preferible a simular una profundidad que no da tiempo a construir
de verdad.

### 12.1. Modelo de datos (por fin necesario)

Ahora sí se crean las tablas descritas en la sección 25 del encargo
(`db/install.xml` + `db/upgrade.php`, savepoint `2026072700`):

* `local_profilephoto_session` - una sesión = un operador + un filtro
  (`course` o `cohort`) + un orden. `filterdata` guarda el id filtrado
  como JSON (`{"courseid":5}`) en vez de columnas separadas por tipo de
  filtro, para no tener que ampliar el esquema cada vez que se añada un
  tipo de filtro nuevo.
* `local_profilephoto_session_user` - una fila por alumno en la cola,
  con `status` (`pending|captured|skipped|absent|error`), `sortorder` y
  quién/cuándo la capturó. Índice único `(sessionid, userid)` y
  `(sessionid, status, sortorder)` para que "siguiente pendiente" sea una
  consulta indexada, no un escaneo.
* `local_profilephoto_log` - auditoría. `sessionid` es opcional (una
  exportación no pertenece necesariamente a una sesión concreta).

`classes/local/session/manager.php` concentra toda la lógica: construir
la cola respetando el ámbito del operador (`scope::can_use_course()` para
csos por curso - un único chequeo cubre a todos los matriculados, en vez
de comprobar usuario a usuario; cohortes sí necesitan chequeo por alumno,
porque una cohorte puede abarcar varios cursos), ordenarla
(`lastname|firstname|email|idnumber|username`), avanzar, saltar, marcar
ausente y auto-completar la sesión cuando no queda nada pendiente.

### 12.2. Cola en la pantalla de captura

`amd/src/queue.js` añade un panel de configuración de sesión (curso o
cohorte + orden) encima del buscador existente, y un panel de progreso
(`X/Y capturados, Z pendientes`) que lo sustituye una vez iniciada. La
búsqueda manual sigue funcionando en paralelo dentro de una sesión activa
(para saltarse el orden y localizar a alguien concreto), y ambos caminos
convergen en la misma función `selectUser()` de `capture.js`, que sigue
aplicando la regla de la Entrega 2: cualquier captura pendiente sin
guardar se descarta al cambiar de alumno, sea cual sea el origen de la
selección.

Al guardar con éxito dentro de una sesión, `save_picture` recibe el
`sessionid` y marca la entrada como `captured`; `capture.js` entonces pide
el siguiente pendiente automáticamente (`queueHandle.loadNext()`) en vez
de volver a un estado "sin selección" - así se cumple el flujo
"foto → guardar → carga el siguiente" sin que el operador tenga que volver
a tocar el buscador.

### 12.3. Auditoría y eventos

`classes/local/audit/logger.php` escribe en `local_profilephoto_log` desde
`save_picture.php` (éxito y error), `create_session.php`,
`update_queue_item.php` y `create_export.php`/`export.php`. Nunca se
registra la imagen, base64, tokens ni contraseñas (encargo sección 18) -
solo identificadores, la acción y un mensaje corto.

Eventos nuevos: `session_started`, `session_completed` (se dispara solo
internamente al auto-completarse, sin `trigger()` explícito adicional -
ver nota más abajo), `export_created`, `export_downloaded`. El evento
`picture_updated` de la Entrega 1 ahora lleva `other.replaced` (booleano)
en vez de duplicarse en una clase `picture_replaced` separada: una única
clase de evento con un dato adicional es más simple que dos clases casi
idénticas, y sigue permitiendo diferenciar "primera foto" de "sustitución"
para quien consuma el log de eventos. **No implementados**: `picture_restored`
(no existe función de restaurar, ver 12.7).

### 12.4. Exportación ZIP (encargo sección 15)

`classes/local/export/filename_strategy.php` construye el nombre de cada
archivo reutilizando el saneador `PARAM_FILE` de Moodle (en vez de un
regex propio) y desambigua duplicados como `nombre_2.jpg`, `nombre_3.jpg`.
`classes/local/export/zip_builder.php` construye el ZIP leyendo
directamente el archivo `f3.jpg`/`f3.png` que `process_new_icon()` generó
en su momento (nunca una copia propia del plugin) y añade `manifest.csv`
con las columnas exactas del encargo.

El flujo de descarga es de un solo uso y sin URL predecible: `create_export`
guarda el ZIP en un directorio temporal con nombre aleatorio
(`bin2hex(random_bytes(16))`), registra un token igualmente aleatorio (20
bytes) en una caché de sesión (`local_profilephoto/exports`) que asocia
`token → {ruta, operatorid, nombre de descarga}`, y `export.php?token=...`
lo consume una única vez (`$cache->delete($token)` antes de servir el
archivo) llamando a `send_temp_file()`, que Moodle ya se encarga de
transmitir y borrar. Un `scheduled_task` (`cleanup_exports`, cada 15
minutos) barre el directorio temporal por si un ZIP se generó y nunca se
descargó.

**Simplificación consciente**: la exportación es siempre síncrona. En vez
de construir una tarea ad-hoc con sondeo de estado (`prepare_export` +
`get_export_status` de la sección 24 del encargo), se limita el número de
alumnos por exportación (`local_profilephoto/maxsyncexportusers`, 300 por
defecto) y se pide acotar el filtro si se supera. Es una degradación
razonable para el volumen objetivo (encargo sección 28: "exportación de
500 fotografías"), pero no es lo mismo que una tarea en segundo plano
para volúmenes mayores - ver 12.7.

### 12.5. Ámbito de curso/cohorte

`get_session_options.php` y `get_export_options.php` listan cursos vía
`scope::get_allowed_courseids()` (o los cursos del propio operador,
`enrol_get_users_courses()`, si tiene `viewallusers`) y cohortes **solo**
si el operador tiene `viewallusers` - el modelo de ámbito de la Entrega 1
no tiene noción de "cohortes autorizadas" por operador (encargo sección 17
lo lista como una posible configuración de ámbito, pero implementarla
habría exigido una tabla de asignación cohorte↔operador que no existía y
que no ha dado tiempo a diseñar con cuidado en esta entrega combinada) -
ver 12.7.

### 12.6. Privacy API completa

Ahora que existen tablas propias con datos personales,
`classes/privacy/provider.php` deja de ser un `null_provider` y pasa a
implementar `metadata\provider`, `request\plugin\provider` y
`core_userlist_provider`. Todo el dato vive en `CONTEXT_SYSTEM` (el plugin
no tiene almacenamiento por curso), lo que simplifica
`get_contexts_for_userid()` a un único `SELECT :contextid ... WHERE
EXISTS (...)`. `delete_data_for_user()` borra la cola donde el usuario es
alumno, sus propias sesiones como operador (y la cola/log asociados a
ellas), y las filas de log donde aparece como operador o como alumno.

### 12.7. Recortes conscientes de esta entrega combinada

Documentados aquí en vez de simulados, para que quien retome el proyecto
sepa exactamente qué falta:

* **Sin "deshacer" / copia temporal de la foto anterior** (Opción B de la
  sección 14 del encargo). Solo existe la Opción A (sustitución directa).
  No hay evento `picture_restored`, capacidad `restoreprevious` sin uso
  real, ni configuración de retención de copias.
* **Sin navegación libre "alumno anterior/siguiente" por toda la cola**:
  solo avance automático al siguiente pendiente, más saltar/ausente. No
  hay una lista completa de la cola clicable en pantalla, así que
  `ArrowLeft` no está vinculado (ver 11.4).
* **Exportación siempre síncrona**, sin tarea ad-hoc en segundo plano ni
  `get_export_status`; ver 12.4.
* **Ámbito de cohortes solo para operadores con `viewallusers`**, no hay
  "cohortes autorizadas" por operador; ver 12.5.
* **QR/código de barras** (encargo sección 4.3): no implementado. Es
  priority 9 ("funciones avanzadas") en la propia lista de prioridades del
  encargo (sección 32), y no bloquea ningún criterio de aceptación.
* **Pruebas automatizadas escritas pero no ejecutadas aquí**: este entorno
  de desarrollo no tiene un checkout de Moodle core, por lo que ni
  PHPUnit ni Behat se han podido correr de verdad contra el plugin. Los
  archivos en `tests/` siguen las convenciones estándar de Moodle
  (`advanced_testcase`, generadores de datos, pasos Behat habituales) pero
  deben verificarse en un entorno real antes de darlas por buenas -
  tratarlas como "listas para revisar", no como "verificadas en verde".
* **Modo de prueba Behat**: como Behat no puede accionar una cámara real,
  `index.php` fuerza `forceManualCapture` cuando
  `defined('BEHAT_SITE_RUNNING')`, reutilizando el flujo de subida manual
  (ya existente para navegadores sin cámara) como mecanismo determinista
  de captura en los tests, tal como pide la sección 27 del encargo. Ese
  flag no tiene efecto fuera de una ejecución Behat real.
