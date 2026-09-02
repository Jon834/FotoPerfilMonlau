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

        // Render optional heading/comment and get final Y position.
        $firstpagebaseY = self::render_heading($pdf, $heading);

        // Render content based on layout, passing first-page Y position.
        $brand = self::brand_colors($stage);
        if ($layout === 'grid6') {
            self::render_grid_6col($pdf, $users, $firstpagebaseY, $brand);
        } else if ($layout === 'directory') {
            self::render_directory($pdf, $users, $firstpagebaseY, $brand);
        } else {
            self::render_grid_4col($pdf, $users, $firstpagebaseY, $brand);
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
        // Position below header blue bar to prevent overlap with heading.
        $pdf->SetY(31);
    }

    /**
     * Render optional heading/comment section (first page only).
     *
     * @param \TCPDF $pdf
     * @param string $heading
     * @return float The Y position after heading
     */
    private static function render_heading(\TCPDF &$pdf, string $heading): float {
        if ($heading === '') {
            return $pdf->GetY();
        }

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFillColor(245, 247, 251);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 5, $heading, 0, 'L', true);
        $endY = $pdf->GetY();
        $pdf->SetY($endY + 3);
        return $pdf->GetY();
    }

    /**
     * Render a 4-column grid layout (orla classic) with proper pagination.
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebaseY Y position after header+heading on first page
     */
    private static function render_grid_4col(\TCPDF &$pdf, array $users, float $firstpagebaseY = 35, array $brand = ['r' => 12, 'g' => 80, 'b' => 160]): void {
        $columns = 4;
        $pagew = 210;
        $left = 12;
        $gutter = 6;
        $cardw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $cardh = 40;
        $rowgap = 3;
        $rowheight = $cardh + $rowgap;

        // A4 height = 297mm. Auto page break is disabled during grid rendering, so a small
        // bottom margin is enough.
        $pageheight = 297;
        $bottommargin = 4;
        // Calculate max rows that fit on first page (accounting for header+heading).
        $availableheight = $pageheight - $firstpagebaseY - $bottommargin;
        $maxrows = (int) floor($availableheight / $rowheight);
        $usersperpage = $columns * $maxrows;
        $basestarty = $firstpagebaseY;

        // Disable auto page break to prevent TCPDF from interfering with manual grid pagination.
        $pdf->SetAutoPageBreak(false);

        $indexOnCurrentPage = 0;
        foreach ($users as $index => $user) {
            // Check if we need a new page based on position within current page.
            if ($index > 0 && $indexOnCurrentPage >= $usersperpage) {
                $pdf->AddPage();
                $basestarty = 12 + 2;
                // Recalculate maxrows for subsequent pages (no header/heading).
                $availableheight = $pageheight - $basestarty - $bottommargin;
                $maxrows = (int) floor($availableheight / $rowheight);
                $usersperpage = $columns * $maxrows;
                $indexOnCurrentPage = 0;  // Reset counter for new page
            }

            $col = $indexOnCurrentPage % $columns;
            $row = intdiv($indexOnCurrentPage, $columns);

            $x = $left + ($col * ($cardw + $gutter));
            $y = $basestarty + ($row * $rowheight);

            self::render_student_card($pdf, $x, $y, $cardw, $cardh, $user->photo,
                self::format_student_name($user), $index + 1, $brand, 'normal');

            $indexOnCurrentPage++;
        }

        // Re-enable auto page break after grid rendering.
        $pdf->SetAutoPageBreak(true, 9);
    }

    /**
     * Render a 6-column grid layout (compact) with proper pagination.
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebaseY Y position after header+heading on first page
     */
    private static function render_grid_6col(\TCPDF &$pdf, array $users, float $firstpagebaseY = 35, array $brand = ['r' => 12, 'g' => 80, 'b' => 160]): void {
        $columns = 6;
        $pagew = 210;
        $left = 8;
        $gutter = 3;
        $cardw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $cardh = 32;
        $rowgap = 3;
        $rowheight = $cardh + $rowgap;

        // A4 height = 297mm, minimal bottom margin = 3mm (SetAutoPageBreak disabled).
        $pageheight = 297;
        $bottommargin = 3;
        // Calculate max rows that fit on first page (accounting for header+heading).
        $availableheight = $pageheight - $firstpagebaseY - $bottommargin;
        $maxrows = (int) floor($availableheight / $rowheight);
        $usersperpage = $columns * $maxrows;
        $basestarty = $firstpagebaseY;

        // Disable auto page break to prevent TCPDF from interfering with manual grid pagination.
        $pdf->SetAutoPageBreak(false);

        $indexOnCurrentPage = 0;
        foreach ($users as $index => $user) {
            // Check if we need a new page based on position within current page.
            if ($index > 0 && $indexOnCurrentPage >= $usersperpage) {
                $pdf->AddPage();
                $basestarty = 12 + 2;
                // Recalculate maxrows for subsequent pages (no header/heading).
                $availableheight = $pageheight - $basestarty - $bottommargin;
                $maxrows = (int) floor($availableheight / $rowheight);
                $usersperpage = $columns * $maxrows;
                $indexOnCurrentPage = 0;  // Reset counter for new page
            }

            $col = $indexOnCurrentPage % $columns;
            $row = intdiv($indexOnCurrentPage, $columns);

            $x = $left + ($col * ($cardw + $gutter));
            $y = $basestarty + ($row * $rowheight);

            self::render_student_card($pdf, $x, $y, $cardw, $cardh, $user->photo,
                self::format_student_name($user), $index + 1, $brand, 'compact');

            $indexOnCurrentPage++;
        }

        // Re-enable auto page break after grid rendering.
        $pdf->SetAutoPageBreak(true, 9);
    }

    /**
     * Render a directory table layout (photo + name + email) with 2 columns.
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebaseY Y position after header+heading on first page
     */
    private static function render_directory(\TCPDF &$pdf, array $users, float $firstpagebaseY = 35, array $brand = ['r' => 12, 'g' => 80, 'b' => 160]): void {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        $columns = 2;
        $pagew = 210;
        $left = 10;
        $gutter = 8;
        $colw = ($pagew - $left * 2 - $gutter) / $columns;
        $rowheight = 22;
        $cardh = $rowheight - 3;

        // A4 height = 297mm, minimal bottom margin = 3mm (SetAutoPageBreak disabled).
        $pageheight = 297;
        $bottommargin = 3;
        // Calculate max rows that fit on first page (accounting for header+heading).
        $availableheight = $pageheight - $firstpagebaseY - $bottommargin;
        $maxrows = (int) floor($availableheight / $rowheight);
        $usersperpage = $columns * $maxrows;
        $basestarty = $firstpagebaseY;

        // Disable auto page break to prevent TCPDF from interfering with manual pagination.
        $pdf->SetAutoPageBreak(false);

        $indexOnCurrentPage = 0;
        foreach ($users as $index => $user) {
            // Check if we need a new page based on position within current page.
            if ($indexOnCurrentPage > 0 && $indexOnCurrentPage >= $usersperpage) {
                $pdf->AddPage();
                $basestarty = 12 + 2;
                // Recalculate maxrows for subsequent pages (no header/heading).
                $availableheight = $pageheight - $basestarty - $bottommargin;
                $maxrows = (int) floor($availableheight / $rowheight);
                $usersperpage = $columns * $maxrows;
                $indexOnCurrentPage = 0;  // Reset counter for new page
            }

            $col = $indexOnCurrentPage % $columns;
            $pagerow = intdiv($indexOnCurrentPage, $columns);

            $x = $left + ($col * ($colw + $gutter));
            $y = $basestarty + ($pagerow * $rowheight);

            // Card background.
            $pdf->RoundedRect($x, $y, $colw, $cardh, 2, '1111', 'DF',
                ['width' => 0.2, 'color' => [226, 232, 240]], [248, 250, 252]);

            // Circular photo on the left of the card.
            $photod = $cardh - 4;
            $pcx = $x + 2 + $photod / 2;
            $pcy = $y + 2 + $photod / 2;
            self::draw_circular_photo($pdf, $user->photo, $pcx, $pcy, $photod);

            $textx = $x + $photod + 6;
            $textw = $colw - $photod - 8;

            // Number badge overlapping the top-left of the photo.
            self::draw_number_badge($pdf, $x + 2.6, $y + 2.6, 3.4, 5, $index + 1, $brand);

            // Name.
            $pdf->SetXY($textx, $y + 3);
            $pdf->SetTextColor(45, 55, 72);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell($textw, 5, self::fit_name(self::format_student_name($user), 40), 0, 0, 'L');

            // Email.
            $pdf->SetXY($textx, $y + 9);
            $pdf->SetTextColor(110, 120, 135);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell($textw, 4, (string) ($user->email ?? ''), 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);

            $indexOnCurrentPage++;
        }

        // Re-enable auto page break after directory rendering.
        $pdf->SetAutoPageBreak(true, 9);
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
     * Render a single student "card": rounded panel, circular photo with ring,
     * a coloured number badge in the top-left corner and the name underneath.
     *
     * @param \TCPDF $pdf
     * @param float $x card left
     * @param float $y card top
     * @param float $cardw card width
     * @param float $cardh card height
     * @param string $photo raw image bytes
     * @param string $name formatted student name
     * @param int $number 1-based position shown in the badge
     * @param array{r:int,g:int,b:int} $brand badge fill colour
     * @param string $size 'normal' or 'compact'
     */
    private static function render_student_card(\TCPDF &$pdf, float $x, float $y, float $cardw, float $cardh,
            string $photo, string $name, int $number, array $brand, string $size = 'normal'): void {
        $compact = ($size === 'compact');
        $pad = $compact ? 2.0 : 3.0;
        $imgd = $compact ? 15.0 : 24.0;
        $namefont = $compact ? 6 : 8;
        $nameh = $compact ? 2.6 : 3.6;
        $badger = $compact ? 2.9 : 3.7;
        $badgefont = $compact ? 5 : 6;
        $namechars = $compact ? 26 : 34;

        // 1. Card panel.
        $pdf->RoundedRect($x, $y, $cardw, $cardh, $compact ? 1.8 : 2.4, '1111', 'DF',
            ['width' => 0.2, 'color' => [226, 232, 240]], [248, 250, 252]);

        // 2. Circular photo near the top, centred horizontally.
        $cx = $x + $cardw / 2;
        $imgy = $y + $pad + ($compact ? 1.0 : 1.5);
        $cy = $imgy + $imgd / 2;
        self::draw_circular_photo($pdf, $photo, $cx, $cy, $imgd);

        // 3. Name block under the photo (up to two lines).
        $pdf->SetFont('helvetica', 'B', $namefont);
        $pdf->SetTextColor(45, 55, 72);
        $pdf->SetXY($x + 1, $imgy + $imgd + ($compact ? 1.2 : 1.6));
        $namemaxh = $compact ? 7.0 : 9.0;
        $pdf->MultiCell($cardw - 2, $nameh, self::fit_name($name, $namechars), 0, 'C', false, 0,
            '', '', true, 0, false, true, $namemaxh, 'T');

        // 4. Number badge in the top-left corner of the card.
        self::draw_number_badge($pdf, $x + $badger + ($compact ? 1.0 : 1.4),
            $y + $badger + ($compact ? 1.0 : 1.4), $badger, $badgefont, $number, $brand);

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Draw an image clipped to a circle plus a thin white/grey ring.
     *
     * @param \TCPDF $pdf
     * @param string $photo raw image bytes
     * @param float $cx circle centre X
     * @param float $cy circle centre Y
     * @param float $d circle diameter
     */
    private static function draw_circular_photo(\TCPDF &$pdf, string $photo, float $cx, float $cy, float $d): void {
        $r = $d / 2;
        $pdf->StartTransform();
        $pdf->Circle($cx, $cy, $r, 0, 360, 'CNZ');
        $pdf->Image('@' . $photo, $cx - $r, $cy - $r, $d, $d, '', '', '', false, 300,
            '', false, false, 0, 'C', false, false);
        $pdf->StopTransform();

        $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.5, 'color' => [255, 255, 255]]);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.2, 'color' => [205, 213, 224]]);
    }

    /**
     * Draw a filled circular badge with a centred white number.
     *
     * @param \TCPDF $pdf
     * @param float $cx badge centre X
     * @param float $cy badge centre Y
     * @param float $r badge radius
     * @param int $font font size for the number
     * @param int $number value to show
     * @param array{r:int,g:int,b:int} $brand fill colour
     */
    private static function draw_number_badge(\TCPDF &$pdf, float $cx, float $cy, float $r, int $font, int $number, array $brand): void {
        $pdf->Circle($cx, $cy, $r, 0, 360, 'DF',
            ['width' => 0.3, 'color' => [255, 255, 255]], [$brand['r'], $brand['g'], $brand['b']]);
        $pdf->SetFont('helvetica', 'B', $font);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($cx - $r, $cy - $r);
        $pdf->Cell($r * 2, $r * 2, (string) $number, 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Shorten a name so it stays within the card, cutting on a word boundary.
     *
     * @param string $name
     * @param int $maxchars
     * @return string
     */
    private static function fit_name(string $name, int $maxchars): string {
        if (mb_strlen($name) <= $maxchars) {
            return $name;
        }
        $short = mb_substr($name, 0, $maxchars - 1);
        $lastspace = mb_strrpos($short, ' ');
        if ($lastspace !== false && $lastspace > $maxchars * 0.5) {
            $short = mb_substr($short, 0, $lastspace);
        }
        return rtrim($short, " ,.;:-") . '…';
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
