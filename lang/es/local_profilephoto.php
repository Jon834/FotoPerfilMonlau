<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for local_profilephoto.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Captura de fotografías de perfil';
$string['profilephoto:view'] = 'Acceder a la pantalla de captura de fotografías';
$string['profilephoto:searchusers'] = 'Buscar alumnos en la pantalla de captura';
$string['profilephoto:capture'] = 'Capturar fotografías de alumnos';
$string['profilephoto:updatepicture'] = 'Actualizar la fotografía de perfil oficial de un alumno';
$string['profilephoto:replaceexisting'] = 'Sustituir una fotografía de perfil ya existente';
$string['profilephoto:viewidentifiers'] = 'Ver identificadores personales (correo, idnumber, usuario)';
$string['profilephoto:viewallusers'] = 'Ver y fotografiar alumnos de todo el sistema, sin restricción de ámbito';
$string['profilephoto:exportsession'] = 'Exportar las fotografías de una sesión propia';
$string['profilephoto:exportall'] = 'Exportar fotografías de cualquier sesión';
$string['profilephoto:managesessions'] = 'Gestionar sesiones fotográficas';
$string['profilephoto:configure'] = 'Configurar el plugin de captura de fotografías';
$string['profilephoto:viewlogs'] = 'Ver el registro de auditoría de fotografías';
$string['profilephoto:restoreprevious'] = 'Restaurar la fotografía anterior de un alumno';

$string['settings_enabled'] = 'Activar el plugin';
$string['settings_enabled_desc'] = 'Si se desactiva, la pantalla de captura no estará disponible para nadie salvo administradores.';
$string['settings_targetsize'] = 'Resolución final (píxeles)';
$string['settings_targetsize_desc'] = 'Tamaño (ancho = alto) al que se redimensiona la fotografía antes de enviarla al mecanismo oficial de Moodle. Moodle genera además su propia miniatura de 512×512, por lo que se recomienda un valor igual o superior.';
$string['settings_jpegquality'] = 'Calidad JPEG';
$string['settings_jpegquality_desc'] = 'Calidad de compresión JPEG, de 0 a 100.';
$string['settings_maxsourcebytes'] = 'Tamaño máximo de la imagen capturada (bytes)';
$string['settings_maxsourcebytes_desc'] = 'Las imágenes recibidas que superen este tamaño se rechazan antes de procesarse.';
$string['settings_maxsearchresults'] = 'Número máximo de resultados de búsqueda';
$string['settings_maxsearchresults_desc'] = 'Límite superior de resultados devueltos por cada búsqueda, independientemente de lo que solicite el cliente.';
$string['opencapturescreen'] = 'Abrir pantalla de captura';

$string['event_picture_updated'] = 'Fotografía de perfil actualizada';

$string['privacy:metadata:null_reason'] = 'Este plugin no almacena datos personales propios: la fotografía capturada se procesa en memoria y se guarda únicamente a través del mecanismo oficial de Moodle (componente "user"), ya cubierto por el proveedor de privacidad del núcleo. Los ficheros temporales de borrador se eliminan mediante los mecanismos estándar de Moodle.';

$string['error_emptyimage'] = 'No se ha recibido ninguna imagen.';
$string['error_imagetoolarge'] = 'La imagen recibida supera el tamaño máximo permitido.';
$string['error_imagetoosmall'] = 'La imagen recibida es demasiado pequeña.';
$string['error_invalidimage'] = 'El archivo recibido no es una imagen válida.';
$string['error_unsupportedmimetype'] = 'Formato de imagen no admitido. Use JPEG o PNG.';
$string['error_processingfailed'] = 'No se ha podido procesar la imagen en el servidor.';
$string['error_outofscope'] = 'No tiene permiso para operar sobre este alumno.';
$string['error_replacenotallowed'] = 'Este alumno ya tiene una fotografía de perfil y no tiene permiso para sustituirla.';
$string['error_duplicatesubmission'] = 'Esta fotografía ya se ha guardado. Evite enviar la misma captura dos veces.';
$string['error_plugindisabled'] = 'La captura de fotografías de perfil está desactivada en este sitio.';

$string['search_label'] = 'Buscar alumno';
$string['search_placeholder'] = 'Nombre, correo, usuario o idnumber…';
$string['search_noresults'] = 'No se han encontrado alumnos.';
$string['no_student_selected'] = 'Seleccione un alumno para empezar.';
$string['save_and_next'] = 'Guardar y siguiente';
$string['save_success'] = 'Fotografía guardada correctamente para {$a}';
$string['badge_hasphoto'] = 'Ya tiene foto';
$string['badge_suspended'] = 'Suspendido';
$string['warning_hasphoto'] = 'Este alumno ya tiene una fotografía de perfil';
$string['warning_cannotupdate'] = 'No tiene permiso para actualizar la fotografía de este alumno';
$string['label_idnumber'] = 'ID';

$string['camera_unsupported'] = 'Este navegador o esta conexión no permiten acceder a la cámara (se requiere HTTPS). Suba una fotografía de prueba manualmente.';
$string['camera_select'] = 'Seleccionar cámara';
$string['camera_activate'] = 'Activar cámara';
$string['take_photo'] = 'Hacer foto';
$string['repeat_photo'] = 'Repetir';
$string['manual_fallback_desc'] = 'No se puede usar la cámara en directo en este dispositivo o navegador. Suba una fotografía manualmente.';
$string['manual_fallback_label'] = 'Fotografía';
$string['shortcuts_help_title'] = 'Atajos de teclado';
$string['key_space'] = 'Espacio';
$string['key_enter'] = 'Enter';
$string['key_esc'] = 'Esc';
$string['shortcut_capture'] = 'Hacer fotografía';
$string['shortcut_save'] = 'Guardar y siguiente';
$string['shortcut_repeat'] = 'Repetir fotografía';
$string['shortcut_search'] = 'Ir al buscador';
$string['shortcut_cancel'] = 'Cancelar previsualización';

$string['camera_error_insecure'] = 'Este sitio no usa HTTPS: el navegador no permite acceder a la cámara. Suba una fotografía manualmente.';
$string['camera_error_permission'] = 'Ha denegado el acceso a la cámara. Revise los permisos del navegador para este sitio, o suba una fotografía manualmente.';
$string['camera_error_notfound'] = 'No se ha detectado ninguna cámara en este dispositivo.';
$string['camera_error_inuse'] = 'La cámara está siendo utilizada por otra aplicación.';
$string['camera_error_generic'] = 'No se ha podido acceder a la cámara.';

$string['settings_enableshortcuts'] = 'Activar atajos de teclado';
$string['settings_enableshortcuts_desc'] = 'Permite usar Espacio, Enter, R, B y Esc para operar la pantalla de captura sin ratón (o con un disparador USB que emule esas teclas).';
$string['settings_enablecountdown'] = 'Activar cuenta atrás';
$string['settings_enablecountdown_desc'] = 'Si está activado, al pulsar «Hacer foto» se inicia una cuenta atrás antes de capturar, en lugar de capturar inmediatamente. Desactivado por defecto.';
$string['settings_countdownseconds'] = 'Duración de la cuenta atrás (segundos)';
$string['settings_countdownseconds_desc'] = 'Solo se aplica si la cuenta atrás está activada.';
