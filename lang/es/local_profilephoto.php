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
$string['camera_comingsoon'] = 'La captura con cámara en directo se activará en una próxima entrega. Por ahora, suba una fotografía de prueba para validar el guardado en el perfil de Moodle.';
$string['manual_capture_label'] = 'Fotografía de prueba (modo manual, entrega 1)';
$string['save_and_next'] = 'Guardar y siguiente';
$string['save_success'] = 'Fotografía guardada correctamente para {$a}';
$string['badge_hasphoto'] = 'Ya tiene foto';
$string['badge_suspended'] = 'Suspendido';
$string['warning_hasphoto'] = 'Este alumno ya tiene una fotografía de perfil';
$string['warning_cannotupdate'] = 'No tiene permiso para actualizar la fotografía de este alumno';
$string['label_idnumber'] = 'ID';
