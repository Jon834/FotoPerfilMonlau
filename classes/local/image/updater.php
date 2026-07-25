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

namespace local_profilephoto\local\image;

use context_user;
use core\user as core_user_class;

defined('MOODLE_INTERNAL') || die();

/**
 * Applies an already-validated JPEG as a user's official Moodle profile picture.
 *
 * This is the ONLY place in the plugin allowed to touch the user's picture.
 * It reuses Moodle's own picture-update pipeline instead of writing to
 * mdl_user.picture or moodledata directly (encargo section 9):
 *
 *  1. The processed JPEG is stored as a single file in a fresh draft file
 *     area belonging to the OPERATOR's user context - exactly what the
 *     'imagefile' filemanager element on user/editadvanced_form.php would
 *     produce when someone uploads a picture by hand.
 *  2. \core\user::update_picture() is called with that draft itemid. That
 *     is the same call user/editadvanced.php makes when saving the "Edit
 *     profile" form: it moves the file via file_save_draft_area_files(),
 *     regenerates the f1/f2/f3 icon variants via process_new_icon()
 *     (public/lib/gdlib.php), and updates mdl_user.picture.
 *
 * Verified against MOODLE_501_STABLE source on 2026-07-25 - see
 * docs/technical-design.md section 4 for the full trace.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class updater {

    /**
     * Replace a user's profile picture using Moodle's official mechanism.
     *
     * Caller is responsible for all authorization checks (capability +
     * scope) - this method assumes they have already been done and does
     * not repeat them, since it has no notion of "operator vs target".
     *
     * @param int $operatorid user whose draft file area will stage the upload.
     * @param int $targetuserid user whose profile picture is being replaced.
     * @param string $jpegdata already-processed, square JPEG binary data.
     * @return bool true if the picture actually changed, false otherwise
     *              (Moodle returns false when the new image is identical
     *              to produce no unnecessary picture-revision bump).
     */
    public static function update_user_picture(int $operatorid, int $targetuserid, string $jpegdata): bool {
        global $CFG;

        require_once($CFG->libdir . '/gdlib.php');
        // file_get_unused_draft_itemid() below, and file_save_draft_area_files()
        // called internally by \core\user::update_picture(), both live in
        // filelib.php - which setup.php only autoloads under a specific
        // proxy configuration, not on every request (confirmed against
        // MOODLE_501_STABLE source; same class of bug as export.php's
        // missing send_temp_file()).
        require_once($CFG->libdir . '/filelib.php');

        $draftitemid = self::stage_draft_file($operatorid, $jpegdata);

        $filemanageroptions = [
            'maxbytes' => $CFG->maxbytes,
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => 'web_image',
        ];

        $usernew = (object) [
            'id' => $targetuserid,
            'imagefile' => $draftitemid,
        ];

        return (bool) core_user_class::update_picture($usernew, $filemanageroptions);
    }

    /**
     * Store the processed JPEG in a brand-new draft file area for the operator.
     *
     * @param int $operatorid
     * @param string $jpegdata
     * @return int the new draft itemid.
     */
    private static function stage_draft_file(int $operatorid, string $jpegdata): int {
        $usercontext = context_user::instance($operatorid, MUST_EXIST);
        $fs = get_file_storage();
        $draftitemid = file_get_unused_draft_itemid();

        $filerecord = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'profilephoto_' . time() . '.jpg',
        ];

        $fs->create_file_from_string($filerecord, $jpegdata);

        return $draftitemid;
    }
}
