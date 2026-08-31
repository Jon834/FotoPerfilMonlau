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
$string['settingspagetitle'] = 'Configuración captura de fotografías de perfil';

$string['event_picture_updated'] = 'Fotografía de perfil actualizada';
$string['event_session_started'] = 'Sesión fotográfica iniciada';
$string['event_session_completed'] = 'Sesión fotográfica completada';
$string['event_export_created'] = 'Exportación de fotografías generada';
$string['event_export_downloaded'] = 'Exportación de fotografías descargada';

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
$string['camera_switch_to_front'] = 'Usar cámara frontal';
$string['camera_switch_to_back'] = 'Usar cámara trasera';
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
$string['shortcut_skip'] = 'Saltar alumno (en sesión)';

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

$string['settings_exportheading'] = 'Exportación';
$string['settings_exportfilenamestrategy'] = 'Formato del nombre de archivo';
$string['settings_exportfilenamestrategy_desc'] = 'Campo usado para nombrar cada fotografía dentro del ZIP exportado.';
$string['settings_exportfallbackstrategy'] = 'Formato alternativo';
$string['settings_exportfallbackstrategy_desc'] = 'Campo usado cuando el formato principal está vacío para un alumno (por ejemplo, sin idnumber).';
$string['settings_maxsyncexportusers'] = 'Máximo de alumnos por exportación';
$string['settings_maxsyncexportusers_desc'] = 'Si el filtro seleccionado incluye más alumnos que este número, se pide acotar la selección en lugar de generar la exportación (esta entrega genera los ZIP de forma síncrona, sin tarea en segundo plano).';
$string['settings_exportretentionminutes'] = 'Retención de los ZIP temporales (minutos)';
$string['settings_exportretentionminutes_desc'] = 'Los archivos ZIP generados y no descargados se eliminan automáticamente pasado este tiempo mediante una tarea programada.';

$string['task_cleanup_exports'] = 'Eliminar exportaciones ZIP caducadas';

$string['session_filtertype'] = 'Ámbito de la sesión';
$string['session_filtertype_help'] = 'Elige "Curso" o "Cohorte" para generar automáticamente una cola con todos sus alumnos, en el orden que prefieras. Útil cuando vas a fotografiar a un grupo entero seguido. Si solo necesitas a un alumno concreto, no hace falta iniciar una sesión: usa el buscador de más abajo directamente.';
$string['session_filter_course'] = 'Curso';
$string['session_filter_cohort'] = 'Cohorte';
$string['session_order'] = 'Orden';
$string['session_order_help'] = 'Determina en qué orden aparecerán los alumnos de la cola al pulsar "Guardar y siguiente". Por defecto, por apellidos.';
$string['order_lastname'] = 'Apellidos';
$string['order_firstname'] = 'Nombre';
$string['order_email'] = 'Correo electrónico';
$string['order_idnumber'] = 'ID';
$string['order_username'] = 'Nombre de usuario';
$string['session_start'] = 'Iniciar sesión de fotos';
$string['session_end'] = 'Finalizar sesión';
$string['session_end_confirm'] = '¿Finalizar la sesión de fotos actual? Los alumnos ya fotografiados quedan guardados; los pendientes se podrán retomar iniciando una nueva sesión con el mismo curso o cohorte.';
$string['session_progress_template'] = '{$a->captured}/{$a->total} fotografiados — {$a->pending} pendientes';
$string['queue_skip'] = 'Saltar';
$string['queue_absent'] = 'Ausente';

$string['ux_step1'] = 'Busca un alumno, o inicia una sesión por curso/cohorte';
$string['ux_step2'] = 'Activa la cámara y haz la foto';
$string['ux_step3'] = 'Guarda: el siguiente alumno se carga solo';
$string['camera_placeholder'] = 'Pulsa «Activar cámara» para empezar';
$string['search_label_help'] = 'Escribe al menos 2 letras del nombre, apellidos, correo, usuario o ID del alumno. Los resultados aparecen solos, sin necesidad de pulsar nada.';

$string['export_title'] = 'Exportar fotografías';
$string['export_link'] = 'Exportar fotografías descargables';
$string['export_filtertype'] = 'Exportar por';
$string['export_filter_session'] = 'Sesión fotográfica';
$string['export_filter_course'] = 'Curso';
$string['export_filter_cohort'] = 'Cohorte';
$string['export_filenamestrategy'] = 'Nombrar archivos por';
$string['export_filetype'] = 'Tipo de exportación';
$string['export_filetype_zip'] = 'ZIP de fotografías';
$string['export_filetype_roster'] = 'PDF roster';
$string['export_filetype_orla'] = 'PDF orla';
$string['export_stage'] = 'Etapa';
$string['export_stage_fp'] = 'FP';
$string['export_stage_eso'] = 'ESO';
$string['export_stage_batx'] = 'Bachillerato';
$string['export_language'] = 'Idioma';
$string['export_language_ca'] = 'Catalán';
$string['export_language_es'] = 'Español';
$string['export_language_en'] = 'Inglés';
$string['export_heading'] = 'Texto extra del documento';
$string['export_heading_placeholder'] = 'Opcional: 2025-2026, grupo A…';
$string['export_generate'] = 'Generar exportación';
$string['export_generating'] = 'Generando la exportación…';
$string['export_ready'] = 'Exportación lista con {$a} fotografías. Descargando…';
$string['export_pdf_title_default'] = 'Listado de alumnos';

$string['error_exportexpired'] = 'Este enlace de descarga ha caducado o ya se ha utilizado. Genera la exportación de nuevo.';
$string['error_exporttoobig'] = 'La selección supera el máximo de {$a} alumnos para una exportación. Acota el filtro (curso, cohorte o sesión más pequeños).';
$string['error_invalidexportfilter'] = 'Filtro de exportación no válido.';
$string['error_invalidstatus'] = 'Estado de cola no válido.';

$string['privacy:metadata:session'] = 'Datos de cada sesión fotográfica abierta por un operador.';
$string['privacy:metadata:session:operatorid'] = 'El usuario que abrió la sesión.';
$string['privacy:metadata:session:filtertype'] = 'Si la sesión se generó a partir de un curso o de una cohorte.';
$string['privacy:metadata:session:filterdata'] = 'El identificador del curso o la cohorte usado como filtro.';
$string['privacy:metadata:session:timecreated'] = 'Cuándo se creó la sesión.';
$string['privacy:metadata:session_user'] = 'El estado de captura de cada alumno dentro de una sesión fotográfica.';
$string['privacy:metadata:session_user:userid'] = 'El alumno en cola.';
$string['privacy:metadata:session_user:capturedby'] = 'El operador que realizó la captura.';
$string['privacy:metadata:session_user:status'] = 'Estado de la captura (pendiente, capturado, saltado, ausente, error).';
$string['privacy:metadata:session_user:timecaptured'] = 'Cuándo se capturó la fotografía.';
$string['privacy:metadata:log'] = 'Registro de auditoría de las acciones realizadas con el plugin.';
$string['privacy:metadata:log:operatorid'] = 'El usuario que realizó la acción.';
$string['privacy:metadata:log:targetuserid'] = 'El alumno afectado por la acción, si corresponde.';
$string['privacy:metadata:log:action'] = 'El tipo de acción registrada.';
$string['privacy:metadata:log:ipaddress'] = 'La dirección IP desde la que se realizó la acción.';
$string['privacy:metadata:log:timecreated'] = 'Cuándo se registró la acción.';
$string['privacy:metadata:corefiles'] = 'La fotografía de perfil en sí se almacena íntegramente mediante el componente "user" del núcleo de Moodle, no por este plugin.';
$string['privacy:path:sessions'] = 'Captura de fotografías de perfil/Sesiones';
$string['privacy:path:queueentries'] = 'Captura de fotografías de perfil/Cola';
$string['privacy:path:logs'] = 'Captura de fotografías de perfil/Registro de auditoría';
