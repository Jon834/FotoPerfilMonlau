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

$string['task_cleanup_exports'] = 'Delete expired export ZIPs';

$string['session_filtertype'] = 'Session scope';
$string['session_filtertype_help'] = 'Choose "Course" or "Cohort" to automatically build a queue with all its students, in whichever order you prefer. Useful when photographing a whole group in a row. If you only need one specific student, you don\'t need to start a session: use the search box below directly.';
$string['session_filter_course'] = 'Course';
$string['session_filter_cohort'] = 'Cohort';
$string['session_order'] = 'Order';
$string['session_order_help'] = 'Determines the order students appear in the queue when you click "Save and next". Last name by default.';
$string['order_lastname'] = 'Last name';
$string['order_firstname'] = 'First name';
$string['order_email'] = 'Email';
$string['order_idnumber'] = 'ID';
$string['order_username'] = 'Username';
$string['session_start'] = 'Start photo session';
$string['session_end'] = 'End session';
$string['session_end_confirm'] = 'End the current photo session? Students already photographed stay saved; pending ones can be resumed by starting a new session with the same course or cohort.';
$string['session_progress_template'] = '{$a->captured}/{$a->total} captured — {$a->pending} pending';
$string['queue_skip'] = 'Skip';
$string['queue_absent'] = 'Absent';

$string['ux_step1'] = 'Search for a student, or start a session by course/cohort';
$string['ux_step2'] = 'Activate the camera and take the photo';
$string['ux_step3'] = 'Save: the next student loads on its own';
$string['camera_placeholder'] = 'Click "Activate camera" to get started';
$string['search_label_help'] = 'Type at least 2 letters of the student\'s name, surname, email, username or ID. Results appear on their own, no need to press anything.';

$string['export_title'] = 'Export photos';
$string['export_link'] = 'Export downloadable photos';
$string['export_filtertype'] = 'Export by';
$string['export_filter_session'] = 'Photography session';
$string['export_filter_course'] = 'Course';
$string['export_filter_cohort'] = 'Cohort';
$string['export_target'] = 'Selection';
$string['export_target_none'] = 'Nothing selected';
$string['export_search'] = 'Type to search…';
$string['export_intro'] = 'Generate class photo boards, rosters and photo downloads from a cohort, a course or a photography session.';
$string['export_section_what'] = 'What to export';
$string['export_section_document'] = 'Document format';
$string['export_section_files'] = 'Files';
$string['export_roleset'] = 'Course participants';
$string['export_roleset_students'] = 'Students only';
$string['export_roleset_studentsteachers'] = 'Students and teachers';
$string['export_roleset_all'] = 'All participants';
$string['export_roleset_help'] = 'Only applies when exporting by course. Filters who is included based on their role:

* **Students only** (default): users with a student-type role.
* **Students and teachers**: also includes the teacher and editing teacher roles.
* **All participants**: anyone enrolled in the course.

Cohorts always export all of their members.';
$string['export_filtertype_help'] = 'Source of the students included in the export:

* **Cohort**: a site cohort (groups defined by administration).
* **Course**: users enrolled in a course.
* **Photography session**: a capture session you ran yourself.';
$string['export_target_help'] = 'Type to filter the list. Only the cohorts, courses or sessions you have access to are shown.';
$string['export_filetype_help'] = 'What gets generated:

* **Compact orla**: PDF with many students per page (photo and name).
* **Card orla**: PDF with each student on a card.
* **Student directory**: PDF with photo, name and email.
* **Signature sheet**: PDF with photo, name and a box to sign.
* **Photo ZIP**: compressed file with each student photo separately.';
$string['export_filenamestrategy_help'] = 'Only applies to the photo ZIP. Sets how each student file is named. If the chosen field is empty, the username is used.';
$string['export_density_help'] = 'PDF only. Adjusts how many students fit per page and the photo size.';
$string['export_stage_help'] = 'PDF only. Pick the education stage to apply the matching orla template and style.';
$string['export_language_help'] = 'Language of the document fixed texts (titles and headers). Does not affect student names.';
$string['export_heading_help'] = 'Optional text shown under the document title. For example the academic year or the group.';
$string['export_filenamestrategy'] = 'Name files by';
$string['export_filetype'] = 'Export format';
$string['export_filetype_zip'] = 'Photo ZIP';
$string['export_filetype_roster'] = 'Photo list';
$string['export_filetype_orla'] = 'Card orla';
$string['export_filetype_grid6'] = 'Compact orla';
$string['export_filetype_directory'] = 'Student directory';
$string['export_filetype_signatures'] = 'Signature sheet';
$string['export_density'] = 'Density';
$string['export_density_normal'] = 'Normal';
$string['export_density_compact'] = 'Compact (more per page)';
$string['export_density_large'] = 'Large (bigger photos)';
$string['export_stage'] = 'Stage';
$string['export_stage_fp'] = 'Vocational';
$string['export_stage_eso'] = 'Secondary';
$string['export_stage_batx'] = 'Upper secondary';
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
$string['error_exporttoobig'] = 'The selection exceeds the maximum of {$a} students for one export. Narrow the filter (a smaller course, cohort or session).';
$string['error_invalidexportfilter'] = 'Invalid export filter.';
$string['error_invalidstatus'] = 'Invalid queue status.';

$string['privacy:metadata:session'] = 'Data about each photography session opened by an operator.';
$string['privacy:metadata:session:operatorid'] = 'The user who opened the session.';
$string['privacy:metadata:session:filtertype'] = 'Whether the session was built from a course or a cohort.';
$string['privacy:metadata:session:filterdata'] = 'The course or cohort id used as the filter.';
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
