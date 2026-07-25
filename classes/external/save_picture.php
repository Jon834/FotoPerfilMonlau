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
use context_system;
use context_user;
use core\user as core_user_class;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\event\picture_updated;
use local_profilephoto\local\access\scope;
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\image\processor;
use local_profilephoto\local\image\updater;
use local_profilephoto\local\session\manager;
use moodle_exception;
use Throwable;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirm a captured photo and set it as the target user's official
 * Moodle profile picture (encargo section 9 - the single most critical
 * function in this plugin).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_picture extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Target user id'),
            'imagedata' => new external_value(PARAM_RAW, 'Base64-encoded JPEG/PNG captured frame'),
            'operationid' => new external_value(
                PARAM_ALPHANUM,
                'Client-generated idempotency token, one per capture confirmation',
                VALUE_DEFAULT,
                ''
            ),
            'sessionid' => new external_value(PARAM_INT, 'Photography session this capture belongs to, 0 if none',
                VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Validate, process and apply the captured photo.
     *
     * @param int $userid
     * @param string $imagedata
     * @param string $operationid
     * @param int $sessionid
     * @return array
     */
    public static function execute(int $userid, string $imagedata, string $operationid = '', int $sessionid = 0): array {
        global $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'imagedata' => $imagedata,
            'operationid' => $operationid,
            'sessionid' => $sessionid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:capture', $context);
        require_capability('local/profilephoto:updatepicture', $context);
        require_sesskey();

        self::guard_against_duplicate_submission($USER->id, $params['userid'], $params['operationid']);

        $session = null;
        if ($params['sessionid'] > 0) {
            $session = manager::get_session($params['sessionid']);
            manager::require_owner($session, $USER->id);
        }

        try {
            $user = core_user_class::get_user($params['userid'], 'id, firstname, lastname, picture, suspended, deleted',
                MUST_EXIST);

            // Re-check authorization on the actual target right before writing,
            // never trust that the earlier search/get_user check still applies.
            if (!scope::can_operate_on_user($USER->id, $user)) {
                throw new moodle_exception('error_outofscope', 'local_profilephoto');
            }

            $wasreplace = ((int) $user->picture) > 0;
            if ($wasreplace && !has_capability('local/profilephoto:replaceexisting', $context)) {
                throw new moodle_exception('error_replacenotallowed', 'local_profilephoto');
            }

            $maxsourcebytes = (int) get_config('local_profilephoto', 'maxsourcebytes') ?: 8 * 1024 * 1024;
            $targetsize = (int) get_config('local_profilephoto', 'targetsize') ?: 500;
            $jpegquality = (int) get_config('local_profilephoto', 'jpegquality') ?: 88;

            // Reject grossly oversized base64 payloads before spending memory decoding them.
            if (strlen($params['imagedata']) > (int) ($maxsourcebytes * 1.4)) {
                throw new moodle_exception('error_imagetoolarge', 'local_profilephoto');
            }

            $binary = base64_decode($params['imagedata'], true);
            if ($binary === false) {
                throw new moodle_exception('error_invalidimage', 'local_profilephoto');
            }

            $jpeg = processor::process($binary, $maxsourcebytes, $targetsize, $jpegquality);

            $changed = updater::update_user_picture($USER->id, $user->id, $jpeg);
        } catch (Throwable $e) {
            logger::log('picture_updated', $USER->id, $params['userid'], $session->id ?? null, 'error', $e->getMessage());
            if ($session !== null) {
                manager::set_status($session->id, $params['userid'], 'error', $e->getMessage());
            }
            throw $e;
        }

        picture_updated::create([
            'objectid' => $user->id,
            'relateduserid' => $user->id,
            'context' => context_user::instance($user->id),
            'other' => ['replaced' => $wasreplace],
        ])->trigger();

        logger::log('picture_updated', $USER->id, $user->id, $session->id ?? null, 'success');

        if ($session !== null) {
            manager::mark_captured($session->id, $user->id, $USER->id);
        }

        $freshuser = core_user_class::get_user($user->id, 'id, firstname, lastname, picture, imagealt', MUST_EXIST);
        $picture = new user_picture($freshuser);
        $picture->size = 300;

        return [
            'success' => true,
            'changed' => $changed,
            'userid' => (int) $user->id,
            'fullname' => fullname($user),
            'pictureurl' => $picture->get_url($PAGE)->out(false),
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Always true when no exception was thrown'),
            'changed' => new external_value(PARAM_BOOL, 'Whether the picture actually changed'),
            'userid' => new external_value(PARAM_INT, 'Target user id'),
            'fullname' => new external_value(PARAM_NOTAGS, 'Target user full name, for the confirmation message'),
            'pictureurl' => new external_value(PARAM_URL, 'New profile picture URL'),
        ]);
    }

    /**
     * Reject a resubmission of the same client-generated operation id.
     *
     * Cheap, self-contained double-submit guard (encargo sections 12/20): a
     * short-lived session cache entry keyed by operator + target +
     * operation id, independent of the session/queue tables.
     *
     * @param int $operatorid
     * @param int $targetuserid
     * @param string $operationid empty string disables the guard (caller chose not to send one).
     * @throws moodle_exception
     */
    private static function guard_against_duplicate_submission(int $operatorid, int $targetuserid, string $operationid): void {
        if ($operationid === '') {
            return;
        }

        $cache = cache::make('local_profilephoto', 'recentsaves');
        $key = sha1($operatorid . ':' . $targetuserid . ':' . $operationid);

        if ($cache->get($key) !== false) {
            throw new moodle_exception('error_duplicatesubmission', 'local_profilephoto');
        }

        $cache->set($key, time());
    }
}
