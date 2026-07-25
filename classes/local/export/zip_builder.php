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

namespace local_profilephoto\local\export;

use context_user;
use core\user as core_user_class;
use ZipArchive;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds a ZIP of profile photos plus a manifest.csv (encargo section 15).
 *
 * Only exports users who actually have a custom profile picture; reads the
 * 512x512 "f3" variant Moodle itself generated via process_new_icon() (see
 * image/updater.php) - the plugin never keeps its own copy of the photo.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zip_builder {

    /** @var string[] manifest.csv column headers, in order (encargo section 15). */
    const MANIFEST_HEADERS = ['userid', 'username', 'idnumber', 'fullname', 'email', 'filename', 'capturedate', 'session', 'status'];

    /**
     * Build the export ZIP for a list of user ids.
     *
     * @param int[] $userids
     * @param string $strategy primary filename strategy (filename_strategy::STRATEGIES).
     * @param string $fallback fallback filename strategy, used when the primary value is empty.
     * @param int|null $sessionid recorded in the manifest only, may be null.
     * @return array{path: string, filename: string, count: int} temp file path is caller's to manage/delete.
     */
    public static function build(array $userids, string $strategy, string $fallback, ?int $sessionid): array {
        $tempdir = make_temp_directory('local_profilephoto/exports');
        $zipfilename = bin2hex(random_bytes(16)) . '.zip';
        $zippath = $tempdir . '/' . $zipfilename;

        $zip = new ZipArchive();
        $zip->open($zippath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usednames = [];
        $manifestlines = [implode(',', self::MANIFEST_HEADERS)];
        $count = 0;

        foreach ($userids as $userid) {
            $user = core_user_class::get_user($userid, 'id, username, idnumber, firstname, lastname, email, picture',
                IGNORE_MISSING);
            if (!$user || ((int) $user->picture) <= 0) {
                continue;
            }

            $content = self::get_icon_content((int) $userid);
            if ($content === null) {
                continue;
            }

            $filename = filename_strategy::build_filename($user, $strategy, $fallback, $usednames);
            $zip->addFromString($filename, $content);
            $count++;

            $manifestlines[] = implode(',', array_map([self::class, 'csv_escape'], [
                $user->id,
                $user->username,
                $user->idnumber,
                fullname($user),
                $user->email,
                $filename,
                userdate(time(), '%Y-%m-%d %H:%M'),
                $sessionid ?? '',
                'exported',
            ]));
        }

        $zip->addFromString('manifest.csv', implode("\n", $manifestlines) . "\n");
        $zip->close();

        return [
            'path' => $zippath,
            'filename' => 'profilephoto_export_' . userdate(time(), '%Y%m%d_%H%M') . '.zip',
            'count' => $count,
        ];
    }

    /**
     * Read the raw content of a user's largest official icon variant.
     *
     * @param int $userid
     * @return string|null null if the user or file cannot be found.
     */
    private static function get_icon_content(int $userid): ?string {
        $usercontext = context_user::instance($userid, IGNORE_MISSING);
        if (!$usercontext) {
            return null;
        }

        $fs = get_file_storage();
        foreach (['f3.jpg', 'f3.png'] as $candidate) {
            $file = $fs->get_file($usercontext->id, 'user', 'icon', 0, '/', $candidate);
            if ($file && !$file->is_directory()) {
                return $file->get_content();
            }
        }

        return null;
    }

    /**
     * Escape one CSV field.
     *
     * @param mixed $value
     * @return string
     */
    private static function csv_escape($value): string {
        $value = (string) $value;
        if (preg_match('/[",\n]/', $value)) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
