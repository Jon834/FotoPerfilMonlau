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
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

defined('MOODLE_INTERNAL') || die();

/**
 * Embed a student's photo (or an initials avatar) into an Excel cell, for
 * every PhpSpreadsheet-based export builder ({@see activity_xlsx_builder},
 * {@see photo_xlsx_builder}).
 *
 * Deliberately GD-based rather than anything needing an external font file
 * or ImageMagick: {@see branding::fallback_logo()} already proves GD works
 * on every deployment this plugin runs on.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class xlsx_avatar {

    /** @var int Square avatar size, in pixels. */
    private const SIZE_PX = 40;

    /** @var array<int, array{0:int,1:int,2:int}> Deterministic initials-avatar palette. */
    private const PALETTE = [
        [79, 114, 189], [39, 131, 118], [193, 116, 73], [124, 92, 176],
        [67, 145, 91], [187, 92, 128], [107, 132, 89], [74, 125, 173],
        [163, 118, 60], [96, 107, 168],
    ];

    /**
     * Read the raw content of a user's largest official icon variant.
     *
     * @param int $userid
     * @return string|null
     */
    public static function get_icon_content(int $userid): ?string {
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
     * Embed a student's avatar (photo, cropped to a centred square and resized,
     * or - when there is no usable photo - a solid-colour initials square) at
     * the given cell. Always embeds something: callers never need a fallback.
     *
     * @param Worksheet $sheet
     * @param string $cell e.g. "B7"
     * @param string|null $photobytes raw image bytes, or null when the student has no photo.
     * @param string $firstname
     * @param string $lastname
     */
    public static function embed(Worksheet $sheet, string $cell, ?string $photobytes,
            string $firstname, string $lastname): void {
        $image = $photobytes !== null ? self::photo_to_square($photobytes) : null;
        if ($image === null) {
            $image = self::initials_square(self::initials($firstname, $lastname), trim($lastname . ' ' . $firstname));
        }

        $drawing = new MemoryDrawing();
        $drawing->setName('avatar');
        $drawing->setDescription('avatar');
        $drawing->setImageResource($image);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
        $drawing->setHeight(self::SIZE_PX);
        $drawing->setWidth(self::SIZE_PX);
        $drawing->setOffsetX(3);
        $drawing->setOffsetY(3);
        $drawing->setCoordinates($cell);
        $drawing->setWorksheet($sheet);
    }

    /** @var int Max width/height (px) the stage logo is scaled to fit, aspect preserved. */
    private const LOGO_MAX_W = 90;
    private const LOGO_MAX_H = 30;

    /**
     * Embed the stage's brand logo at the given cell, scaled to fit a small box while
     * preserving its aspect ratio. Raster logos (jpg/png) are fetched and decoded as-is;
     * the one SVG stage logo (monlaugroup) - and any raster fetch failure - falls back to
     * {@see branding::fallback_logo()}, the same generated mark the PDF builders use when
     * their own logo fetch fails, so there's always something in the header.
     *
     * @param Worksheet $sheet
     * @param string $cell e.g. "A1"
     * @param string|null $stage
     */
    public static function embed_logo(Worksheet $sheet, string $cell, ?string $stage): void {
        $stage = branding::normalise_stage($stage);
        $url = branding::logo_url($stage);
        $issvg = $url !== null && preg_match('/\.svg(\?|$)/i', $url);

        $image = null;
        if ($url !== null && !$issvg) {
            $bytes = branding::fetch_raster($url);
            if ($bytes !== null) {
                $decoded = @imagecreatefromstring($bytes);
                $image = $decoded !== false ? $decoded : null;
            }
        }
        if ($image === null) {
            $image = imagecreatefromstring(branding::fallback_logo($stage));
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(self::LOGO_MAX_W / $width, self::LOGO_MAX_H / $height, 1.0);

        $drawing = new MemoryDrawing();
        $drawing->setName('logo');
        $drawing->setDescription('logo');
        $drawing->setImageResource($image);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
        $drawing->setWidth((int) round($width * $scale));
        $drawing->setHeight((int) round($height * $scale));
        $drawing->setOffsetX(4);
        $drawing->setOffsetY(4);
        $drawing->setCoordinates($cell);
        $drawing->setWorksheet($sheet);
    }

    /**
     * Centre-crop raw image bytes to a square and resize to {@see SIZE_PX}.
     *
     * @param string $bytes
     * @return \GdImage|null null when the bytes can't be decoded as an image.
     */
    private static function photo_to_square(string $bytes) {
        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $side = min($width, $height);

        $dst = imagecreatetruecolor(self::SIZE_PX, self::SIZE_PX);
        imagecopyresampled($dst, $src, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2),
            self::SIZE_PX, self::SIZE_PX, $side, $side);
        imagedestroy($src);

        return $dst;
    }

    /**
     * A solid-colour square with centred white initials.
     *
     * @param string $initials 1-2 upper-case letters
     * @param string $colourseed string the background colour is derived from
     * @return \GdImage
     */
    private static function initials_square(string $initials, string $colourseed) {
        $dst = imagecreatetruecolor(self::SIZE_PX, self::SIZE_PX);
        [$r, $g, $b] = self::avatar_color($colourseed);
        $fill = imagecolorallocate($dst, $r, $g, $b);
        imagefilledrectangle($dst, 0, 0, self::SIZE_PX - 1, self::SIZE_PX - 1, $fill);

        $white = imagecolorallocate($dst, 255, 255, 255);
        $font = 5;
        $textwidth = imagefontwidth($font) * strlen($initials);
        $textheight = imagefontheight($font);
        imagestring($dst, $font, (int) round((self::SIZE_PX - $textwidth) / 2),
            (int) round((self::SIZE_PX - $textheight) / 2), $initials, $white);

        return $dst;
    }

    /**
     * First letter of the first name + first letter of the last name, upper-cased.
     *
     * @param string $firstname
     * @param string $lastname
     * @return string
     */
    private static function initials(string $firstname, string $lastname): string {
        $first = trim($firstname);
        $last = trim($lastname);
        $initials = mb_strtoupper(($first !== '' ? mb_substr($first, 0, 1) : '')
            . ($last !== '' ? mb_substr($last, 0, 1) : ''));
        return $initials !== '' ? $initials : '?';
    }

    /**
     * Deterministic avatar background colour derived from a string.
     *
     * @param string $seed
     * @return array{0:int,1:int,2:int}
     */
    private static function avatar_color(string $seed): array {
        $index = abs(crc32(mb_strtolower($seed))) % count(self::PALETTE);
        return self::PALETTE[$index];
    }
}
