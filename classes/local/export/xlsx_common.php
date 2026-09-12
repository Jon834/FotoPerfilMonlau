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

defined('MOODLE_INTERNAL') || die();

/**
 * Tiny, non-visual helpers shared by every PhpSpreadsheet-based export
 * builder ({@see activity_xlsx_builder}, {@see photo_xlsx_builder}).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class xlsx_common {

    /**
     * Convert an {r,g,b} triple into an "RRGGBB" hex string for PhpSpreadsheet fills.
     *
     * @param array{r:int,g:int,b:int} $rgb
     * @return string
     */
    public static function rgb_hex(array $rgb): string {
        return sprintf('%02X%02X%02X', $rgb['r'], $rgb['g'], $rgb['b']);
    }

    /**
     * Sanitise a name into a valid Excel sheet title: strip characters Excel
     * forbids in a sheet name (\ / ? * [ ] :) and cap at Excel's 31-character limit.
     *
     * @param string $name
     * @param string $fallback used when $name is empty once sanitised.
     * @return string
     */
    public static function sheet_title(string $name, string $fallback): string {
        $name = trim((string) preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name));
        $name = mb_substr($name, 0, 31);
        return $name !== '' ? $name : $fallback;
    }
}
