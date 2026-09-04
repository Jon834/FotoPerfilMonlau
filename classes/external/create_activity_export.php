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

namespace local_profilephoto\external;

use cache;
use context;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\event\export_created;
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\export\activity_pdf_builder;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Build a "Control d'activitat" PDF for a cohort: an A4 landscape roster
 * with configurable pen-and-paper columns, for outings, workshops and
 * similar activities.
 *
 * Nothing about the activity (name, date, place, staff, chosen columns) is
 * stored: it only lives for the duration of this request, exactly like
 * `heading`/`density` already do in {@see create_export}. Only the
 * resulting PDF is written to a temp file and referenced by a one-time
 * download token, the same mechanism every other export type already uses.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_activity_export extends external_api {

    /** @var int Maximum number of custom (user-defined) columns. */
    private const MAX_CUSTOM_COLUMNS = 4;

    /** @var int Maximum length kept from a custom column's name. */
    private const MAX_CUSTOM_LABEL_LENGTH = 40;

    /** @var string[] Column keys reserved for the always-present Núm./Alumne columns. */
    private const RESERVED_KEYS = ['num', 'numero', 'alumne', 'alumnat', 'student', 'name'];

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cohortid' => new external_value(PARAM_INT, 'Cohort id'),
            'activity' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Activity name', VALUE_DEFAULT, ''),
                'date' => new external_value(PARAM_TEXT, 'Activity date, Y-m-d', VALUE_DEFAULT, ''),
                'place' => new external_value(PARAM_TEXT, 'Activity place', VALUE_DEFAULT, ''),
                'responsables' => new external_value(PARAM_TEXT, 'Staff in charge', VALUE_DEFAULT, ''),
            ]),
            'columns' => new external_multiple_structure(new external_single_structure([
                'key' => new external_value(PARAM_ALPHANUMEXT, 'Column key'),
                'label' => new external_value(PARAM_TEXT, 'Column label (custom columns only)', VALUE_DEFAULT, ''),
                'type' => new external_value(PARAM_ALPHA, 'checkbox | text', VALUE_DEFAULT, 'checkbox'),
            ]), 'Ordered list of extra columns, beyond the mandatory Núm./Alumne', VALUE_DEFAULT, []),
            'language' => new external_value(PARAM_ALPHA, 'ca | es | en', VALUE_DEFAULT, 'ca'),
            'stage' => new external_value(PARAM_ALPHA, 'fp | eso | batx | corporate', VALUE_DEFAULT, 'fp'),
            'showphotos' => new external_value(PARAM_BOOL, 'Show a photo/avatar next to each name', VALUE_DEFAULT, true),
            'showgeneralobs' => new external_value(PARAM_BOOL, 'Add a general incidents/notes box', VALUE_DEFAULT, true),
            'order' => new external_value(PARAM_ALPHA, 'lastname | firstname | cohort', VALUE_DEFAULT, 'lastname'),
        ]);
    }

    /**
     * Build the export.
     *
     * @param int $cohortid
     * @param array $activity
     * @param array $columns
     * @param string $language
     * @param string $stage
     * @param bool $showphotos
     * @param bool $showgeneralobs
     * @param string $order
     * @return array
     */
    public static function execute(
        int $cohortid,
        array $activity,
        array $columns = [],
        string $language = 'ca',
        string $stage = 'fp',
        bool $showphotos = true,
        bool $showgeneralobs = true,
        string $order = 'lastname'
    ): array {
        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cohortid' => $cohortid,
            'activity' => $activity,
            'columns' => $columns,
            'language' => $language,
            'stage' => $stage,
            'showphotos' => $showphotos,
            'showgeneralobs' => $showgeneralobs,
            'order' => $order,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:exportactivity', $context);
        require_sesskey();

        $cohort = $DB->get_record('cohort', ['id' => $params['cohortid']], 'id, name, contextid', IGNORE_MISSING);
        if (!$cohort) {
            throw new moodle_exception('error_activitycohortnotfound', 'local_profilephoto');
        }
        $cohortcontext = context::instance_by_id((int) $cohort->contextid, IGNORE_MISSING);
        if (!$cohortcontext || !has_capability('moodle/cohort:view', $cohortcontext)) {
            throw new moodle_exception('error_outofscope', 'local_profilephoto');
        }

        $extracolumns = self::normalize_columns($params['columns']);

        require_once($CFG->dirroot . '/cohort/lib.php');
        $members = cohort_get_members($cohort->id);

        $maxsync = (int) get_config('local_profilephoto', 'maxsyncexportusers') ?: 300;
        if (count($members) > $maxsync) {
            throw new moodle_exception('error_exporttoobig', 'local_profilephoto', '', $maxsync);
        }

        $activityoptions = [
            'name' => trim($params['activity']['name'] ?? ''),
            'date' => trim($params['activity']['date'] ?? ''),
            'place' => trim($params['activity']['place'] ?? ''),
            'responsables' => trim($params['activity']['responsables'] ?? ''),
        ];

        $order = in_array($params['order'], ['lastname', 'firstname', 'cohort'], true) ? $params['order'] : 'lastname';

        $built = activity_pdf_builder::build(
            array_values($members),
            format_string($cohort->name),
            $activityoptions,
            $extracolumns,
            [
                'language' => $params['language'],
                'stage' => $params['stage'],
                'showphotos' => $params['showphotos'],
                'showgeneralobs' => $params['showgeneralobs'],
                'order' => $order,
                'generatedby' => fullname($USER),
            ]
        );

        $token = bin2hex(random_bytes(20));
        $cache = cache::make('local_profilephoto', 'exports');
        $cache->set($token, [
            'path' => $built['path'],
            'operatorid' => (int) $USER->id,
            'filename' => $built['filename'],
        ]);

        logger::log('export_created', $USER->id, null, null, 'success',
            'activity:' . $cohort->id . ' (' . $built['count'] . ' files)');

        export_created::create([
            'context' => $context,
            'other' => ['count' => $built['count']],
        ])->trigger();

        return [
            'token' => $token,
            'filename' => $built['filename'],
            'count' => $built['count'],
        ];
    }

    /**
     * Validate and normalise the client-supplied column list.
     *
     * Standard columns always get their own fixed type, ignoring whatever the
     * client sent (only their presence/order/key is trusted). Anything else
     * is treated as a custom column: it must have an explicit checkbox/text
     * type and a non-empty label, and there can be at most
     * self::MAX_CUSTOM_COLUMNS of them.
     *
     * @param array $columns raw {key, label, type} entries from the client.
     * @return array normalised {key, label, type} entries.
     */
    private static function normalize_columns(array $columns): array {
        if (count($columns) > activity_pdf_builder::MAX_EXTRA_COLUMNS) {
            throw new moodle_exception('error_activitytoomanycolumns', 'local_profilephoto', '',
                activity_pdf_builder::MAX_EXTRA_COLUMNS);
        }

        $normalized = [];
        $seenkeys = [];
        $customcount = 0;

        foreach ($columns as $column) {
            $key = (string) $column['key'];
            if ($key === '' || in_array($key, self::RESERVED_KEYS, true) || isset($seenkeys[$key])) {
                throw new moodle_exception('error_activityinvalidcolumn', 'local_profilephoto');
            }
            $seenkeys[$key] = true;

            if (activity_pdf_builder::is_standard_column($key)) {
                $normalized[] = [
                    'key' => $key,
                    'label' => '',
                    'type' => activity_pdf_builder::standard_column_type($key),
                ];
                continue;
            }

            $customcount++;
            if ($customcount > self::MAX_CUSTOM_COLUMNS) {
                throw new moodle_exception('error_activitytoomanycustomcolumns', 'local_profilephoto', '',
                    self::MAX_CUSTOM_COLUMNS);
            }

            $type = (string) $column['type'];
            if (!in_array($type, ['checkbox', 'text'], true)) {
                throw new moodle_exception('error_activityinvalidcolumn', 'local_profilephoto');
            }

            $label = trim((string) $column['label']);
            if ($label === '') {
                throw new moodle_exception('error_activityinvalidcolumn', 'local_profilephoto');
            }
            $label = mb_substr($label, 0, self::MAX_CUSTOM_LABEL_LENGTH);

            $normalized[] = ['key' => $key, 'label' => $label, 'type' => $type];
        }

        return $normalized;
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'token' => new external_value(PARAM_ALPHANUM, 'One-time download token for export.php'),
            'filename' => new external_value(PARAM_FILE, 'Suggested download filename'),
            'count' => new external_value(PARAM_INT, 'Number of students included'),
        ]);
    }
}
