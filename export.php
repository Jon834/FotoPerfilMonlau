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
 * Export screen (Entrega 4): filter selection page, plus the one-time,
 * token-protected download endpoint for a ZIP or PDF already built by
 * classes/external/create_export.php or create_activity_export.php.
 *
 * The token is unguessable (20 random bytes) and single-use: it is
 * deleted from cache the moment it is consumed, whether the download
 * succeeds or the entry has already expired (encargo section 15:
 * "impedir acceso mediante URL pública predecible").
 *
 * ?token=...&preview=1 serves the exact same file inline (Content-Disposition:
 * inline via send_file(), instead of send_temp_file()'s hardcoded "attachment")
 * so the "Control d'activitat" screen can embed it in an <iframe> as a live
 * preview. Still governed by the same one-time token: each "Vista prèvia"
 * click asks for a fresh export/token first, so this never needs the token to
 * be reusable.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
// send_temp_file() lives in filelib.php, which setup.php only autoloads
// under a specific proxy configuration - not guaranteed on every request,
// so it must be required explicitly here.
require_once($CFG->libdir . '/filelib.php');

require_login();

$context = context_system::instance();
if (!has_capability('local/profilephoto:exportsession', $context)
        && !has_capability('local/profilephoto:exportall', $context)
        && !has_capability('local/profilephoto:exportactivity', $context)) {
    require_capability('local/profilephoto:exportsession', $context);
}

if (!get_config('local_profilephoto', 'enabled')) {
    throw new moodle_exception('error_plugindisabled', 'local_profilephoto');
}

$token = optional_param('token', '', PARAM_ALPHANUM);
$preview = optional_param('preview', 0, PARAM_BOOL);

if ($token !== '') {
    $cache = cache::make('local_profilephoto', 'exports');
    $entry = $cache->get($token);
    $cache->delete($token);

    if ($entry === false || !file_exists($entry['path'])) {
        throw new moodle_exception('error_exportexpired', 'local_profilephoto');
    }

    if ((int) $entry['operatorid'] !== (int) $USER->id && !has_capability('local/profilephoto:exportall', $context)) {
        throw new moodle_exception('error_outofscope', 'local_profilephoto');
    }

    \local_profilephoto\local\audit\logger::log('export_downloaded', $USER->id);
    \local_profilephoto\event\export_downloaded::create(['context' => $context])->trigger();

    if ($preview) {
        // Inline display for the embedded <iframe> preview - send_temp_file() always
        // forces "Content-Disposition: attachment", which a browser can only offer to
        // download, never render in place. send_file() leaves the temp file behind for
        // cleanup_exports.php's scheduled task to sweep, same as an abandoned download.
        send_file($entry['path'], $entry['filename'], 0, 0, false, false, 'application/pdf');
        // send_file() does not return.
    }

    send_temp_file($entry['path'], $entry['filename']);
    // send_temp_file() does not return.
}

$PAGE->set_url(new moodle_url('/local/profilephoto/export.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('export_title', 'local_profilephoto'));
$PAGE->set_heading(get_string('export_title', 'local_profilephoto'));

$PAGE->requires->js_call_amd('local_profilephoto/export', 'init');

$helpicons = [
    'filtertype' => $OUTPUT->help_icon('export_filtertype', 'local_profilephoto'),
    'target' => $OUTPUT->help_icon('export_target', 'local_profilephoto'),
    'roleset' => $OUTPUT->help_icon('export_roleset', 'local_profilephoto'),
    'filetype' => $OUTPUT->help_icon('export_filetype', 'local_profilephoto'),
    'filenamestrategy' => $OUTPUT->help_icon('export_filenamestrategy', 'local_profilephoto'),
    'density' => $OUTPUT->help_icon('export_density', 'local_profilephoto'),
    'stage' => $OUTPUT->help_icon('export_stage', 'local_profilephoto'),
    'language' => $OUTPUT->help_icon('export_language', 'local_profilephoto'),
    'heading' => $OUTPUT->help_icon('export_heading', 'local_profilephoto'),
    'activitycohort' => $OUTPUT->help_icon('activity_cohort', 'local_profilephoto'),
    'activitytemplate' => $OUTPUT->help_icon('activity_template', 'local_profilephoto'),
    'activitycolumns' => $OUTPUT->help_icon('activity_columns', 'local_profilephoto'),
    'activityorder' => $OUTPUT->help_icon('activity_order', 'local_profilephoto'),
];

$canexportactivity = has_capability('local/profilephoto:exportactivity', $context);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_profilephoto/export', [
    'helpicons' => $helpicons,
    'canexportactivity' => $canexportactivity,
]);
echo $OUTPUT->footer();
