# Instalación — local_profilephoto (Entrega 1)

## Requisitos

* Moodle **5.1.0 o posterior** (rama `MOODLE_501_STABLE` o superior).
  Moodle 5.x sirve el código desde una carpeta `public/`; este plugin se
  copia en `public/local/profilephoto/`.
* PHP con extensión **GD** compilada con soporte JPEG y PNG (usada tanto
  por este plugin como por el propio `process_new_icon()` de Moodle).
* HTTPS en producción (obligatorio para que el navegador conceda acceso a
  la cámara en la Entrega 2; no bloqueante para esta Entrega 1, que usa
  subida manual de archivo).

## Pasos

1. Copia la carpeta de este plugin (todo el contenido de este repositorio)
   dentro de tu instalación de Moodle como:

   ```text
   <moodle>/public/local/profilephoto/
   ```

   (en instalaciones anteriores a la reestructuración `public/` de Moodle
   5.0, sería `<moodle>/local/profilephoto/`).

2. Visita **Administración del sitio → Notificaciones** y deja que Moodle
   ejecute la instalación del plugin.

3. Ve a **Administración del sitio → Usuarios → Permisos → Definir roles**
   y crea (o adapta un rol existente) un rol **Fotógrafo** con, como
   mínimo:

   * `local/profilephoto:view` — Permitir
   * `local/profilephoto:searchusers` — Permitir
   * `local/profilephoto:capture` — Permitir
   * `local/profilephoto:updatepicture` — Permitir

   Asigna ese rol en el contexto de curso/categoría que corresponda al
   ámbito real del fotógrafo (o en el contexto de sistema si debe operar
   sobre todo el centro, junto con `local/profilephoto:viewallusers`).

4. Ve a **Administración del sitio → Extensiones → Plugins locales →
   Captura de fotografías de perfil** y revisa la configuración (tamaño
   final, calidad JPEG, tamaño máximo de imagen, resultados máximos de
   búsqueda).

5. Accede a `<moodle>/local/profilephoto/index.php` (o usa el enlace que
   aparece en la navegación principal para cualquier usuario con
   `local/profilephoto:view`).

## Verificación funcional mínima de esta entrega

1. Busca un alumno por nombre, correo, usuario o `idnumber` — los
   resultados deben aparecer sin recargar la página.
2. Selecciónalo: debe verse su ficha con nombre, foto actual y avisos de
   estado.
3. Sube una imagen de prueba (JPEG o PNG) y pulsa **Guardar y siguiente**.
4. Comprueba en `user/profile.php?id=<id>` que la fotografía ha cambiado
   inmediatamente, sin necesidad de purgar cachés a mano.
5. Repite el intento con un usuario **sin** `local/profilephoto:updatepicture`:
   la llamada debe fallar con un error de capacidad y la foto no debe
   cambiar, aunque se manipule el `userid` enviado por AJAX.

## Nota sobre el módulo AMD

`amd/build/search.min.js` de esta entrega es un puerto manual (no
minificado, funcionalmente idéntico) de `amd/src/search.js`, escrito así
porque este entorno de desarrollo no dispone del toolchain `grunt`/Node de
un checkout completo de Moodle core. Antes de una entrega a producción a
gran escala, regenera el build de forma oficial:

```bash
# desde la raíz de un checkout de Moodle core, con local/profilephoto ya copiado dentro
npm install
npx grunt amd --root=local/profilephoto
```

Después de cualquier cambio en `amd/src/`, incrementa `$plugin->version`
en `version.php` para invalidar la caché de JS (`$CFG->jsrev`).
