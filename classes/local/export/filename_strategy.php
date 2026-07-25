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

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds sanitised, deduplicated export filenames (encargo section 15).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filename_strategy {

    /** @var string[] Supported naming strategies. */
    const STRATEGIES = ['idnumber', 'username', 'email', 'userid', 'fullname'];

    /**
     * Build a unique, safe filename (with .jpg extension) for a user.
     *
     * Falls back to $fallback when the primary strategy's field is empty,
     * and to the numeric user id if both are empty. Duplicates are
     * disambiguated as name_2.jpg, name_3.jpg... (encargo section 15).
     *
     * @param stdClass $user must contain id and whichever fields the strategies need.
     * @param string $strategy one of self::STRATEGIES.
     * @param string $fallback one of self::STRATEGIES, used when the primary value is empty.
     * @param array $usednames map of already-used filenames => true, updated in place.
     * @return string
     */
    public static function build_filename(stdClass $user, string $strategy, string $fallback, array &$usednames): string {
        $base = self::raw_value($user, $strategy);
        if ($base === '') {
            $base = self::raw_value($user, $fallback);
        }
        if ($base === '') {
            $base = 'user_' . $user->id;
        }

        $base = self::sanitize($base);
        $filename = $base . '.jpg';
        $suffix = 2;
        while (isset($usednames[$filename])) {
            $filename = $base . '_' . $suffix . '.jpg';
            $suffix++;
        }
        $usednames[$filename] = true;

        return $filename;
    }

    /**
     * Read the raw (unsanitised) value for a naming strategy.
     *
     * @param stdClass $user
     * @param string $strategy
     * @return string
     */
    private static function raw_value(stdClass $user, string $strategy): string {
        switch ($strategy) {
            case 'idnumber':
                return (string) ($user->idnumber ?? '');
            case 'username':
                return (string) ($user->username ?? '');
            case 'email':
                return (string) ($user->email ?? '');
            case 'userid':
                return (string) $user->id;
            case 'fullname':
                return fullname($user);
            default:
                return '';
        }
    }

    /**
     * Sanitise a value into a safe filename component.
     *
     * Reuses Moodle's own PARAM_FILE cleaner (strips path separators, null
     * bytes, leading dots...) instead of a bespoke regex, to avoid
     * reinventing path-traversal protection (encargo section 20).
     *
     * @param string $value
     * @return string
     */
    private static function sanitize(string $value): string {
        $value = clean_param($value, PARAM_FILE);
        $value = trim($value, '._ ');
        if ($value === '') {
            $value = 'user';
        }
        return $value;
    }
}
