# Instalación — local_profilephoto (Entregas 1 y 2)

## Requisitos

* Moodle **5.1.0 o posterior** (rama `MOODLE_501_STABLE` o superior).
  Moodle 5.x sirve el código desde una carpeta `public/`; este plugin se
  copia en `public/local/profilephoto/`.
* PHP con extensión **GD** compilada con soporte JPEG y PNG (usada tanto
  por este plugin como por el propio `process_new_icon()` de Moodle).
* **HTTPS** (o `localhost`) para que el navegador conceda acceso a la
  cámara. Sin HTTPS la pantalla sigue siendo utilizable: cae
  automáticamente a la subida manual de archivo.

## Pasos

Este repositorio tiene el plugin en su raíz (sin carpeta contenedora): los
archivos `version.php`, `lib.php`, etc. están directamente en la raíz del
repo. Elige una de estas dos vías para llevarlo a tu Moodle:

### Opción A — clonar directamente en el servidor (recomendada)

```bash
git clone https://github.com/Jon834/FotoPerfilMonlau.git public/local/profilephoto
```

(en instalaciones anteriores a la reestructuración `public/` de Moodle 5.0,
el destino sería `local/profilephoto` en lugar de `public/local/profilephoto`).

### Opción B — subir un ZIP desde la interfaz de administración

1. Descarga [`dist/profilephoto.zip`](dist/profilephoto.zip) directamente
   desde GitHub (botón "Download raw file" en esa página, o
   `https://github.com/Jon834/FotoPerfilMonlau/raw/main/dist/profilephoto.zip`).
   Ese ZIP ya tiene la carpeta interna llamada `profilephoto` (el nombre
   que Moodle espera para instalar `local_profilephoto`), a diferencia del
   ZIP que genera el botón "Code → Download ZIP" de GitHub, que **no** es
   directamente instalable (usa el nombre del repositorio, no del plugin).
2. Ve a **Administración del sitio → Extensiones → Plugins → Instalar
   plugins** y sube ese ZIP.

En ambos casos, continúa con estos pasos:

1. Visita **Administración del sitio → Notificaciones** y deja que Moodle
   ejecute la instalación del plugin.

2. Ve a **Administración del sitio → Usuarios → Permisos → Definir roles**
   y crea (o adapta un rol existente) un rol **Fotógrafo** con, como
   mínimo:

   * `local/profilephoto:view` — Permitir
   * `local/profilephoto:searchusers` — Permitir
   * `local/profilephoto:capture` — Permitir
   * `local/profilephoto:updatepicture` — Permitir

   Asigna ese rol en el contexto de curso/categoría que corresponda al
   ámbito real del fotógrafo (o en el contexto de sistema si debe operar
   sobre todo el centro, junto con `local/profilephoto:viewallusers`).

3. Ve a **Administración del sitio → Extensiones → Plugins locales →
   Captura de fotografías de perfil** y revisa la configuración (tamaño
   final, calidad JPEG, tamaño máximo de imagen, resultados máximos de
   búsqueda).

4. Accede a `<moodle>/local/profilephoto/index.php` (o usa el enlace que
   aparece en la navegación principal para cualquier usuario con
   `local/profilephoto:view`).

## Verificación funcional mínima

1. Busca un alumno por nombre, correo, usuario o `idnumber` — los
   resultados deben aparecer sin recargar la página.
2. Selecciónalo: debe verse su ficha con nombre, foto actual y avisos de
   estado.
3. Pulsa **Activar cámara** y concede permiso cuando el navegador lo pida.
   Debe verse el vídeo en directo con la guía facial superpuesta.
4. Pulsa **Hacer foto** (o la barra espaciadora): debe aparecer la
   previsualización congelada con los botones **Repetir** y **Guardar y
   siguiente**.
5. Pulsa **Repetir** (o `R`) y comprueba que vuelve al vídeo en directo sin
   reiniciar la cámara (sin parpadeo/reconexión).
6. Vuelve a capturar y pulsa **Guardar y siguiente** (o `Enter`).
7. Comprueba en `user/profile.php?id=<id>` que la fotografía ha cambiado
   inmediatamente, sin necesidad de purgar cachés a mano.
8. En un navegador/pestaña sin HTTPS, o denegando el permiso de cámara,
   comprueba que la pantalla cae automáticamente a la subida manual de
   archivo (Entrega 1) y que ese flujo sigue guardando correctamente.
9. Repite el intento con un usuario **sin** `local/profilephoto:updatepicture`:
   la llamada debe fallar con un error de capacidad y la foto no debe
   cambiar, aunque se manipule el `userid` enviado por AJAX.
10. Selecciona un alumno, haz una foto (sin guardar) y luego busca y
    selecciona a otro alumno distinto: la captura pendiente del primero
    debe descartarse automáticamente, nunca guardarse contra el segundo.

## Nota sobre los módulos AMD

Los cuatro módulos AMD del plugin (`search`, `camera`, `shortcuts`,
`capture`) se distribuyen como un puerto manual (no minificado,
funcionalmente idéntico) de sus fuentes en `amd/src/`, escrito así porque
este entorno de desarrollo no dispone del toolchain `grunt`/Node de un
checkout completo de Moodle core. Antes de una entrega a producción a gran
escala, regenera los builds de forma oficial:

```bash
# desde la raíz de un checkout de Moodle core, con local/profilephoto ya copiado dentro
npm install
npx grunt amd --root=local/profilephoto
```

Después de cualquier cambio en `amd/src/`:

1. Incrementa `$plugin->version` en `version.php`.
2. Purga las cachés del sitio (**Administración del sitio → Desarrollo →
   Purgar cachés**). Esto es imprescindible incluso en sitios de
   desarrollo: Moodle combina todos los módulos AMD en un paquete cacheado
   en disco que **no** se invalida solo con subir la versión del plugin.

### Por qué cada módulo lleva también un `.js.map`

Confirmado leyendo `public/lib/requirejs.php` de `MOODLE_501_STABLE`: cuando
`$CFG->cachejs` está desactivado (típico en un sitio de desarrollo, con
`$rev <= 0`), Moodle sirve los módulos AMD uno a uno y, para cada uno,
comprueba si existe `amd/build/<módulo>.min.js.map` junto al build:

* si existe, sirve el `.min.js` real (nuestro build correcto, en formato
  `define()`);
* si **no** existe, asume que es "código fuente antiguo de un plugin" y
  sirve directamente `amd/src/<módulo>.js` tal cual, sin transpilar.

Como los ficheros en `amd/src/` están escritos en el estilo moderno de
Moodle (ES modules, con `import`/`export`), servirlos sin pasar por el
build produce exactamente `Uncaught SyntaxError: Cannot use import
statement outside a module` seguido de `No define call for
local_profilephoto/<módulo>` en la consola del navegador — el bug real que
se diagnosticó durante las pruebas de la Entrega 1. Por eso cada
`amd/build/<módulo>.min.js` incluye su `.map` (con `mappings` vacío — solo
hace falta que el archivo exista para que Moodle tome la rama correcta; no
es una fuente de mapeo línea a línea real). Cuando se regeneren los builds
con `grunt amd` oficialmente, los `.map` correctos sustituirán a estos.
