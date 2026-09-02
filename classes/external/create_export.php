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
use context_course;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\event\export_created;
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\export\filename_strategy;
use local_profilephoto\local\export\pdf_builder;
use local_profilephoto\local\export\zip_builder;
use local_profilephoto\local\session\manager;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Build a ZIP export of profile photos matching a filter (encargo section 15).
 *
 * Synchronous only: for very large selections the operator is asked to
 * narrow the filter (local_profilephoto/maxsyncexportusers) rather than
 * this entrega implementing a background export task.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_export extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtertype' => new external_value(PARAM_ALPHA, 'session | course | cohort'),
            'filterid' => new external_value(PARAM_INT, 'sessionid, courseid or cohortid, matching filtertype'),
            'filenamestrategy' => new external_value(PARAM_ALPHA, 'Primary filename strategy', VALUE_DEFAULT, ''),
            'fallbackstrategy' => new external_value(PARAM_ALPHA, 'Fallback filename strategy', VALUE_DEFAULT, ''),
            'exporttype' => new external_value(PARAM_ALPHANUM, 'zip | orla | grid6 | directory | signatures',
                VALUE_DEFAULT, 'zip'),
            'language' => new external_value(PARAM_ALPHA, 'ca | es | en', VALUE_DEFAULT, 'ca'),
            'stage' => new external_value(PARAM_ALPHA, 'fp | eso | batx', VALUE_DEFAULT, 'fp'),
            'heading' => new external_value(PARAM_TEXT, 'Optional heading text', VALUE_DEFAULT, ''),
            'density' => new external_value(PARAM_ALPHA, 'compact | normal | large', VALUE_DEFAULT, 'normal'),
        ]);
    }

    /**
     * Build the export.
     *
     * @param string $filtertype
     * @param int $filterid
     * @param string $filenamestrategy
     * @param string $fallbackstrategy
     * @return array
     */
    public static function execute(
        string $filtertype,
        int $filterid,
        string $filenamestrategy = '',
        string $fallbackstrategy = '',
        string $exporttype = 'zip',
        string $language = 'ca',
        string $stage = 'fp',
        string $heading = '',
        string $density = 'normal'
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'filtertype' => $filtertype,
            'filterid' => $filterid,
            'filenamestrategy' => $filenamestrategy,
            'fallbackstrategy' => $fallbackstrategy,
            'exporttype' => $exporttype,
            'language' => $language,
            'stage' => $stage,
            'heading' => $heading,
            'density' => $density,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_sesskey();

        $strategy = in_array($params['filenamestrategy'], filename_strategy::STRATEGIES, true)
            ? $params['filenamestrategy']
            : (get_config('local_profilephoto', 'exportfilenamestrategy') ?: 'idnumber');
        $fallback = in_array($params['fallbackstrategy'], filename_strategy::STRATEGIES, true)
            ? $params['fallbackstrategy']
            : (get_config('local_profilephoto', 'exportfallbackstrategy') ?: 'username');

        $sessionid = null;

        if ($params['filtertype'] === 'session') {
            $session = manager::get_session($params['filterid']);
            if ((int) $session->operatorid === (int) $USER->id) {
                require_capability('local/profilephoto:exportsession', $context);
            } else {
                require_capability('local/profilephoto:exportall', $context);
            }
            $sessionid = $session->id;
            $userids = array_map(static function($item) {
                return (int) $item->userid;
            }, array_filter(manager::get_queue($session->id), static function($item) {
                return $item->status === 'captured';
            }));
        } else if ($params['filtertype'] === 'course') {
            require_capability('local/profilephoto:exportall', $context);
            $course = $DB->get_record('course', ['id' => $params['filterid']], 'id', MUST_EXIST);
            $enrolled = get_enrolled_users(context_course::instance($course->id), '', 0, 'u.id');
            $userids = array_map('intval', array_keys($enrolled));
        } else if ($params['filtertype'] === 'cohort') {
            require_capability('local/profilephoto:exportall', $context);
            $members = $DB->get_records('cohort_members', ['cohortid' => $params['filterid']], '', 'userid');
            $userids = array_map('intval', array_keys($members));
        } else {
            throw new moodle_exception('error_invalidexportfilter', 'local_profilephoto');
        }

        $maxsync = (int) get_config('local_profilephoto', 'maxsyncexportusers') ?: 300;
        if (count($userids) > $maxsync) {
            throw new moodle_exception('error_exporttoobig', 'local_profilephoto', '', $maxsync);
        }

        $layout = in_array($params['exporttype'], ['roster', 'orla', 'grid6', 'directory', 'signatures'], true)
            ? $params['exporttype'] : 'zip';
        if ($layout === 'zip') {
            $built = zip_builder::build($userids, $strategy, $fallback, $sessionid);
        } else {
            $title = self::resolve_export_title($params['filtertype'], $params['filterid']);
            $built = pdf_builder::build($userids, $title, $layout, [
                'language' => $params['language'],
                'stage' => $params['stage'],
                'heading' => $params['heading'],
                'density' => $params['density'],
                'generatedby' => fullname($USER),
            ]);
        }

        $token = bin2hex(random_bytes(20));
        $cache = cache::make('local_profilephoto', 'exports');
        $cache->set($token, [
            'path' => $built['path'],
            'operatorid' => (int) $USER->id,
            'filename' => $built['filename'],
        ]);

        logger::log('export_created', $USER->id, null, $sessionid, 'success',
            $params['filtertype'] . ':' . $params['filterid'] . ' (' . $built['count'] . ' files)');

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
     * Resolve the visible title for PDF roster exports.
     *
     * @param string $filtertype
     * @param int $filterid
     * @return string
     */
    private static function resolve_export_title(string $filtertype, int $filterid): string {
        global $DB;

        if ($filtertype === 'course') {
            $course = $DB->get_record('course', ['id' => $filterid], 'id, fullname', MUST_EXIST);
            return format_string($course->fullname);
        }

        if ($filtertype === 'cohort') {
            $cohort = $DB->get_record('cohort', ['id' => $filterid], 'id, name', MUST_EXIST);
            return format_string($cohort->name);
        }

        $session = manager::get_session($filterid);
        $filterdata = json_decode($session->filterdata ?? '{}', true);
        if (($session->filtertype ?? '') === 'course') {
            $courseid = (int) ($filterdata['courseid'] ?? 0);
            if ($courseid) {
                $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname', MUST_EXIST);
                return format_string($course->fullname);
            }
        }
        if (($session->filtertype ?? '') === 'cohort') {
            $cohortid = (int) ($filterdata['cohortid'] ?? 0);
            if ($cohortid) {
                $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'id, name', MUST_EXIST);
                return format_string($cohort->name);
            }
        }

        return get_string('export_pdf_title_default', 'local_profilephoto');
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
            'count' => new external_value(PARAM_INT, 'Number of photos included'),
        ]);
    }
}
