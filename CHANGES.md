# Changelog — local_profilephoto

## 0.8.2 (fix: la exportación a Excel fallaba con "Failed opening required phpspreadsheet")

* En algunos Moodle (confirmado en un sitio 5.1 con la reestructuración
  del docroot `public/`) la librería PhpSpreadsheet no está en la ruta
  clásica `lib/phpspreadsheet/vendor/autoload.php`, y el Excel del Control
  d'activitat fallaba con un error fatal de PHP al intentar cargarla.
* `activity_xlsx_builder::require_phpspreadsheet()` ahora primero
  comprueba si la clase ya está disponible (por si el propio Moodle la
  autocarga), y si no, prueba varias rutas plausibles en vez de una sola
  fija. Si de verdad no está en ningún sitio, se lanza un error traducido
  y comprensible («aquest Moodle no té disponible la llibreria
  PhpSpreadsheet…») en lugar del fallo crudo de PHP.

## 0.8.1 (fix: el build de export.js no reflejaba los cambios de la 0.8.0)

* `amd/build/export.min.js` (el fichero que Moodle sirve realmente al
  navegador) no se había actualizado al añadir la orientación y el Excel
  en la 0.8.0 — por eso esas dos opciones no hacían nada. Se ha portado a
  mano el `amd/src/export.js` actual a ese fichero (ver nota en
  `INSTALL.md` sobre por qué este plugin usa un build "a mano" en vez del
  `grunt amd` habitual). De paso se corrige que también le faltaba el
  soporte para los grupos de radio (`#lpp-export-filtertype`,
  `#lpp-export-type`) de un cambio anterior.
* Tras esta actualización hay que **purgar las cachés del sitio**
  (Administración del sitio → Desarrollo → Purgar cachés): Moodle cachea
  el paquete AMD en disco y no lo invalida solo con subir la versión del
  plugin.

## 0.8.0 (Control de actividad: formato por defecto, orientación de página, exportación a Excel)

* **«Control d'activitat»** pasa a ser el tipo de documento seleccionado por
  defecto en la pantalla de exportación, en lugar de «Orla compacta».
* Nueva opción **«Orientació de pàgina»** (Vertical A4 / Apaïsat A4) en el
  Control d'activitat. Apaïsat sigue siendo el comportamiento de siempre; en
  vertical, la cabecera de marca y el bloque Activitat/Data/Lloc/Responsables
  se adaptan al ancho más estrecho (el bloque de actividad pasa a dos líneas).
* Nueva opción **«Format de sortida»**: además de PDF, el Control d'activitat
  se puede generar como **Excel (.xlsx)** (`classes/local/export/activity_xlsx_builder.php`),
  con las mismas columnas, orden y colores de marca configurados — sin
  fotografías ni orientación de página, ya que no aplican a una hoja de
  cálculo. Las columnas de casella/texto se generan como celdas vacías con
  borde, listas para rellenar en el ordenador.
* `activity_pdf_builder` expone ahora varios ayudantes de solo datos
  (`translate_word`, `column_label`, `sort_users`...) que el nuevo generador
  de Excel reutiliza, en lugar de duplicar el modelo de columnas.
* Cobertura en `tests/activity_xlsx_builder_test.php` y casos nuevos en
  `tests/activity_pdf_builder_test.php` para la orientación vertical.

## 0.7.12 (etapa «Corporate»)

* La etapa se muestra como **«Corporate»** en los tres idiomas (antes
  «Corporativo» / «Corporatiu»), igual que «Monlau Group».

## 0.7.11 (etapas de los PDF: ESO/BATX unificadas, nueva Monlau Group)

* **ESO** y **Bachillerato/Batxillerat** pasan a ser una sola opción
  **«ESO / Bachillerato»** (clave interna `eso`, color de cabecera
  rgb 60,168,83). Los valores `batx` antiguos (formularios guardados,
  colas) se pliegan a `eso` automáticamente.
* Nueva etapa **«Monlau Group»** (clave `monlaugroup`), cabecera negra,
  logo `monlaugroup.svg`.
* **Corporativo** deja de usar un logo generado por código y toma
  `monlau_corp.jpg` del tema.
* Los logos de FP y ESO/BATX se leen del mismo sitio (`monlau_fp.jpg`,
  `monlau_eso.jpg`), ya no de un host de staging.
* Nuevo ajuste **«URL base de las imágenes Monlau»**
  (`local_profilephoto/monlauimagesbase`): la carpeta `customimages` del
  tema. Su último segmento cambia al resubir una imagen en el tema; si
  los logos dejan de salir, se actualiza ahí sin tocar código.
* Se admite el token `[[monlauimage:archivo.ext]]` para referirse a esos
  activos (`branding::expand_tokens()`).
* Todo el manejo de color/logo/etapa vive ahora en
  `classes/local/export/branding.php`, compartido por los dos
  generadores de PDF (antes estaba duplicado y podía divergir). Cobertura
  en `branding_test.php`.

## 0.7.10 (Control de actividad: mismo aspecto que el resto de la pantalla)

* El formulario del Control de Actividad usa ahora el mismo ritmo
  vertical que el formulario estándar (`gap` de 1,5 rem entre secciones),
  así las leyendas «Actividad», «Plantilla», «Columnas», «Opciones» dejan
  de quedar pegadas al campo anterior.
* El encabezado «Vista previa» del panel derecho recibe el mismo trato
  que una leyenda de sección (mayúsculas, línea inferior), para que el
  panel se lea como parte del mismo formulario.
* Solo CSS.

## 0.7.9 (exportar: tolerancia a caché de plantilla obsoleta)

* `groupValue()` en `amd/{src,build}/export.js` vuelve a leer `.value` si
  no encuentra radios dentro del contenedor, para que la pantalla siga
  funcionando mientras la caché de plantillas de Moodle todavía sirve el
  `<select>` antiguo tras actualizar.
* Si el buscador de grupo/cohorte no aparece (parpadea y desaparece):
  purga todas las cachés y recarga con Ctrl+F5. Es un desajuste
  plantilla/JS en caché, no un fallo de datos.

## 0.7.8 (Control de actividad: cohortes acotadas al ámbito del docente)

* En el Control de Actividad, un operador **sin**
  `local/profilephoto:viewallusers` ya no ve todas las cohortes del sitio:
  solo las que tienen algún alumno **matriculado activamente en un curso
  donde el operador tiene `local/profilephoto:capture`**. El listado
  generado sigue incluyendo a **toda** la cohorte.
* Un operador **con** `viewallusers` (o administrador) sigue viendo
  todas.
* Se sustituye la comprobación de `moodle/cohort:view` por este ámbito
  propio del plugin, coherente con la pantalla de captura. Ya no hace
  falta conceder `moodle/cohort:view` al rol del docente.
* Nuevos métodos `scope::get_allowed_cohortids()` y
  `scope::can_use_cohort()`, con cobertura en `scope_test.php`.
* Afecta a `get_activity_cohorts`, `get_activity_cohort_info` y
  `create_activity_export`.

## 0.7.7 (exportar: selección de documento y origen sin desplegables)

* **«Tipo de documento»** deja de ser un desplegable y pasa a ser una
  lista de tarjetas seleccionables, cada una con una descripción de una
  línea de lo que genera. Las 6 opciones (incluido el Control de
  Actividad/Hoja Personalizable) quedan visibles sin abrir nada.
* Ese selector sale ahora de la zona que se ocultaba al entrar en el
  Control de Actividad: se puede cambiar de tipo de documento en ambos
  sentidos sin recargar.
* **«Exportar por»** (Grupo o Clase · Módulo o Asignatura · Sesión) pasa
  a ser un grupo de botones en lugar de un desplegable.
* Densidad, Etapa e Idioma se mantienen como desplegables.
* La sección de ajustes del PDF pasa a llamarse «Ajustes del documento».
* Sin cambios de backend: la plantilla y `amd/{src,build}/export.js`
  leen el valor del radio marcado; el resto del flujo es idéntico.

## 0.7.6 (exportar: revisión de terminología)

Cambio solo de textos (es/ca/en). No cambia ninguna URL, capacidad ni
comportamiento.

* La pantalla pasa a llamarse **«Generador de orlas y listados»** (antes
  «Exportar fotografías»), en el título, la cabecera y el enlace de la
  pantalla de captura.
* En «Exportar por» y en el Control de actividad:
  * **Cohorte** → **«Grupo o Clase»**
  * **Curso** → **«Módulo o Asignatura»**
  * Se mantiene entre paréntesis el término técnico de Moodle (cohorte /
    curso) en la ayuda `?`, para que el administrador sepa a qué mapea.
* **«Tipo de exportación»** → **«Tipo de documento»**.
* **«Control de actividad»** → **«Control de Actividad/Hoja
  Personalizable»**.
* Se actualizan también las ayudas contextuales, los mensajes de error,
  la pantalla de captura (sesiones por grupo/módulo) y los textos de la
  Privacy API afectados.

## 0.6.3 (exportar: filtro de participantes por rol)

* Al exportar **por curso** aparece un selector **Participantes del curso**:
  *Solo estudiantes* (por defecto), *Estudiantes y profesores* o *Todos los
  participantes*. Antes se incluía siempre a todo el mundo matriculado
  (profesores incluidos) en las orlas y listados.
* El filtro se basa en el arquetipo del rol (student / teacher /
  editingteacher), así que respeta roles personalizados del centro.
* No aplica a cohortes (se exportan todos sus miembros) ni a sesiones
  fotográficas (la cola ya está fijada).

## 0.6.2 (exportar: pantalla reorganizada y ayuda por campo)

* La pantalla de exportación se reorganiza en tres secciones ("Qué
  exportar", "Formato del documento", "Archivos") con una frase
  introductoria, rejilla de dos/tres columnas para los selectores cortos
  y botón "Generar" a ancho completo.
* Cada campo tiene un icono de ayuda `?` (popover nativo de Moodle) que
  explica para qué sirve y a qué tipo de exportación aplica.
* Los campos se muestran según el **Tipo de exportación**: con ZIP se ve
  "Nombrar archivos por"; con las orlas/PDF se ven densidad, etapa,
  idioma y texto extra.
* El tipo de exportación por defecto pasa a ser **Orla compacta**.

## 0.6.1 (exportar: cohorte por defecto y búsqueda)

* En la pantalla de exportación, "Exportar por" ahora sale como **Cohorte**
  por defecto (antes: sesión fotográfica).
* Los selectores de **cohorte** y de **curso** se convierten en campos con
  búsqueda: se puede escribir para filtrar la lista en vez de desplegar
  todas las opciones. Usa `core/form-autocomplete` del núcleo de Moodle.

## 0.5.3 (cámara frontal/trasera en móvil)

* En móviles y tablets (detectados por puntero táctil), la pantalla de
  captura ahora ofrece un botón para alternar entre cámara frontal y
  trasera, usando la restricción estándar `facingMode` en lugar del
  selector de dispositivo por `deviceId` (poco fiable para este propósito
  en navegadores móviles, especialmente iOS Safari). Empieza por defecto
  en la cámara trasera, ya que el operador fotografía a otra persona, no
  se hace un selfie; recuerda la última cámara usada.
* El selector de dispositivo por nombre (pensado para varias webcams de
  escritorio) sigue igual en ordenadores de sobremesa/portátiles.

## 0.5.2 (menú de administración)

* La página de ajustes y "Abrir pantalla de captura" ahora aparecen agrupadas
  bajo una categoría "Captura de fotografías de perfil" en Site
  administration > Plugins > Local plugins, indentadas igual que otras
  integraciones con sub-páginas (p. ej. Office 365).
* La página de ajustes se renombra a "Configuración captura de fotografías de
  perfil" para distinguirla del enlace de acceso directo a la pantalla de
  captura.

## 0.5.0 — Entregas 3+4+5 (combinadas)

* Sesiones fotográficas por curso o cohorte, con cola ordenada
  (apellidos/nombre/correo/idnumber/usuario), avance automático al
  siguiente pendiente, saltar/marcar ausente y auto-finalización cuando
  no queda nadie pendiente.
* Auditoría persistente (`local_profilephoto_log`) y eventos Moodle
  nuevos: `session_started`, `session_completed`, `export_created`,
  `export_downloaded`. `picture_updated` ahora indica si sustituyó una
  foto existente.
* Exportación ZIP con `manifest.csv`, formato de nombre configurable
  (idnumber/username/email/userid/fullname), desambiguación de
  duplicados, descarga de un solo uso mediante token no adivinable, y
  tarea programada de limpieza de ZIP no descargados.
* Privacy API completa: metadatos, exportación y eliminación de datos
  para las tres tablas propias del plugin (antes `null_provider`, ya no
  aplicable con datos propios reales).
* Atajo de teclado `S` para saltar dentro de una sesión activa.
* Modo de prueba para Behat: `index.php` fuerza el flujo de subida manual
  cuando `BEHAT_SITE_RUNNING` está definido, ya que Behat no puede
  accionar una cámara real.
* Nuevas tablas: `local_profilephoto_session`,
  `local_profilephoto_session_user`, `local_profilephoto_log`.
* Pendiente documentado (no implementado): restaurar fotografía anterior,
  navegación libre "alumno anterior" en la cola, exportación asíncrona
  para selecciones muy grandes, cohortes autorizadas por operador, QR.
  Ver `docs/technical-design.md` sección 12.7.

## 0.2.0 — Entrega 2

* Captura con cámara en directo (`getUserMedia`), con selector de
  dispositivo recordado y subida manual como fallback automático.
* Atajos de teclado: Espacio, Enter, R, B, Esc.
* Cuenta atrás opcional antes de capturar (desactivada por defecto).
* Refuerzo de la protección contra doble envío y contra asignar una
  captura pendiente al alumno equivocado al cambiar de selección.

## 0.1.0 — Entrega 1

* Esqueleto instalable: capacidades, configuración de administración,
  navegación.
* Búsqueda AJAX priorizada (idnumber/correo/usuario exactos primero) con
  ámbito de usuarios basado en capacidades, nunca en `is_siteadmin()`.
* Actualización real de la fotografía de perfil mediante
  `\core\user::update_picture()`.
* Captura mediante subida manual de archivo, para validar el guardado
  real sin depender de una cámara.
