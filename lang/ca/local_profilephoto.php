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
$string['profilephoto:exportactivity'] = 'Generar un Control d\'activitat/Full personalitzable (llistat imprimible d\'un grup o classe)';
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
$string['settingspagetitle'] = 'Configuració captura de fotografies de perfil';

$string['event_picture_updated'] = 'Fotografia de perfil actualitzada';
$string['event_session_started'] = 'Sessió fotogràfica iniciada';
$string['event_session_completed'] = 'Sessió fotogràfica completada';
$string['event_export_created'] = 'Exportació de fotografies generada';
$string['event_export_downloaded'] = 'Exportació de fotografies descarregada';

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
$string['camera_switch_to_front'] = 'Utilitzar càmera frontal';
$string['camera_switch_to_back'] = 'Utilitzar càmera posterior';
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
$string['shortcut_skip'] = 'Saltar alumne (en sessió)';

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

$string['settings_exportheading'] = 'Exportació';
$string['settings_exportfilenamestrategy'] = 'Format del nom de fitxer';
$string['settings_exportfilenamestrategy_desc'] = 'Camp usat per anomenar cada fotografia dins del ZIP exportat.';
$string['settings_exportfallbackstrategy'] = 'Format alternatiu';
$string['settings_exportfallbackstrategy_desc'] = 'Camp usat quan el format principal és buit per a un alumne (per exemple, sense idnumber).';
$string['settings_maxsyncexportusers'] = 'Màxim d\'alumnes per exportació';
$string['settings_maxsyncexportusers_desc'] = 'Si el filtre seleccionat inclou més alumnes que aquest número, es demana acotar la selecció en lloc de generar l\'exportació (aquest lliurament genera els ZIP de forma síncrona, sense tasca en segon pla).';
$string['settings_exportretentionminutes'] = 'Retenció dels ZIP temporals (minuts)';
$string['settings_exportretentionminutes_desc'] = 'Els fitxers ZIP generats i no descarregats s\'eliminen automàticament passat aquest temps mitjançant una tasca programada.';
$string['settings_monlauimagesbase'] = 'URL base de les imatges Monlau';
$string['settings_monlauimagesbase_desc'] = 'Carpeta de «customimages» del tema Monlau d\'on es prenen els logotips de les orles i del Control d\'activitat (monlau_fp.jpg, monlau_eso.jpg, monlau_corp.jpg, monlaugroup.svg). L\'últim segment de la ruta canvia cada vegada que es torna a pujar una imatge al tema; si els logotips deixen de sortir, actualitza aquí aquesta URL.';

$string['task_cleanup_exports'] = 'Eliminar exportacions ZIP caducades';

$string['session_filtertype'] = 'Àmbit de la sessió';
$string['session_filtertype_help'] = 'Tria "Mòdul o Assignatura" o "Grup o Classe" per generar automàticament una cua amb tots els seus alumnes, en l\'ordre que prefereixis. Útil quan fotografies un grup sencer seguit. Si només necessites un alumne concret, no cal iniciar una sessió: fes servir el cercador de més avall directament.';
$string['session_filter_course'] = 'Mòdul o Assignatura';
$string['session_filter_cohort'] = 'Grup o Classe';
$string['session_order'] = 'Ordre';
$string['session_order_help'] = 'Determina en quin ordre apareixeran els alumnes de la cua en prémer "Desar i següent". Per defecte, per cognoms.';
$string['order_lastname'] = 'Cognoms';
$string['order_firstname'] = 'Nom';
$string['order_email'] = 'Correu electrònic';
$string['order_idnumber'] = 'ID';
$string['order_username'] = 'Nom d\'usuari';
$string['session_start'] = 'Iniciar sessió de fotos';
$string['session_end'] = 'Finalitzar sessió';
$string['session_end_confirm'] = 'Finalitzar la sessió de fotos actual? Els alumnes ja fotografiats queden desats; els pendents es podran reprendre iniciant una nova sessió amb el mateix mòdul, assignatura, grup o classe.';
$string['session_progress_template'] = '{$a->captured}/{$a->total} fotografiats — {$a->pending} pendents';
$string['queue_skip'] = 'Saltar';
$string['queue_absent'] = 'Absent';

$string['ux_step1'] = 'Cerca un alumne, o inicia una sessió per mòdul/assignatura o grup/classe';
$string['ux_step2'] = 'Activa la càmera i fes la foto';
$string['ux_step3'] = 'Desa: el següent alumne es carrega sol';
$string['camera_placeholder'] = 'Prem «Activar càmera» per començar';
$string['search_label_help'] = 'Escriu almenys 2 lletres del nom, cognoms, correu, usuari o ID de l\'alumne. Els resultats apareixen sols, sense necessitat de prémer res.';

$string['export_title'] = 'Generador d\'orles i llistats';
$string['export_link'] = 'Generador d\'orles i llistats';
$string['export_filtertype'] = 'Exportar per';
$string['export_filter_session'] = 'Sessió fotogràfica';
$string['export_filter_course'] = 'Mòdul o Assignatura';
$string['export_filter_cohort'] = 'Grup o Classe';
$string['export_target'] = 'Selecció';
$string['export_target_none'] = 'Res seleccionat';
$string['export_search'] = 'Escriu per cercar…';
$string['export_intro'] = 'Genera orles, llistats i descàrregues de fotos a partir d\'un grup o classe, un mòdul o assignatura, o una sessió fotogràfica.';
$string['export_section_what'] = 'Què exportar';
$string['export_section_document'] = 'Ajustaments del document';
$string['export_section_files'] = 'Fitxers';
$string['export_roleset'] = 'Participants del mòdul o assignatura';
$string['export_roleset_students'] = 'Només estudiants';
$string['export_roleset_studentsteachers'] = 'Estudiants i professorat';
$string['export_roleset_all'] = 'Tots els participants';
$string['export_roleset_help'] = 'Només s\'aplica quan exportes per mòdul o assignatura. Filtra qui s\'inclou segons el seu rol:

* **Només estudiants** (per defecte): usuaris amb un rol de tipus estudiant.
* **Estudiants i professorat**: també inclou els rols de professor i professor editor.
* **Tots els participants**: qualsevol persona matriculada.

Els grups o classes exporten sempre tots els seus membres.';
$string['export_filtertype_help'] = 'Origen de l\'alumnat que s\'inclou a l\'exportació:

* **Grup o Classe**: un grup o classe del centre (una *cohort* de Moodle, definida per administració).
* **Mòdul o Assignatura**: l\'alumnat matriculat en un *curs* de Moodle.
* **Sessió fotogràfica**: una sessió de captura que hagis fet tu.';
$string['export_target_help'] = 'Escriu per filtrar la llista. Només apareixen els grups, classes, mòduls, assignatures o sessions als quals tens accés.';
$string['export_filetype_help'] = 'Què es genera:

* **Orla compacta**: PDF amb molts alumnes per pàgina (foto i nom).
* **Orla en targetes**: PDF amb cada alumne en una targeta.
* **Directori d\'alumnes**: PDF amb foto, nom i correu.
* **Full de signatures**: PDF amb foto, nom i una casella per signar.
* **ZIP de fotografies**: fitxer comprimit amb la foto de cada alumne per separat.
* **Control d\'activitat/Full personalitzable**: PDF horitzontal per a sortides, tallers i activitats d\'un grup o classe, amb columnes configurables (assistència, autorització, transport...) per marcar amb bolígraf.';
$string['export_filenamestrategy_help'] = 'Només s\'aplica al ZIP de fotografies. Determina com s\'anomena el fitxer de cada alumne. Si la dada triada és buida, s\'utilitza el nom d\'usuari.';
$string['export_density_help'] = 'Només per als PDF. Ajusta quants alumnes caben per pàgina i la mida de les fotos.';
$string['export_stage_help'] = 'Només per als PDF. Selecciona l\'etapa educativa per aplicar la plantilla i l\'estil d\'orla corresponent.';
$string['export_language_help'] = 'Idioma dels textos fixos del document (títols i capçaleres). No afecta els noms dels alumnes.';
$string['export_heading_help'] = 'Text opcional que apareix sota el títol del document. Per exemple, el curs acadèmic o el grup.';
$string['export_filenamestrategy'] = 'Anomenar fitxers per';
$string['export_filetype'] = 'Tipus de document';
$string['export_filetype_zip'] = 'ZIP de fotografies';
$string['export_filetype_roster'] = 'Llistat fotogràfic';
$string['export_filetype_orla'] = 'Orla en targetes';
$string['export_filetype_grid6'] = 'Orla compacta';
$string['export_filetype_directory'] = 'Directori d’alumnes';
$string['export_filetype_signatures'] = 'Full de signatures';
$string['export_filetype_grid6_desc'] = 'Molts alumnes per pàgina, amb foto i nom. Per al tauler de l\'aula.';
$string['export_filetype_orla_desc'] = 'Cada alumne a la seva targeta, amb el nom complet.';
$string['export_filetype_directory_desc'] = 'Llistat amb foto, nom i correu de cada alumne.';
$string['export_filetype_signatures_desc'] = 'Foto, nom i una casella per signar. Per controlar l\'assistència.';
$string['export_filetype_zip_desc'] = 'Les fotos soltes en un ZIP, amb un manifest.csv que indica de qui és cadascuna.';
$string['export_filetype_activity_desc'] = 'PDF (vertical o apaïsat) o Excel, amb columnes per marcar a mà o digitalment (assistència, autorització, transport…). Per a sortides i tallers.';
$string['export_density'] = 'Densitat';
$string['export_density_normal'] = 'Normal';
$string['export_density_compact'] = 'Compacta (més per pàgina)';
$string['export_density_large'] = 'Gran (fotos més grans)';
$string['export_stage'] = 'Etapa';
$string['export_stage_fp'] = 'FP';
$string['export_stage_eso'] = 'ESO / Batxillerat';
$string['export_stage_batx'] = 'ESO / Batxillerat';
$string['export_stage_corporate'] = 'Corporate';
$string['export_stage_monlaugroup'] = 'Monlau Group';
$string['export_language'] = 'Idioma';
$string['export_language_ca'] = 'Català';
$string['export_language_es'] = 'Espanyol';
$string['export_language_en'] = 'Anglès';
$string['export_heading'] = 'Text extra del document';
$string['export_heading_placeholder'] = 'Opcional: 2025-2026, grup A…';
$string['export_generate'] = 'Generar exportació';
$string['export_generating'] = 'Generant l’exportació…';
$string['export_ready'] = 'Exportació preparada amb {$a} fotografies. Descarregant…';
$string['export_pdf_title_default'] = 'Llistat d’alumnes';

$string['error_exportexpired'] = 'Aquest enllaç de descàrrega ha caducat o ja s\'ha utilitzat. Genera l\'exportació de nou.';
$string['error_exporttoobig'] = 'La selecció supera el màxim de {$a} alumnes per a una exportació. Acota el filtre (un mòdul, assignatura, grup, classe o sessió més petits).';
$string['error_invalidexportfilter'] = 'Filtre d\'exportació no vàlid.';
$string['error_invalidstatus'] = 'Estat de cua no vàlid.';
$string['error_activitycohortnotfound'] = 'El grup o classe seleccionat no existeix.';
$string['error_phpspreadsheetmissing'] = 'Aquest Moodle no té disponible la llibreria PhpSpreadsheet (necessària per exportar a Excel). Contacta amb l’administrador del servidor, o genera el document en PDF mentre es resol.';
$string['error_activitytoomanycolumns'] = 'Has seleccionat massa columnes per generar un document llegible. Redueix el nombre de columnes o elimina alguna columna (màxim {$a} a més de Núm. i Alumne).';
$string['error_activitytoomanycustomcolumns'] = 'Només es poden afegir fins a {$a} columnes personalitzades.';
$string['error_activityinvalidcolumn'] = 'Alguna de les columnes seleccionades no és vàlida.';

$string['export_filetype_activity'] = 'Control d’activitat/Full personalitzable';
$string['export_section_activity_cohort'] = 'Grup o Classe';
$string['export_section_activity_info'] = 'Activitat';
$string['export_section_activity_template'] = 'Plantilla';
$string['export_section_activity_columns'] = 'Columnes';
$string['export_section_activity_options'] = 'Opcions';

$string['activity_cohort'] = 'Grup o Classe';
$string['activity_cohort_help'] = 'Grup o classe del centre (una cohort de Moodle) de la qual s\'obtenen els membres actuals per al llistat. El PDF sempre reflecteix la composició actual del grup: no es desa cap llista pròpia. Només apareixen els grups amb alumnes matriculats als teus cursos, tret que tinguis accés global.';
$string['activity_cohort_membercount'] = '{$a} alumnes';
$string['activity_name'] = 'Nom de l’activitat';
$string['activity_name_placeholder'] = 'p. ex. Visita CosmoCaixa';
$string['activity_date'] = 'Data';
$string['activity_place'] = 'Lloc';
$string['activity_place_placeholder'] = 'p. ex. Barcelona';
$string['activity_responsables'] = 'Responsables';
$string['activity_responsables_placeholder'] = 'p. ex. Jonatan Núñez, Marta Solé';
$string['activity_template'] = 'Plantilla';
$string['activity_template_help'] = 'Preselecciona un conjunt habitual de columnes. Pots ajustar-lo lliurement després de triar-la; «Personalitzat» no modifica la selecció actual.';
$string['activity_template_sortida'] = 'Sortida';
$string['activity_template_activitat'] = 'Activitat';
$string['activity_template_taller'] = 'Taller';
$string['activity_template_personalitzat'] = 'Personalitzat';
$string['activity_columns'] = 'Columnes';
$string['activity_columns_warning'] = 'Aquesta combinació de columnes no hi cabria de manera llegible. Treu-ne alguna, o posa «Observacions» en mode curt.';
$string['activity_columns_help'] ='Tria les columnes que apareixeran al PDF, a més de Núm. i Alumne (sempre presents). Màxim {$a} columnes addicionals perquè el document es mantingui llegible.';
$string['activity_col_present'] = 'Present';
$string['activity_col_autoritzacio'] = 'Autorització';
$string['activity_col_transport'] = 'Transport';
$string['activity_col_epi'] = 'EPI';
$string['activity_col_material'] = 'Material';
$string['activity_col_hora'] = 'Hora';
$string['activity_col_email'] = 'Correu';
$string['activity_col_observacions'] = 'Observacions';
$string['activity_obs_width'] = 'Amplada d’Observacions';
$string['activity_obs_width_normal'] = 'Normal (tot l’espai lliure)';
$string['activity_obs_width_short'] = 'Curta (la meitat, deixa lloc per a més columnes)';
$string['activity_addcolumn'] = 'Afegir columna';
$string['activity_customcolumn_name'] = 'Nom';
$string['activity_customcolumn_type'] = 'Tipus';
$string['activity_customcolumn_type_checkbox'] = 'Casella de verificació';
$string['activity_customcolumn_type_text'] = 'Text curt';
$string['activity_customcolumn_remove'] = 'Elimina aquesta columna';
$string['activity_customcolumn_placeholder'] = 'p. ex. Samarreta';
$string['activity_reorder'] = 'Ordre de les columnes';
$string['activity_showphotos'] = 'Mostrar fotografia / avatar';
$string['activity_showphotos_yes'] = 'Sí';
$string['activity_showphotos_no'] = 'No';
$string['activity_showgeneralobs'] = 'Mostrar observacions generals';
$string['activity_order'] = 'Ordenar alumnes';
$string['activity_order_help'] = 'Ordre en què apareixen els alumnes al PDF. «Ordre del grup o classe» manté l\'ordre retornat pel sistema, sense reordenar.';
$string['activity_order_lastname'] = 'Cognoms / Nom';
$string['activity_order_firstname'] = 'Nom / Cognoms';
$string['activity_order_cohort'] = 'Ordre del grup o classe';
$string['activity_density_large'] = 'Gran (fotos més grans, ~20 alumnes/pàgina)';
$string['activity_preview'] = 'Vista prèvia';
$string['activity_preview_placeholder'] = 'Prem «Vista prèvia» per veure aquí com quedarà el document.';
$string['activity_generate'] = 'Generar document';
$string['activity_generating'] = 'Generant el document…';
$string['activity_nocohort'] = 'Selecciona un grup o classe per continuar.';
$string['activity_format'] = 'Format de sortida';
$string['activity_format_help'] = 'L’Excel no inclou fotografies ni orientació de pàgina; les columnes de casella o text es generen com a cel·les buides amb vora per emplenar-les des de l’ordinador.';
$string['activity_format_pdf'] = 'PDF';
$string['activity_format_excel'] = 'Excel (.xlsx)';
$string['activity_orientation'] = 'Orientació de pàgina';
$string['activity_orientation_landscape'] = 'Apaïsat (A4)';
$string['activity_orientation_portrait'] = 'Vertical (A4)';

$string['privacy:metadata:session'] = 'Dades de cada sessió fotogràfica oberta per un operador.';
$string['privacy:metadata:session:operatorid'] = 'L\'usuari que va obrir la sessió.';
$string['privacy:metadata:session:filtertype'] = 'Si la sessió es va generar a partir d\'un mòdul/assignatura o d\'un grup/classe.';
$string['privacy:metadata:session:filterdata'] = 'L\'identificador del mòdul/assignatura o del grup/classe usat com a filtre.';
$string['privacy:metadata:session:timecreated'] = 'Quan es va crear la sessió.';
$string['privacy:metadata:session_user'] = 'L\'estat de captura de cada alumne dins d\'una sessió fotogràfica.';
$string['privacy:metadata:session_user:userid'] = 'L\'alumne en cua.';
$string['privacy:metadata:session_user:capturedby'] = 'L\'operador que va realitzar la captura.';
$string['privacy:metadata:session_user:status'] = 'Estat de la captura (pendent, capturat, saltat, absent, error).';
$string['privacy:metadata:session_user:timecaptured'] = 'Quan es va capturar la fotografia.';
$string['privacy:metadata:log'] = 'Registre d\'auditoria de les accions realitzades amb el connector.';
$string['privacy:metadata:log:operatorid'] = 'L\'usuari que va realitzar l\'acció.';
$string['privacy:metadata:log:targetuserid'] = 'L\'alumne afectat per l\'acció, si escau.';
$string['privacy:metadata:log:action'] = 'El tipus d\'acció registrada.';
$string['privacy:metadata:log:ipaddress'] = 'L\'adreça IP des de la qual es va realitzar l\'acció.';
$string['privacy:metadata:log:timecreated'] = 'Quan es va registrar l\'acció.';
$string['privacy:metadata:corefiles'] = 'La fotografia de perfil en si s\'emmagatzema íntegrament mitjançant el component "user" del nucli de Moodle, no per aquest connector.';
$string['privacy:path:sessions'] = 'Captura de fotografies de perfil/Sessions';
$string['privacy:path:queueentries'] = 'Captura de fotografies de perfil/Cua';
$string['privacy:path:logs'] = 'Captura de fotografies de perfil/Registre d\'auditoria';

$string['internalcredit'] = 'Built by';
$string['internalcreditbrand'] = 'IT MONLAU';
