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
 * Catalan strings for local_profilephoto.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Captura de fotografies de perfil';
$string['profilephoto:view'] = 'Accedir a la pantalla de captura de fotografies';
$string['profilephoto:searchusers'] = 'Cercar alumnes a la pantalla de captura';
$string['profilephoto:capture'] = 'Capturar fotografies d\'alumnes';
$string['profilephoto:updatepicture'] = 'Actualitzar la fotografia de perfil oficial d\'un alumne';
$string['profilephoto:replaceexisting'] = 'Substituir una fotografia de perfil ja existent';
$string['profilephoto:viewidentifiers'] = 'Veure identificadors personals (correu, idnumber, usuari)';
$string['profilephoto:viewallusers'] = 'Veure i fotografiar alumnes de tot el sistema, sense restricció d\'àmbit';
$string['profilephoto:exportsession'] = 'Exportar les fotografies d\'una sessió pròpia';
$string['profilephoto:exportall'] = 'Exportar fotografies de qualsevol sessió';
$string['profilephoto:managesessions'] = 'Gestionar sessions fotogràfiques';
$string['profilephoto:configure'] = 'Configurar el connector de captura de fotografies';
$string['profilephoto:viewlogs'] = 'Veure el registre d\'auditoria de fotografies';
$string['profilephoto:restoreprevious'] = 'Restaurar la fotografia anterior d\'un alumne';

$string['settings_enabled'] = 'Activar el connector';
$string['settings_enabled_desc'] = 'Si es desactiva, la pantalla de captura no estarà disponible per a ningú excepte administradors.';
$string['settings_targetsize'] = 'Resolució final (píxels)';
$string['settings_targetsize_desc'] = 'Mida (amplada = alçada) a la qual es redimensiona la fotografia abans d\'enviar-la al mecanisme oficial de Moodle. Moodle genera a més la seva pròpia miniatura de 512×512, per la qual cosa es recomana un valor igual o superior.';
$string['settings_jpegquality'] = 'Qualitat JPEG';
$string['settings_jpegquality_desc'] = 'Qualitat de compressió JPEG, de 0 a 100.';
$string['settings_maxsourcebytes'] = 'Mida màxima de la imatge capturada (bytes)';
$string['settings_maxsourcebytes_desc'] = 'Les imatges rebudes que superin aquesta mida es rebutgen abans de processar-se.';
$string['settings_maxsearchresults'] = 'Nombre màxim de resultats de cerca';
$string['settings_maxsearchresults_desc'] = 'Límit superior de resultats retornats per cada cerca, independentment del que sol·liciti el client.';
$string['opencapturescreen'] = 'Obrir pantalla de captura';

$string['event_picture_updated'] = 'Fotografia de perfil actualitzada';

$string['privacy:metadata:null_reason'] = 'Aquest connector no emmagatzema dades personals pròpies: la fotografia capturada es processa en memòria i es desa únicament mitjançant el mecanisme oficial de Moodle (component "user"), ja cobert pel proveïdor de privadesa del nucli. Els fitxers temporals d\'esborrany s\'eliminen mitjançant els mecanismes estàndard de Moodle.';

$string['error_emptyimage'] = 'No s\'ha rebut cap imatge.';
$string['error_imagetoolarge'] = 'La imatge rebuda supera la mida màxima permesa.';
$string['error_imagetoosmall'] = 'La imatge rebuda és massa petita.';
$string['error_invalidimage'] = 'El fitxer rebut no és una imatge vàlida.';
$string['error_unsupportedmimetype'] = 'Format d\'imatge no admès. Utilitzeu JPEG o PNG.';
$string['error_processingfailed'] = 'No s\'ha pogut processar la imatge al servidor.';
$string['error_outofscope'] = 'No teniu permís per operar sobre aquest alumne.';
$string['error_replacenotallowed'] = 'Aquest alumne ja té una fotografia de perfil i no teniu permís per substituir-la.';
$string['error_duplicatesubmission'] = 'Aquesta fotografia ja s\'ha desat. Eviteu enviar la mateixa captura dues vegades.';
$string['error_plugindisabled'] = 'La captura de fotografies de perfil està desactivada en aquest lloc.';

$string['search_label'] = 'Cercar alumne';
$string['search_placeholder'] = 'Nom, correu, usuari o idnumber…';
$string['search_noresults'] = 'No s\'ha trobat cap alumne.';
$string['no_student_selected'] = 'Seleccioneu un alumne per començar.';
$string['save_and_next'] = 'Desar i següent';
$string['save_success'] = 'Fotografia desada correctament per a {$a}';
$string['badge_hasphoto'] = 'Ja té foto';
$string['badge_suspended'] = 'Suspès';
$string['warning_hasphoto'] = 'Aquest alumne ja té una fotografia de perfil';
$string['warning_cannotupdate'] = 'No teniu permís per actualitzar la fotografia d\'aquest alumne';
$string['label_idnumber'] = 'ID';

$string['camera_unsupported'] = 'Aquest navegador o aquesta connexió no permeten accedir a la càmera (cal HTTPS). Pugeu una fotografia de prova manualment.';
$string['camera_select'] = 'Seleccionar càmera';
$string['camera_activate'] = 'Activar càmera';
$string['take_photo'] = 'Fer foto';
$string['repeat_photo'] = 'Repetir';
$string['manual_fallback_desc'] = 'No es pot fer servir la càmera en directe en aquest dispositiu o navegador. Pugeu una fotografia manualment.';
$string['manual_fallback_label'] = 'Fotografia';
$string['shortcuts_help_title'] = 'Dreceres de teclat';
$string['key_space'] = 'Espai';
$string['key_enter'] = 'Enter';
$string['key_esc'] = 'Esc';
$string['shortcut_capture'] = 'Fer fotografia';
$string['shortcut_save'] = 'Desar i següent';
$string['shortcut_repeat'] = 'Repetir fotografia';
$string['shortcut_search'] = 'Anar al cercador';
$string['shortcut_cancel'] = 'Cancel·lar la previsualització';

$string['camera_error_insecure'] = 'Aquest lloc no fa servir HTTPS: el navegador no permet accedir a la càmera. Pugeu una fotografia manualment.';
$string['camera_error_permission'] = 'Heu denegat l\'accés a la càmera. Reviseu els permisos del navegador per a aquest lloc, o pugeu una fotografia manualment.';
$string['camera_error_notfound'] = 'No s\'ha detectat cap càmera en aquest dispositiu.';
$string['camera_error_inuse'] = 'La càmera està sent utilitzada per una altra aplicació.';
$string['camera_error_generic'] = 'No s\'ha pogut accedir a la càmera.';

$string['settings_enableshortcuts'] = 'Activar dreceres de teclat';
$string['settings_enableshortcuts_desc'] = 'Permet fer servir Espai, Enter, R, B i Esc per operar la pantalla de captura sense ratolí (o amb un disparador USB que emuli aquestes tecles).';
$string['settings_enablecountdown'] = 'Activar compte enrere';
$string['settings_enablecountdown_desc'] = 'Si està activat, en prémer «Fer foto» s\'inicia un compte enrere abans de capturar, en lloc de capturar immediatament. Desactivat per defecte.';
$string['settings_countdownseconds'] = 'Durada del compte enrere (segons)';
$string['settings_countdownseconds_desc'] = 'Només s\'aplica si el compte enrere està activat.';
