# Changelog — local_profilephoto

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
