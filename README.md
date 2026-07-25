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

Desarrollo iterativo en 5 entregas (ver sección 33 del encargo original).
**Entregas 1 y 2 completas:**

* ✅ Esqueleto instalable, capacidades, configuración de administración.
* ✅ Búsqueda AJAX rápida y priorizada (idnumber/correo/usuario exactos
  primero, luego coincidencias parciales), respetando el ámbito de
  usuarios del operador.
* ✅ Selección de alumno con ficha visual (foto actual, aviso si ya tiene
  foto, aviso de permisos).
* ✅ Actualización **real** de la fotografía de perfil oficial mediante
  `\core\user::update_picture()` — la misma API que usa
  `user/editadvanced.php` en el núcleo de Moodle.
* ✅ Captura con cámara en directo (`getUserMedia`), con subida manual de
  archivo como fallback automático cuando el navegador o el contexto no
  admiten cámara.
* ✅ Atajos de teclado (Espacio, Enter, R, B, Esc), configurables y
  desactivables; compatibles con disparadores USB tipo teclado.
* ✅ Cuenta atrás opcional antes de capturar (desactivada por defecto).
* ✅ Protección contra doble envío (identificador de operación + botón
  deshabilitado) y contra asignar una captura pendiente al alumno
  equivocado si se cambia de selección antes de guardar.
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
