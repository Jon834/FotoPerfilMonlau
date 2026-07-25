# Guía de uso — Captura de fotografías de perfil

## Acceso

Ve a **`<tu-moodle>/local/profilephoto/index.php`**, o usa el enlace
"Captura de fotografías de perfil" que aparece en el menú de navegación
(cajón lateral en el tema Boost) si tienes el permiso
`local/profilephoto:view`.

## Flujo rápido: fotografiar a un alumno concreto

1. Escribe en el buscador (nombre, correo, usuario o `idnumber`) — los
   resultados aparecen solos, sin necesidad de pulsar nada.
2. Haz clic en el alumno correcto. Comprueba el nombre y la foto actual
   antes de continuar — si ya tiene foto, verás un aviso.
3. Pulsa **Activar cámara** la primera vez (el navegador pedirá permiso).
4. Pulsa **Hacer foto** (o la barra espaciadora).
5. Si la foto está bien, pulsa **Guardar y siguiente** (o `Enter`). Si no,
   pulsa **Repetir** (o `R`) y vuelve a intentarlo — la cámara no se
   reinicia, es instantáneo.
6. Verás un mensaje de confirmación con el nombre del alumno, y la foto
   ya es la oficial de su perfil de Moodle.

## Flujo por lotes: fotografiar un curso o cohorte entero

1. En el panel superior, elige **Curso** o **Cohorte** y selecciona cuál.
2. Elige el orden (por defecto, apellidos).
3. Pulsa **Iniciar sesión de fotos**. El primer alumno pendiente se carga
   automáticamente.
4. Haz la foto y pulsa **Guardar y siguiente**: el siguiente alumno se
   carga solo, sin que tengas que buscar nada.
5. Si un alumno no está presente, pulsa **Ausente**. Si quieres saltarlo
   y volver más tarde, pulsa **Saltar** (o la tecla `S`).
6. El contador de arriba (`X/Y capturados — Z pendientes`) te dice cuánto
   queda. Cuando no queda nadie pendiente, la sesión se marca como
   completada sola.
7. Si cierras el navegador sin querer, vuelve a entrar y arranca de nuevo
   el filtro: tu progreso anterior sigue guardado (los alumnos ya
   capturados no vuelven a aparecer como pendientes).

## Si la cámara no funciona

- Si el sitio no usa HTTPS, o el navegador no admite cámara, o deniegas
  el permiso: aparece automáticamente un cuadro para **subir una
  fotografía manualmente** en su lugar. El resto del flujo (previsualizar,
  guardar, siguiente) es idéntico.
- Revisa los permisos de cámara del navegador para este sitio si crees
  que deberías tener acceso y no te lo pide.

## Atajos de teclado

| Tecla     | Acción                                  |
|-----------|------------------------------------------|
| Espacio   | Hacer foto                               |
| Enter     | Guardar y siguiente                      |
| R         | Repetir                                  |
| B         | Ir al buscador                           |
| Esc       | Cancelar la previsualización             |
| S         | Saltar alumno (solo dentro de una sesión)|

Se pueden desactivar desde la configuración del plugin. Un lector/disparador
USB que emule estas teclas funciona igual que el teclado, sin configuración
adicional.

## Exportar fotografías

1. Pulsa **Exportar fotografías descargables** (arriba de la pantalla de
   captura), o ve directamente a `export.php`.
2. Elige si quieres exportar por **sesión**, **curso** o **cohorte**.
3. Elige cómo se nombran los archivos (por defecto, `idnumber`; si un
   alumno no tiene, se usa su nombre de usuario).
4. Pulsa **Generar ZIP**. La descarga empieza automáticamente al terminar.
5. El ZIP incluye un `manifest.csv` con el detalle de cada fotografía
   exportada.
6. El enlace de descarga es de un solo uso: si necesitas el mismo ZIP
   otra vez, genera una nueva exportación.

Si la selección tiene demasiados alumnos, se te pedirá acotar el filtro
(curso/cohorte/sesión más pequeños) — el límite lo fija el administrador.

## Preguntas frecuentes

**¿Puedo sustituir la foto de alguien que ya tiene una?**
Sí, si tienes el permiso correspondiente (`local/profilephoto:replaceexisting`).
Verás un aviso antes de hacerlo, pero no hay forma de recuperar la foto
anterior una vez sustituida — esta versión no guarda copias de seguridad.

**¿Puedo volver al alumno anterior si me equivoco de orden?**
No hay un botón de "alumno anterior" en esta versión: puedes buscarlo de
nuevo manualmente en cualquier momento, incluso con una sesión activa.
