# local_profilephoto — Captura de fotografías de perfil

Plugin local para Moodle **5.1.0 y posteriores** que permite a un fotógrafo,
administrador o usuario autorizado buscar rápidamente a un alumno, hacerle
una fotografía y actualizar de inmediato su fotografía de perfil oficial en
Moodle, encadenando alumnos sin recargar la página.

Consulta [docs/technical-design.md](docs/technical-design.md) para el
diseño técnico completo, incluida la investigación verificada contra el
código fuente de Moodle 5.1 sobre cómo se actualiza oficialmente la
fotografía de un usuario.

## Estado de esta entrega

Esta es la **Entrega 1** de un desarrollo iterativo en 5 entregas (ver
sección 33 del encargo original):

* ✅ Esqueleto instalable, capacidades, configuración de administración.
* ✅ Búsqueda AJAX rápida y priorizada (idnumber/correo/usuario exactos
  primero, luego coincidencias parciales), respetando el ámbito de
  usuarios del operador.
* ✅ Selección de alumno con ficha visual (foto actual, aviso si ya tiene
  foto, aviso de permisos).
* ✅ Actualización **real** de la fotografía de perfil oficial mediante
  `\core\user::update_picture()` — la misma API que usa
  `user/editadvanced.php` en el núcleo de Moodle.
* ✅ Captura mediante subida manual de archivo (sustituye temporalmente a
  la cámara en directo, para poder validar el guardado real sin depender
  de hardware). El contrato de `save_picture` no cambiará al introducir la
  cámara.
* ⏳ Cámara en directo (`getUserMedia`), atajos de teclado, cuenta atrás →
  Entrega 2.
* ⏳ Cohortes/grupos/cursos, colas, sesiones fotográficas, auditoría
  persistente → Entrega 3.
* ⏳ Exportación ZIP, `manifest.csv`, tareas de limpieza → Entrega 4.
* ⏳ Batería completa de PHPUnit/Behat, revisión de seguridad y
  accesibilidad, paquete final → Entrega 5.

## Instalación

Ver [INSTALL.md](INSTALL.md).

## Capacidades

Ver la tabla completa en `db/access.php`. Las más relevantes para operar
el plugin son `local/profilephoto:capture` y
`local/profilephoto:updatepicture` (fotografiar y guardar) y
`local/profilephoto:viewallusers` (levantar la restricción de ámbito por
curso).

## Licencia

GPL v3 o posterior.
