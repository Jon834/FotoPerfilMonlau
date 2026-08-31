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
 * Build a PDF photographic listing with A4 portrait layout and names under each photo.
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
     * @param string $layout orla|grid6|directory (roster kept as alias for orla).
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $userids, string $title, string $layout = 'orla', array $options = []): array {
        global $CFG;

        require_once($CFG->libdir . '/tcpdf/tcpdf.php');

        // Normalize layout aliases.
        if ($layout === 'roster') {
            $layout = 'orla';
        }

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = in_array($options['stage'] ?? 'fp', ['fp', 'eso', 'batx'], true) ? $options['stage'] : 'fp';
        $heading = trim((string) ($options['heading'] ?? ''));

        $users = [];
        foreach ($userids as $userid) {
            $user = core_user_class::get_user((int) $userid, 'id, firstname, lastname, picture, email', IGNORE_MISSING);
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
        $layoutname = match($layout) {
            'grid6' => 'orla6',
            'directory' => 'directorio',
            default => 'orla',
        };
        $coursename = self::sanitize_filename($title);
        $filename = $coursename . '_' . $layoutname . '_' . userdate(time(), '%Y%m%d_%H%M') . '.pdf';
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

        // Render header.
        self::render_header($pdf, $title, $layout, $language, $stage, $heading);

        // Render optional heading/comment.
        self::render_heading($pdf, $heading);

        // Render content based on layout.
        if ($layout === 'grid6') {
            self::render_grid_6col($pdf, $users);
        } else if ($layout === 'directory') {
            self::render_directory($pdf, $users);
        } else {
            self::render_grid_4col($pdf, $users);
        }

        $pdf->Output($path, 'F');

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => count($users),
        ];
    }

    /**
     * Render the PDF header with logo and title.
     *
     * @param \TCPDF $pdf
     * @param string $title
     * @param string $layout
     * @param string $language
     * @param string $stage
     * @param string $heading
     */
    private static function render_header(\TCPDF &$pdf, string $title, string $layout, string $language, string $stage, string $heading): void {
        $brand = self::brand_colors($stage);
        $pdf->SetFillColor($brand['r'], $brand['g'], $brand['b']);
        $pdf->Rect(0, 0, 210, 30, 'F');

        // Logo: constrain to fit without distortion.
        $logo = self::resolve_logo_path($stage);
        $logosize = 16;
        if ($logo !== null && preg_match('/\.svg$/i', $logo)) {
            $pdf->ImageSVG($logo, 10, 7, $logosize, '', '', '', 'T', false);
        } else if ($logo !== null) {
            // Use Image with max height, let width scale proportionally.
            $pdf->Image($logo, 10, 7, 0, $logosize, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            $pdf->Image('@' . self::monlau_logo($stage), 10, 7, $logosize, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY(40, 6);
        $pdf->Cell(0, 8, $title, 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(40, 16);
        $pdf->Cell(0, 6, self::translate_title($layout, $language), 0, 1, 'L');

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Render optional heading/comment section.
     *
     * @param \TCPDF $pdf
     * @param string $heading
     */
    private static function render_heading(\TCPDF &$pdf, string $heading): void {
        if ($heading === '') {
            return;
        }

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFillColor(245, 247, 251);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, $heading, 0, 1, 'L', true);
        $pdf->Ln(3);
    }

    /**
     * Render a 4-column grid layout (orla classic) with proper pagination.
     *
     * @param \TCPDF $pdf
     * @param array $users
     */
    private static function render_grid_4col(\TCPDF &$pdf, array $users): void {
        $columns = 4;
        $pagew = 210;
        $left = 12;
        $gutter = 7;
        $cellw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $imgw = 34;
        $imgh = 34;
        $rowgap = 6;
        $textheight = 12;
        $rowheight = $imgh + $rowgap + $textheight;
        $basestarty = $pdf->GetY() + 3;
        $maxrows = 8;
        $usersperpage = $columns * $maxrows;

        foreach ($users as $index => $user) {
            $pageno = intdiv($index, $usersperpage);
            $useronpage = $index % $usersperpage;
            $col = $useronpage % $columns;
            $row = intdiv($useronpage, $columns);

            // Add new page if needed.
            if ($index > 0 && $useronpage === 0) {
                $pdf->AddPage();
                $basestarty = $pdf->GetY() + 3;
            }

            $x = $left + ($col * ($cellw + $gutter));
            $y = $basestarty + ($row * $rowheight);
            $xpos = $x + (($cellw - $imgw) / 2);

            $pdf->Image('@' . $user->photo, $xpos, $y, $imgw, $imgh, '', '', 'T', false, 300, '', false, false, 0, false, false, false);

            // Number.
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($x, $y - 4);
            $pdf->Cell(5, 4, (string) ($index + 1), 0, 0, 'C');

            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($x, $y + $imgh + 2);
            $pdf->MultiCell($cellw, 4, self::format_student_name($user), 0, 'C', false, 1);
            $pdf->SetFont('helvetica', '', 8);
        }
    }

    /**
     * Render a 6-column grid layout (compact) with proper pagination.
     *
     * @param \TCPDF $pdf
     * @param array $users
     */
    private static function render_grid_6col(\TCPDF &$pdf, array $users): void {
        $columns = 6;
        $pagew = 210;
        $left = 8;
        $gutter = 4;
        $cellw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $imgw = 20;
        $imgh = 20;
        $rowgap = 4;
        $textheight = 8;
        $rowheight = $imgh + $rowgap + $textheight;
        $basestarty = $pdf->GetY() + 3;
        $maxrows = 10;
        $usersperpage = $columns * $maxrows;

        foreach ($users as $index => $user) {
            $pageno = intdiv($index, $usersperpage);
            $useronpage = $index % $usersperpage;
            $col = $useronpage % $columns;
            $row = intdiv($useronpage, $columns);

            // Add new page if needed.
            if ($index > 0 && $useronpage === 0) {
                $pdf->AddPage();
                $basestarty = $pdf->GetY() + 3;
            }

            $x = $left + ($col * ($cellw + $gutter));
            $y = $basestarty + ($row * $rowheight);
            $xpos = $x + (($cellw - $imgw) / 2);

            $pdf->Image('@' . $user->photo, $xpos, $y, $imgw, $imgh, '', '', 'T', false, 300, '', false, false, 0, false, false, false);

            // Number.
            $pdf->SetFont('helvetica', 'B', 5);
            $pdf->SetXY($x, $y - 3);
            $pdf->Cell(4, 3, (string) ($index + 1), 0, 0, 'C');

            $pdf->SetFont('helvetica', 'B', 6);
            $pdf->SetXY($x, $y + $imgh + 1);
            $pdf->MultiCell($cellw, 3, self::format_student_name($user), 0, 'C', false, 1);
            $pdf->SetFont('helvetica', '', 6);
        }
    }

    /**
     * Render a directory table layout (photo + name + email) with 2 columns.
     *
     * @param \TCPDF $pdf
     * @param array $users
     */
    private static function render_directory(\TCPDF &$pdf, array $users): void {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        $columns = 2;
        $pagew = 210;
        $left = 10;
        $gutter = 8;
        $colw = ($pagew - $left * 2 - $gutter) / $columns;
        $rowheight = 20;
        $maxrows = 12;
        $usersperpage = $columns * $maxrows;
        $basestarty = $pdf->GetY() + 3;

        $userindex = 0;
        foreach ($users as $user) {
            // Calculate which page and position this user should be on.
            $pageno = intdiv($userindex, $usersperpage);
            $useronpage = $userindex % $usersperpage;
            $col = $useronpage % $columns;
            $pagerow = intdiv($useronpage, $columns);

            // Add new page if needed.
            if ($userindex > 0 && $useronpage === 0) {
                $pdf->AddPage();
                $basestarty = $pdf->GetY() + 3;
            }

            $x = $left + ($col * ($colw + $gutter));
            $y = $basestarty + ($pagerow * $rowheight);

            // Photo (18mm width, scaled to row height).
            $photow = 18;
            $photoh = $rowheight - 2;
            $pdf->Image('@' . $user->photo, $x, $y, $photow, $photoh, '', '', 'L', false, 300, '', false, false, 0, false, false, false);

            // Number.
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($x + 20, $y);
            $pdf->Cell(5, 4, (string) ($userindex + 1), 0, 0, 'L');

            // Name.
            $pdf->SetXY($x + $photow + 8, $y + 2);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($colw - $photow - 10, 5, self::format_student_name($user), 0, 1, 'L');

            // Email.
            $pdf->SetXY($x + $photow + 2, $y + 8);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell($colw - $photow - 4, 4, (string) ($user->email ?? ''), 0, 1, 'L');

            $userindex++;
        }
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
     * Format a student name as "Apellido1 Apellido2, Nombre".
     *
     * @param object $user
     * @return string
     */
    private static function format_student_name($user): string {
        $lastname = trim((string) ($user->lastname ?? ''));
        $firstname = trim((string) ($user->firstname ?? ''));
        if ($lastname === '' && $firstname === '') {
            return '';
        }
        if ($lastname === '') {
            return $firstname;
        }
        if ($firstname === '') {
            return $lastname;
        }
        return $lastname . ', ' . $firstname;
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
            'ca' => [
                'orla' => 'Listado fotográfico',
                'grid6' => 'Listado fotográfico (6 columnas)',
                'directory' => 'Directorio de alumnos',
                'roster' => 'Listado fotográfico',
            ],
            'es' => [
                'orla' => 'Listado fotográfico',
                'grid6' => 'Listado fotográfico (6 columnas)',
                'directory' => 'Directorio de alumnos',
                'roster' => 'Listado fotográfico',
            ],
            'en' => [
                'orla' => 'Photo list',
                'grid6' => 'Photo list (6 columns)',
                'directory' => 'Student directory',
                'roster' => 'Photo list',
            ],
        ];
        return $map[$language][$layout] ?? $map['ca']['orla'];
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

    /**
     * Sanitize a course/cohort name for use in a filename.
     *
     * @param string $name
     * @return string
     */
    private static function sanitize_filename(string $name): string {
        // Remove or replace problematic characters.
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($name));
        // Replace multiple underscores with single.
        $name = preg_replace('/_+/', '_', $name);
        // Remove leading/trailing underscores.
        $name = trim($name, '_');
        // Limit to 50 chars to keep filenames reasonable.
        $name = substr($name, 0, 50);
        return $name ?: 'export';
    }
}
