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
 * English strings for local_profilephoto.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Profile photo capture';
$string['profilephoto:view'] = 'Access the photo capture screen';
$string['profilephoto:searchusers'] = 'Search students on the capture screen';
$string['profilephoto:capture'] = 'Capture student photos';
$string['profilephoto:updatepicture'] = 'Update a student official profile picture';
$string['profilephoto:replaceexisting'] = 'Replace an already existing profile picture';
$string['profilephoto:viewidentifiers'] = 'View personal identifiers (email, idnumber, username)';
$string['profilephoto:viewallusers'] = 'View and photograph students site-wide, with no scope restriction';
$string['profilephoto:exportsession'] = 'Export photos from a session of your own';
$string['profilephoto:exportactivity'] = 'Generate an Activity control / Custom sheet (printable roster of a group or class)';
$string['profilephoto:exportall'] = 'Export photos from any session';
$string['profilephoto:managesessions'] = 'Manage photo sessions';
$string['profilephoto:configure'] = 'Configure the profile photo capture plugin';
$string['profilephoto:viewlogs'] = 'View the photo capture audit log';
$string['profilephoto:restoreprevious'] = 'Restore a student previous photo';

$string['settings_enabled'] = 'Enable the plugin';
$string['settings_enabled_desc'] = 'When disabled, the capture screen is unavailable to everyone except administrators.';
$string['settings_targetsize'] = 'Final resolution (pixels)';
$string['settings_targetsize_desc'] = 'Size (width = height) the photo is resized to before being handed to Moodle official picture pipeline. Moodle additionally generates its own 512x512 thumbnail, so a value at or above that is recommended.';
$string['settings_jpegquality'] = 'JPEG quality';
$string['settings_jpegquality_desc'] = 'JPEG compression quality, 0 to 100.';
$string['settings_maxsourcebytes'] = 'Maximum size of the captured image (bytes)';
$string['settings_maxsourcebytes_desc'] = 'Incoming images larger than this are rejected before processing.';
$string['settings_maxsearchresults'] = 'Maximum search results';
$string['settings_maxsearchresults_desc'] = 'Upper bound on results returned per search, regardless of what the client requests.';
$string['opencapturescreen'] = 'Open capture screen';
$string['settingspagetitle'] = 'Profile photo capture settings';

$string['event_picture_updated'] = 'Profile picture updated';
$string['event_session_started'] = 'Photography session started';
$string['event_session_completed'] = 'Photography session completed';
$string['event_export_created'] = 'Photo export created';
$string['event_export_downloaded'] = 'Photo export downloaded';

$string['error_emptyimage'] = 'No image was received.';
$string['error_imagetoolarge'] = 'The received image exceeds the maximum allowed size.';
$string['error_imagetoosmall'] = 'The received image is too small.';
$string['error_invalidimage'] = 'The received file is not a valid image.';
$string['error_unsupportedmimetype'] = 'Unsupported image format. Use JPEG or PNG.';
$string['error_processingfailed'] = 'The image could not be processed on the server.';
$string['error_outofscope'] = 'You are not allowed to operate on this student.';
$string['error_replacenotallowed'] = 'This student already has a profile picture and you are not allowed to replace it.';
$string['error_duplicatesubmission'] = 'This photo has already been saved. Avoid sending the same capture twice.';
$string['error_plugindisabled'] = 'Profile photo capture is disabled on this site.';

$string['search_label'] = 'Search student';
$string['search_placeholder'] = 'Name, email, username or idnumber…';
$string['search_noresults'] = 'No students found.';
$string['no_student_selected'] = 'Select a student to get started.';
$string['save_and_next'] = 'Save and next';
$string['save_success'] = 'Photo saved successfully for {$a}';
$string['badge_hasphoto'] = 'Already has a photo';
$string['badge_suspended'] = 'Suspended';
$string['warning_hasphoto'] = 'This student already has a profile picture';
$string['warning_cannotupdate'] = 'You are not allowed to update this student picture';
$string['label_idnumber'] = 'ID';

$string['camera_unsupported'] = 'This browser or connection does not allow camera access (HTTPS is required). Upload a test photo manually instead.';
$string['camera_select'] = 'Select camera';
$string['camera_switch_to_front'] = 'Use front camera';
$string['camera_switch_to_back'] = 'Use back camera';
$string['camera_activate'] = 'Activate camera';
$string['take_photo'] = 'Take photo';
$string['repeat_photo'] = 'Retake';
$string['manual_fallback_desc'] = 'Live camera capture is not available on this device or browser. Upload a photo manually.';
$string['manual_fallback_label'] = 'Photo';
$string['shortcuts_help_title'] = 'Keyboard shortcuts';
$string['key_space'] = 'Space';
$string['key_enter'] = 'Enter';
$string['key_esc'] = 'Esc';
$string['shortcut_capture'] = 'Take photo';
$string['shortcut_save'] = 'Save and next';
$string['shortcut_repeat'] = 'Retake photo';
$string['shortcut_search'] = 'Focus the search box';
$string['shortcut_cancel'] = 'Cancel preview';
$string['shortcut_skip'] = 'Skip student (in a session)';

$string['camera_error_insecure'] = 'This site does not use HTTPS: the browser will not allow camera access. Upload a photo manually.';
$string['camera_error_permission'] = 'You denied camera access. Check this site\'s browser permissions, or upload a photo manually.';
$string['camera_error_notfound'] = 'No camera was detected on this device.';
$string['camera_error_inuse'] = 'The camera is being used by another application.';
$string['camera_error_generic'] = 'The camera could not be accessed.';

$string['settings_enableshortcuts'] = 'Enable keyboard shortcuts';
$string['settings_enableshortcuts_desc'] = 'Allows using Space, Enter, R, B and Esc to operate the capture screen without a mouse (or with a USB trigger emulating those keys).';
$string['settings_enablecountdown'] = 'Enable countdown';
$string['settings_enablecountdown_desc'] = 'When enabled, clicking "Take photo" starts a countdown before capturing, instead of capturing immediately. Disabled by default.';
$string['settings_countdownseconds'] = 'Countdown duration (seconds)';
$string['settings_countdownseconds_desc'] = 'Only applies when the countdown is enabled.';

$string['settings_exportheading'] = 'Export';
$string['settings_exportfilenamestrategy'] = 'Filename format';
$string['settings_exportfilenamestrategy_desc'] = 'Field used to name each photo inside the exported ZIP.';
$string['settings_exportfallbackstrategy'] = 'Fallback format';
$string['settings_exportfallbackstrategy_desc'] = 'Field used when the primary format is empty for a student (e.g. no idnumber).';
$string['settings_maxsyncexportusers'] = 'Maximum students per export';
$string['settings_maxsyncexportusers_desc'] = 'If the selected filter includes more students than this, the operator is asked to narrow it instead of generating the export (this delivery builds ZIPs synchronously, with no background task).';
$string['settings_exportretentionminutes'] = 'Temporary ZIP retention (minutes)';
$string['settings_exportretentionminutes_desc'] = 'Generated ZIPs that are never downloaded are automatically deleted after this time by a scheduled task.';
$string['settings_monlauimagesbase'] = 'Monlau images base URL';
$string['settings_monlauimagesbase_desc'] = 'The theme_monlau "customimages" folder the orla and Activity control logos are read from (monlau_fp.jpg, monlau_eso.jpg, monlau_corp.jpg, monlaugroup.svg). Moodle changes the last path segment every time an image is re-uploaded in the theme; if the logos stop appearing, update this URL.';

$string['task_cleanup_exports'] = 'Delete expired export ZIPs';

$string['session_filtertype'] = 'Session scope';
$string['session_filtertype_help'] = 'Choose "Module or subject" or "Group or class" to automatically build a queue with all its students, in whichever order you prefer. Useful when photographing a whole group in a row. If you only need one specific student, you don\'t need to start a session: use the search box below directly.';
$string['session_filter_course'] = 'Module or subject';
$string['session_filter_cohort'] = 'Group or class';
$string['session_order'] = 'Order';
$string['session_order_help'] = 'Determines the order students appear in the queue when you click "Save and next". Last name by default.';
$string['order_lastname'] = 'Last name';
$string['order_firstname'] = 'First name';
$string['order_email'] = 'Email';
$string['order_idnumber'] = 'ID';
$string['order_username'] = 'Username';
$string['session_start'] = 'Start photo session';
$string['session_end'] = 'End session';
$string['session_end_confirm'] = 'End the current photo session? Students already photographed stay saved; pending ones can be resumed by starting a new session with the same module, subject, group or class.';
$string['session_progress_template'] = '{$a->captured}/{$a->total} captured — {$a->pending} pending';
$string['queue_skip'] = 'Skip';
$string['queue_absent'] = 'Absent';

$string['ux_step1'] = 'Search for a student, or start a session by module/subject or group/class';
$string['ux_step2'] = 'Activate the camera and take the photo';
$string['ux_step3'] = 'Save: the next student loads on its own';
$string['camera_placeholder'] = 'Click "Activate camera" to get started';
$string['search_label_help'] = 'Type at least 2 letters of the student\'s name, surname, email, username or ID. Results appear on their own, no need to press anything.';

$string['export_title'] = 'Class photo & roster generator';
$string['export_link'] = 'Class photo & roster generator';
$string['export_filtertype'] = 'Export by';
$string['export_filter_session'] = 'Photography session';
$string['export_filter_course'] = 'Module or subject';
$string['export_filter_cohort'] = 'Group or class';
$string['export_target'] = 'Selection';
$string['export_target_none'] = 'Nothing selected';
$string['export_search'] = 'Type to search…';
$string['export_intro'] = 'Generate class photo boards, rosters and photo downloads from a group or class, a module or subject, or a photography session.';
$string['export_section_what'] = 'What to export';
$string['export_section_document'] = 'Document settings';
$string['export_section_files'] = 'Files';
$string['export_roleset'] = 'Module or subject participants';
$string['export_roleset_students'] = 'Students only';
$string['export_roleset_studentsteachers'] = 'Students and teachers';
$string['export_roleset_all'] = 'All participants';
$string['export_roleset_help'] = 'Only applies when exporting by module or subject. Filters who is included based on their role:

* **Students only** (default): users with a student-type role.
* **Students and teachers**: also includes the teacher and editing teacher roles.
* **All participants**: anyone enrolled.

Groups and classes always export all of their members.';
$string['export_filtertype_help'] = 'Source of the students included in the export:

* **Group or class**: a group or class at the centre (a Moodle *cohort*, defined by administration).
* **Module or subject**: users enrolled in a Moodle *course*.
* **Photography session**: a capture session you ran yourself.';
$string['export_target_help'] = 'Type to filter the list. Only the groups, classes, modules, subjects or sessions you have access to are shown.';
$string['export_filetype_help'] = 'What gets generated:

* **Compact orla**: PDF with many students per page (photo and name).
* **Card orla**: PDF with each student on a card.
* **Student directory**: PDF with photo, name and email.
* **Signature sheet**: PDF with photo, name and a box to sign.
* **Photo ZIP**: compressed file with each student photo separately.
* **Activity control / Custom sheet**: landscape PDF for outings, workshops and activities of a group or class, with configurable columns (attendance, authorisation, transport...) to mark by hand.';
$string['export_filenamestrategy_help'] = 'Only applies to the photo ZIP. Sets how each student file is named. If the chosen field is empty, the username is used.';
$string['export_density_help'] = 'PDF only. Adjusts how many students fit per page and the photo size.';
$string['export_stage_help'] = 'PDF only. Pick the education stage to apply the matching orla template and style.';
$string['export_language_help'] = 'Language of the document fixed texts (titles and headers). Does not affect student names.';
$string['export_heading_help'] = 'Optional text shown under the document title. For example the academic year or the group.';
$string['export_filenamestrategy'] = 'Name files by';
$string['export_filetype'] = 'Document type';
$string['export_filetype_zip'] = 'Photo ZIP';
$string['export_filetype_roster'] = 'Photo list';
$string['export_filetype_orla'] = 'Card orla';
$string['export_filetype_grid6'] = 'Compact orla';
$string['export_filetype_directory'] = 'Student directory';
$string['export_filetype_signatures'] = 'Signature sheet';
$string['export_filetype_grid6_desc'] = 'Many students per page, with photo and name. For the classroom wall.';
$string['export_filetype_orla_desc'] = 'Each student on their own card, with full name.';
$string['export_filetype_directory_desc'] = 'A list with each student\'s photo, name and email.';
$string['export_filetype_signatures_desc'] = 'Photo, name and a box to sign. For attendance checks.';
$string['export_filetype_zip_desc'] = 'The photos as separate files in a ZIP, with a manifest.csv mapping each one.';
$string['export_filetype_activity_desc'] = 'Portrait or landscape PDF, or Excel, with columns to fill in by hand or digitally (attendance, authorisation, transport…). For outings and workshops.';
$string['export_format'] = 'Output format';
$string['export_format_pdf'] = 'PDF';
$string['export_format_excel'] = 'Excel (.xlsx)';
$string['export_density'] = 'Density';
$string['export_density_normal'] = 'Normal';
$string['export_density_compact'] = 'Compact (more per page)';
$string['export_density_large'] = 'Large (bigger photos)';
$string['export_stage'] = 'Stage';
$string['export_stage_fp'] = 'Vocational';
$string['export_stage_eso'] = 'Secondary / Upper secondary';
$string['export_stage_batx'] = 'Secondary / Upper secondary';
$string['export_stage_corporate'] = 'Corporate';
$string['export_stage_monlaugroup'] = 'Monlau Group';
$string['export_language'] = 'Language';
$string['export_language_ca'] = 'Catalan';
$string['export_language_es'] = 'Spanish';
$string['export_language_en'] = 'English';
$string['export_heading'] = 'Extra document text';
$string['export_heading_placeholder'] = 'Optional: 2025-2026, group A…';
$string['export_generate'] = 'Generate export';
$string['export_generating'] = 'Generating the export…';
$string['export_ready'] = 'Export ready with {$a} photos. Downloading…';
$string['export_pdf_title_default'] = 'Student list';

$string['error_exportexpired'] = 'This download link has expired or has already been used. Generate the export again.';
$string['error_exporttoobig'] = 'The selection exceeds the maximum of {$a} students for one export. Narrow the filter (a smaller module, subject, group, class or session).';
$string['error_invalidexportfilter'] = 'Invalid export filter.';
$string['error_invalidstatus'] = 'Invalid queue status.';
$string['error_activitycohortnotfound'] = 'The selected group or class does not exist.';
$string['error_phpspreadsheetmissing'] = 'This Moodle does not have the PhpSpreadsheet library available (needed for the Excel export). Contact the server administrator, or generate the PDF instead while this is resolved.';
$string['error_activitytoomanycolumns'] = 'You have selected too many columns to generate a readable document. Reduce the number of columns or remove one (maximum {$a} beyond No. and Student).';
$string['error_activitytoomanycustomcolumns'] = 'Only up to {$a} custom columns can be added.';
$string['error_activityinvalidcolumn'] = 'One of the selected columns is not valid.';

$string['export_filetype_activity'] = 'Activity control / Custom sheet';
$string['export_section_activity_cohort'] = 'Group or class';
$string['export_section_activity_info'] = 'Activity';
$string['export_section_activity_template'] = 'Template';
$string['export_section_activity_columns'] = 'Columns';
$string['export_section_activity_options'] = 'Options';

$string['activity_cohort'] = 'Group or class';
$string['activity_cohort_help'] = 'A group or class at the centre (a Moodle cohort); its current members are read for the roster. The PDF always reflects the group\'s current membership: no list of your own is saved. Only groups with students enrolled in your courses are listed, unless you have site-wide access.';
$string['activity_cohort_membercount'] = '{$a} students';
$string['activity_name'] = 'Activity name';
$string['activity_name_placeholder'] = 'e.g. CosmoCaixa visit';
$string['activity_date'] = 'Date';
$string['activity_place'] = 'Place';
$string['activity_place_placeholder'] = 'e.g. Barcelona';
$string['activity_responsables'] = 'Staff in charge';
$string['activity_responsables_placeholder'] = 'e.g. Jonatan Núñez, Marta Solé';
$string['activity_template'] = 'Template';
$string['activity_template_help'] = 'Pre-selects a common set of columns. You can adjust it freely afterwards; "Custom" leaves the current selection untouched.';
$string['activity_template_sortida'] = 'Outing';
$string['activity_template_activitat'] = 'Activity';
$string['activity_template_taller'] = 'Workshop';
$string['activity_template_personalitzat'] = 'Custom';
$string['activity_columns'] = 'Columns';
$string['activity_columns_warning'] = 'This combination of columns would not fit legibly. Remove one, or set Notes to short mode.';
$string['activity_columns_help'] = 'Choose the columns shown in the PDF, beyond No. and Student (always present). Maximum {$a} extra columns, to keep the document readable.';
$string['activity_col_present'] = 'Present';
$string['activity_col_autoritzacio'] = 'Authorisation';
$string['activity_col_transport'] = 'Transport';
$string['activity_col_epi'] = 'PPE';
$string['activity_col_material'] = 'Material';
$string['activity_col_hora'] = 'Time';
$string['activity_col_email'] = 'Email';
$string['activity_col_observacions'] = 'Notes';
$string['activity_obs_width'] = 'Notes column width';
$string['activity_obs_width_normal'] = 'Normal (all the free space)';
$string['activity_obs_width_short'] = 'Short (half, leaves room for more columns)';
$string['activity_addcolumn'] = 'Add column';
$string['activity_customcolumn_name'] = 'Name';
$string['activity_customcolumn_type'] = 'Type';
$string['activity_customcolumn_type_checkbox'] = 'Checkbox';
$string['activity_customcolumn_type_text'] = 'Short text';
$string['activity_customcolumn_remove'] = 'Remove this column';
$string['activity_customcolumn_placeholder'] = 'e.g. T-shirt';
$string['activity_reorder'] = 'Column order';
$string['activity_showphotos'] = 'Show photo / avatar';
$string['activity_showphotos_yes'] = 'Yes';
$string['activity_showphotos_no'] = 'No';
$string['activity_showgeneralobs'] = 'Show general notes box';
$string['activity_order'] = 'Sort students by';
$string['activity_order_help'] = 'Order students appear in on the PDF. "Group or class order" keeps the order returned by the system, without sorting.';
$string['activity_order_lastname'] = 'Surname / First name';
$string['activity_order_firstname'] = 'First name / Surname';
$string['activity_order_cohort'] = 'Group or class order';
$string['activity_density_large'] = 'Large (bigger photos, ~20 students/page)';
$string['activity_preview'] = 'Preview';
$string['activity_preview_placeholder'] = 'Click "Preview" to see how the document will look here.';
$string['activity_generate'] = 'Generate document';
$string['activity_generating'] = 'Generating the document…';
$string['activity_nocohort'] = 'Select a group or class to continue.';
$string['activity_format'] = 'Output format';
$string['activity_format_help'] = 'Excel does not include photos or page orientation; checkbox or text columns are generated as blank, bordered cells to fill in on a computer.';
$string['activity_format_pdf'] = 'PDF';
$string['activity_format_excel'] = 'Excel (.xlsx)';
$string['activity_orientation'] = 'Page orientation';
$string['activity_orientation_landscape'] = 'Landscape (A4)';
$string['activity_orientation_portrait'] = 'Portrait (A4)';

$string['privacy:metadata:session'] = 'Data about each photography session opened by an operator.';
$string['privacy:metadata:session:operatorid'] = 'The user who opened the session.';
$string['privacy:metadata:session:filtertype'] = 'Whether the session was built from a module/subject or a group/class.';
$string['privacy:metadata:session:filterdata'] = 'The module/subject or group/class id used as the filter.';
$string['privacy:metadata:session:timecreated'] = 'When the session was created.';
$string['privacy:metadata:session_user'] = 'The capture status of each student within a photography session.';
$string['privacy:metadata:session_user:userid'] = 'The queued student.';
$string['privacy:metadata:session_user:capturedby'] = 'The operator who performed the capture.';
$string['privacy:metadata:session_user:status'] = 'Capture status (pending, captured, skipped, absent, error).';
$string['privacy:metadata:session_user:timecaptured'] = 'When the photo was captured.';
$string['privacy:metadata:log'] = 'Audit trail of actions performed with the plugin.';
$string['privacy:metadata:log:operatorid'] = 'The user who performed the action.';
$string['privacy:metadata:log:targetuserid'] = 'The student affected by the action, if any.';
$string['privacy:metadata:log:action'] = 'The type of action logged.';
$string['privacy:metadata:log:ipaddress'] = 'The IP address the action was performed from.';
$string['privacy:metadata:log:timecreated'] = 'When the action was logged.';
$string['privacy:metadata:corefiles'] = 'The profile picture itself is stored entirely through Moodle core\'s "user" component, not by this plugin.';
$string['privacy:path:sessions'] = 'Profile photo capture/Sessions';
$string['privacy:path:queueentries'] = 'Profile photo capture/Queue';
$string['privacy:path:logs'] = 'Profile photo capture/Audit log';

$string['internalcredit'] = 'Built by';
$string['internalcreditbrand'] = 'IT MONLAU';
