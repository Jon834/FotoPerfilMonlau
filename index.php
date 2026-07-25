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
 * Main capture screen.
 *
 * Entrega 2: search + select + live camera capture (getUserMedia), with
 * the Entrega 1 manual file input kept as an automatic fallback for
 * browsers/contexts without camera support. See docs/technical-design.md.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/profilephoto:view', $context);

if (!get_config('local_profilephoto', 'enabled')) {
    throw new moodle_exception('error_plugindisabled', 'local_profilephoto');
}

$PAGE->set_url(new moodle_url('/local/profilephoto/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_profilephoto'));
$PAGE->set_heading(get_string('pluginname', 'local_profilephoto'));

$PAGE->requires->js_call_amd('local_profilephoto/capture', 'init', [[
    'countdownEnabled' => (bool) get_config('local_profilephoto', 'enablecountdown'),
    'countdownSeconds' => (int) get_config('local_profilephoto', 'countdownseconds'),
    'shortcutsEnabled' => (bool) get_config('local_profilephoto', 'enableshortcuts'),
    // Behat cannot reliably drive a real/fake webcam; force the
    // deterministic manual file-upload path during automated tests only
    // (encargo section 27). This flag has no effect outside Behat runs.
    'forceManualCapture' => defined('BEHAT_SITE_RUNNING'),
]]);

$canexport = has_capability('local/profilephoto:exportsession', $context)
    || has_capability('local/profilephoto:exportall', $context);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_profilephoto/index', [
    'canexport' => $canexport,
    'exporturl' => (new moodle_url('/local/profilephoto/export.php'))->out(false),
]);

echo $OUTPUT->footer();
