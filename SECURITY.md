# Seguridad — local_profilephoto

Resumen de las medidas de seguridad implementadas y de lo que queda
pendiente de revisar antes de un despliegue en producción a gran escala.
No sustituye una revisión de seguridad formal por un tercero.

## Autenticación y autorización

* Toda página (`index.php`, `export.php`) llama a `require_login()` y
  comprueba capacidades explícitas antes de mostrar nada.
* Cada función externa (`classes/external/*.php`) repite
  `self::validate_context()` + `require_capability()` + `require_sesskey()`
  de forma independiente — nunca se asume que un chequeo anterior en el
  flujo del cliente sigue siendo válido.
* La autorización sobre el **alumno destino** se repite justo antes de
  escribir la fotografía (`save_picture.php`), no solo al abrir la
  pantalla o al listar resultados de búsqueda — un `userid` manipulado en
  la petición AJAX se rechaza igualmente.
* El ámbito de usuarios (`classes/local/access/scope.php`) se basa
  siempre en capacidades + matriculación, nunca en `is_siteadmin()` como
  atajo.
* Las capacidades siguen el principio de mínimo privilegio: capturar,
  actualizar, sustituir, ver identificadores, exportar sesión propia,
  exportar cualquiera, gestionar sesiones y configurar son permisos
  independientes (ver `db/access.php`).

## Validación de entrada

* Todos los parámetros de las funciones externas usan tipos `PARAM_*` de
  Moodle (`external_value`), nunca se leen `$_GET`/`$_POST` directamente.
* La imagen recibida se valida de verdad, no por la extensión del
  archivo: `getimagesizefromstring()` + `imagecreatefromstring()`
  (`classes/local/image/processor.php`) confirman que es una imagen JPEG
  o PNG real antes de procesarla; los metadatos EXIF se eliminan como
  efecto colateral de decodificar y volver a codificar con GD.
* Límites de tamaño (`maxsourcebytes`) y de dimensión mínima aplicados
  antes de decodificar, para no gastar memoria procesando un payload
  malicioso desproporcionado.
* Los nombres de archivo de exportación se sanean con el `PARAM_FILE` de
  Moodle (no con un regex propio), lo que evita traversal de rutas y
  caracteres peligrosos por construcción, no por lista negra manual.

## CSRF y doble envío

* `require_sesskey()` en cada función externa de escritura.
* Identificador de operación (`operationid`) generado en el cliente y
  comprobado en servidor (`cache::make('local_profilephoto', 'recentsaves')`)
  para rechazar el reenvío accidental de la misma captura (doble clic,
  reintento de red).

## Actualización de la fotografía de perfil

* Se usa exclusivamente `\core\user::update_picture()`, el mismo
  mecanismo que usa `user/editadvanced.php` en el núcleo — nunca una
  escritura directa a `mdl_user.picture` ni al sistema de archivos. Ver
  `docs/technical-design.md` sección 4 para la traza completa contra el
  código fuente real de Moodle 5.1.

## Exportación ZIP

* Los archivos temporales se generan con nombre aleatorio
  (`bin2hex(random_bytes(16))`, 128 bits de entropía) en un directorio
  fuera del docroot servible directamente (`make_temp_directory()`).
* El token de descarga es igualmente aleatorio (160 bits,
  `bin2hex(random_bytes(20))`), de un solo uso (se borra de la caché al
  primer acceso, se sirva o no correctamente), y verificado contra el
  operador que lo generó (o la capacidad `exportall`).
* No hay URL predecible: sin el token correcto, `export.php` no revela ni
  confirma que exista ningún ZIP pendiente.

## Auditoría

* `local_profilephoto_log` registra operador, alumno afectado, acción,
  resultado, IP y marca de tiempo — nunca la imagen, base64, tokens ni
  contraseñas (comprobado explícitamente en el diseño de
  `classes/local/audit/logger.php` y `classes/local/export/zip_builder.php`).

## Lo que falta por revisar antes de producción a gran escala

* **Auditoría de seguridad externa formal**: este documento es una
  autoevaluación del propio desarrollo, no una revisión independiente.
* **Pruebas de carga reales** con >10.000 usuarios y colas de 500+
  alumnos (encargo sección 28); el diseño está pensado para ese volumen
  (búsqueda indexada, sin cargar usuarios en el cliente), pero no se ha
  medido en un Moodle real desde este entorno de desarrollo.
* **Cabeceras de seguridad y CSP** del sitio Moodle en general quedan
  fuera del alcance de un plugin `local` — son responsabilidad de la
  configuración del servidor/tema.
* **Revisión de accesibilidad** (contraste, navegación solo teclado,
  lectores de pantalla) hecha de forma razonable durante el desarrollo
  (atajos de teclado, `aria-live`, `<label>` en todos los campos) pero sin
  una auditoría WCAG formal.
