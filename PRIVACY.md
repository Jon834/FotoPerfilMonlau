# Protección de datos — local_profilephoto

Este documento resume, en lenguaje llano, qué datos personales trata este
plugin y cómo. La declaración técnica formal está en
`classes/privacy/provider.php` (implementa la Privacy API de Moodle) y es
la fuente autoritativa; este documento es un resumen de apoyo.

## Qué datos personales trata el plugin

1. **La fotografía de perfil en sí.** El plugin **no la almacena** como
   dato propio: la procesa en memoria durante la petición HTTP y la
   entrega al mecanismo oficial de Moodle
   (`\core\user::update_picture()`), que la guarda como el `user/icon` del
   propio núcleo. A partir de ahí, esa fotografía ya es responsabilidad
   del proveedor de privacidad de `core_user`, no de este plugin.
2. **Datos de sesiones fotográficas** (`local_profilephoto_session`,
   `local_profilephoto_session_user`): quién abrió una sesión, con qué
   filtro (curso o cohorte), qué alumnos se incluyeron y el estado de cada
   uno (pendiente/capturado/saltado/ausente/error). Es información
   operativa, no biométrica.
3. **Registro de auditoría** (`local_profilephoto_log`): quién hizo qué,
   a quién, cuándo, con qué resultado, y la dirección IP. Nunca contiene
   la imagen, tokens ni contraseñas.
4. **Preferencia de cámara del operador**: qué cámara eligió la última
   vez, guardada en el `localStorage` de su propio navegador — no viaja
   al servidor ni se guarda en Moodle. No se considera dato personal del
   alumno, es una preferencia de dispositivo del operador.

## Lo que el plugin explícitamente NO hace

* No envía fotografías ni identificadores a servicios externos.
* No incorpora analítica de terceros.
* No hace reconocimiento facial ni ningún tipo de procesamiento biométrico.
* No guarda datos derivados de la imagen (vectores faciales, huellas, etc.).
* No conserva copias de la fotografía anterior al sustituirla (la
  sustitución es directa; ver "Recortes conscientes" en
  `docs/technical-design.md` sección 12.7 sobre la opción de copia
  temporal para deshacer, no implementada en esta versión).

## Minimización y retención

* Los ficheros ZIP de exportación se generan en un directorio temporal
  con nombre aleatorio, se sirven una única vez y se eliminan
  inmediatamente después de la descarga; si nunca se descargan, una tarea
  programada los elimina pasado el tiempo configurado
  (`local_profilephoto/exportretentionminutes`, 60 minutos por defecto).
* Los ficheros de borrador temporales usados durante el guardado de la
  fotografía se gestionan íntegramente por los mecanismos estándar de
  Moodle (área de archivos `user`/`draft`), no por este plugin.
* No existe ninguna configuración para conservar datos "por si acaso":
  cada tabla propia solo contiene lo estrictamente necesario para que la
  sesión de fotos y la auditoría funcionen.

## Derechos de los interesados (RGPD / normativa equivalente)

Implementado mediante la Privacy API de Moodle, de modo que los flujos
estándar de "Solicitudes de privacidad y protección de datos" del panel de
administración de Moodle funcionan sin intervención manual:

* **Acceso/exportación**: un alumno, u operador, puede solicitar la
  exportación de sus datos; se incluyen sus entradas de cola, las
  sesiones que haya operado, y las filas de auditoría en las que aparece
  como operador o como alumno afectado.
* **Supresión**: al aprobar la eliminación de datos de un usuario, se
  eliminan sus entradas de cola, sus sesiones propias (y la cola/log
  asociados a ellas), y las filas de log donde aparece como operador o
  alumno.
* Todo el dato del plugin vive en el contexto de sistema (no hay
  almacenamiento por curso propio del plugin), lo cual simplifica y hace
  más predecible el barrido de la Privacy API.

## Base jurídica orientativa

Este documento no sustituye el análisis legal que cada centro deba hacer,
pero orienta: el tratamiento de la fotografía de perfil suele ampararse en
el interés legítimo o la ejecución de la relación educativa (identificación
del alumnado en la plataforma), y la auditoría en el interés legítimo de
seguridad y trazabilidad de quién modificó qué. Cada centro debe confirmar
esto con su propio Delegado de Protección de Datos antes de desplegar en
producción.
