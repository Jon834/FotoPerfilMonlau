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

defined('MOODLE_INTERNAL') || die();

/**
 * Build a PDF roster/orla with A4 portrait layout and names under each photo.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf_builder {

    /**
     * Build the PDF document.
     *
     * @param int[] $userids
     * @param string $title course/cohort title shown at the top of the sheet.
     * @param string $layout roster or orla.
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $userids, string $title, string $layout = 'roster', array $options = []): array {
        global $CFG;

        require_once($CFG->libdir . '/tcpdf/tcpdf.php');

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = in_array($options['stage'] ?? 'fp', ['fp', 'eso', 'batx'], true) ? $options['stage'] : 'fp';
        $heading = trim((string) ($options['heading'] ?? ''));

        $users = [];
        foreach ($userids as $userid) {
            $user = core_user_class::get_user((int) $userid, 'id, firstname, lastname, picture', IGNORE_MISSING);
            if (!$user || ((int) $user->picture) <= 0) {
                continue;
            }

            $content = self::get_icon_content((int) $user->id);
            if ($content === null) {
                continue;
            }

            $user->photo = $content;
            $users[] = $user;
        }

        usort($users, static function($a, $b): int {
            $lastname = strcmp((string) ($a->lastname ?? ''), (string) ($b->lastname ?? ''));
            if ($lastname !== 0) {
                return $lastname;
            }
            return strcmp((string) ($a->firstname ?? ''), (string) ($b->firstname ?? ''));
        });

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $filename = 'profilephoto_' . ($layout === 'orla' ? 'orla' : 'roster') . '_' . userdate(time(), '%Y%m%d_%H%M') . '.pdf';
        $path = $tempdir . '/' . $filename;

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, 9);
        $pdf->SetCreator('Moodle local_profilephoto');
        $pdf->SetAuthor('Moodle local_profilephoto');
        $pdf->SetTitle($title);

        $pdf->AddPage();

        $brand = self::brand_colors($stage);
        $pdf->SetFillColor($brand['r'], $brand['g'], $brand['b']);
        $pdf->Rect(0, 0, 210, 28, 'F');

        $logo = self::resolve_logo_path($stage);
        if ($logo !== null && preg_match('/\.svg$/i', $logo)) {
            $pdf->ImageSVG($logo, 10, 6, 28, 14, '', '', 'T', false);
        } else if ($logo !== null) {
            $pdf->Image($logo, 10, 6, 28, 14, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            $pdf->Image('@' . self::monlau_logo($stage), 10, 6, 28, 14, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(42, 8);
        $pdf->Cell(0, 7, self::translate_title($layout, $language), 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(42, 16);
        $pdf->Cell(0, 5, $title, 0, 1, 'L');

        if ($heading !== '') {
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetFillColor(245, 247, 251);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 8, $heading, 0, 1, 'L', true);
            $pdf->Ln(2);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        $columns = 4;
        $pagew = 210;
        $left = 12;
        $gutter = 5;
        $cellw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $imgw = 32;
        $imgh = 32;

        foreach ($users as $index => $user) {
            $col = $index % $columns;
            $row = intdiv($index, $columns);
            $x = $left + ($col * ($cellw + $gutter));
            $y = $pdf->GetY() + ($row > 0 ? 0 : 0);

            if ($row > 0 && $col === 0) {
                $pdf->Ln(42);
            }

            $xpos = $x + (($cellw - $imgw) / 2);
            $ypos = $pdf->GetY();
            $pdf->Image('@' . $user->photo, $xpos, $ypos, $imgw, $imgh, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $pdf->SetY($ypos + $imgh + 2);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->MultiCell($cellw, 4, fullname($user), 0, 'C', false, 1, $x, $pdf->GetY(), true, 0, false, true, 0, 'M');
            $pdf->SetY($pdf->GetY() + 2);
            $pdf->SetFont('helvetica', '', 8);
        }

        $pdf->Output($path, 'F');

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => count($users),
        ];
    }

    /**
     * Provide the Monlau brand palette.
     *
     * @param string $stage
     * @return array{r:int,g:int,b:int}
     */
    private static function brand_colors(string $stage): array {
        if ($stage === 'eso') {
            return ['r' => 42, 'g' => 134, 'b' => 96];
        }
        if ($stage === 'batx') {
            return ['r' => 116, 'g' => 161, 'b' => 47];
        }
        return ['r' => 12, 'g' => 80, 'b' => 160];
    }

    /**
     * Resolve a Monlau logo from the Moodle installation using the exact asset URLs provided.
     *
     * @param string $stage
     * @return string|null
     */
    private static function resolve_logo_path(string $stage): ?string {
        $urls = [
            'fp' => 'https://falcon-caramel42.monlau.com/pluginfile.php/1/theme_monlau/customimages/1788184148/monlau_fp.jpg',
            'eso' => 'https://falcon-caramel42.monlau.com/pluginfile.php/1/theme_monlau/customimages/1788184148/monlau_eso.jpg',
            'batx' => 'https://falcon-caramel42.monlau.com/pluginfile.php/1/theme_monlau/customimages/1788184148/monlaugroup.svg',
        ];

        $url = $urls[$stage] ?? $urls['fp'];
        if ($url !== '') {
            return $url;
        }

        return null;
    }

    /**
     * Return a simplified Monlau logo as a PNG data blob.
     *
     * @param string $stage
     * @return string
     */
    private static function monlau_logo(string $stage): string {
        $width = 140;
        $height = 60;
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);

        $brand = self::brand_colors($stage);
        $blue = imagecolorallocate($im, $brand['r'], $brand['g'], $brand['b']);
        $dark = imagecolorallocate($im, 20, 30, 40);

        imagefilledrectangle($im, 0, 0, 16, $height, $blue);
        imagefilledrectangle($im, 20, 16, 132, 22, $blue);
        imagefilledrectangle($im, 20, 36, 100, 12, $dark);

        $text = 'MONLAU';
        $font = 5;
        imagestring($im, $font, 24, 20, $text, $dark);

        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    /**
     * Translate the page header based on the selected language.
     *
     * @param string $layout
     * @param string $language
     * @return string
     */
    private static function translate_title(string $layout, string $language): string {
        $map = [
            'ca' => ['roster' => 'Roster', 'orla' => 'Orla'],
            'es' => ['roster' => 'Lista', 'orla' => 'Orla'],
            'en' => ['roster' => 'Roster', 'orla' => 'Class photo'],
        ];
        return $map[$language][$layout] ?? $map['ca'][$layout];
    }

    /**
     * Read the raw content of a user's largest official icon variant.
     *
     * @param int $userid
     * @return string|null
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
}
