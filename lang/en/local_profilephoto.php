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

$string['event_picture_updated'] = 'Profile picture updated';

$string['privacy:metadata:null_reason'] = 'This plugin does not store any personal data of its own: the captured photo is processed in memory and saved only through Moodle official mechanism (the "user" component), already covered by core_user own privacy provider. Temporary draft files are removed through Moodle standard mechanisms.';

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
