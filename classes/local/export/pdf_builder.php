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
 * Supported layouts: orla (card grid), grid6 (dense card grid), directory
 * (photo + name + email) and signatures (attendance / sign-off sheet). Every
 * layout gets an informative header, a footer with "page x / total" and an
 * initials avatar for students without a profile photo. The card grids honour
 * a density option (compact | normal | large).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf_builder {

    /** @var float Y coordinate content starts at on pages after the first. */
    private const CONTINUATION_TOP = 16.0;

    /** @var float Space reserved at the bottom of every page for the footer. */
    private const BOTTOM_MARGIN = 16.0;

    /**
     * Build the PDF document.
     *
     * @param int[] $userids
     * @param string $title course/cohort title shown at the top of the sheet.
     * @param string $layout orla|grid6|directory|signatures (roster kept as alias for orla).
     * @param array $options language, stage, density, heading, generatedby.
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $userids, string $title, string $layout = 'orla', array $options = []): array {
        global $CFG;

        require_once($CFG->libdir . '/tcpdf/tcpdf.php');

        // Normalize layout aliases and guard against unknown values.
        if ($layout === 'roster') {
            $layout = 'orla';
        }
        if (!in_array($layout, ['orla', 'grid6', 'directory', 'signatures'], true)) {
            $layout = 'orla';
        }

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = in_array($options['stage'] ?? 'fp', ['fp', 'eso', 'batx', 'corporate'], true) ? $options['stage'] : 'fp';
        $density = in_array($options['density'] ?? 'normal', ['compact', 'normal', 'large'], true)
            ? $options['density'] : 'normal';
        $heading = trim((string) ($options['heading'] ?? ''));
        $generatedby = trim((string) ($options['generatedby'] ?? ''));

        $users = [];
        foreach ($userids as $userid) {
            $user = core_user_class::get_user((int) $userid, 'id, firstname, lastname, picture, email', IGNORE_MISSING);
            if (!$user) {
                continue;
            }
            // Students without an official photo are still listed, with an initials avatar.
            $user->photo = ((int) $user->picture) > 0 ? self::get_icon_content((int) $user->id) : null;
            $users[] = $user;
        }

        usort($users, static function($a, $b): int {
            $lastname = strcmp((string) ($a->lastname ?? ''), (string) ($b->lastname ?? ''));
            if ($lastname !== 0) {
                return $lastname;
            }
            return strcmp((string) ($a->firstname ?? ''), (string) ($b->firstname ?? ''));
        });

        $count = count($users);

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $layoutname = match($layout) {
            'grid6' => 'orla6',
            'directory' => 'directorio',
            'signatures' => 'firmas',
            default => 'orla',
        };
        $coursename = self::sanitize_filename($title);
        $filename = $coursename . '_' . $layoutname . '_' . userdate(time(), '%Y%m%d_%H%M') . '.pdf';
        $path = $tempdir . '/' . $filename;

        $brand = self::brand_colors($stage);

        // TCPDF subclass so every page gets a "title .... page x / total" footer.
        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {

            /** @var string Left-aligned footer caption (document title). */
            public $footercaption = '';

            /** @var string Right-aligned footer caption ("Page x / y" already localised). */
            public $footerpagelabel = 'Pág.';

            /**
             * Print the page footer.
             */
            public function Footer() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $this->SetY(-12);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(140, 140, 140);
                $this->SetDrawColor(220, 224, 230);
                $this->Line($this->GetX(), $this->GetY(), $this->getPageWidth() - $this->original_rMargin, $this->GetY());
                $this->Ln(1);
                $this->Cell(0, 6, $this->footercaption, 0, 0, 'L', false, '', 0, false, 'T', 'M');
                $this->Cell(0, 6, $this->footerpagelabel . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
                    0, 0, 'R', false, '', 0, false, 'T', 'M');
                $this->SetTextColor(0, 0, 0);
                $this->SetDrawColor(0, 0, 0);
            }
        };

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterMargin(12);
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(true, self::BOTTOM_MARGIN);
        $pdf->SetCreator('Moodle local_profilephoto');
        $pdf->SetAuthor('Moodle local_profilephoto');
        $pdf->SetTitle($title);
        $pdf->footercaption = $title;
        $pdf->footerpagelabel = self::translate_word('page', $language);

        $pdf->AddPage();

        self::render_header($pdf, $title, $layout, $language, $stage, $count, $generatedby);
        $firstpagebasey = self::render_heading($pdf, $heading);

        if ($layout === 'signatures') {
            self::render_signatures($pdf, $users, $firstpagebasey, $brand, $language);
        } else if ($layout === 'directory') {
            self::render_directory($pdf, $users, $firstpagebasey, $brand, $density);
        } else {
            self::render_grid($pdf, $users, $firstpagebasey, $brand, self::grid_columns($layout, $density));
        }

        $pdf->Output($path, 'F');

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $count,
        ];
    }

    /**
     * Number of columns for a card grid, given the layout and the density option.
     *
     * @param string $layout orla|grid6
     * @param string $density compact|normal|large
     * @return int
     */
    private static function grid_columns(string $layout, string $density): int {
        if ($layout === 'grid6') {
            return ['compact' => 8, 'normal' => 6, 'large' => 5][$density] ?? 6;
        }
        return ['compact' => 5, 'normal' => 4, 'large' => 3][$density] ?? 4;
    }

    /**
     * Render the PDF header with logo, title and an informative line
     * (student count, generation date and, optionally, who generated it).
     *
     * @param \TCPDF $pdf
     * @param string $title
     * @param string $layout
     * @param string $language
     * @param string $stage
     * @param int $count
     * @param string $generatedby
     */
    private static function render_header(\TCPDF &$pdf, string $title, string $layout, string $language,
            string $stage, int $count, string $generatedby): void {
        $brand = self::brand_colors($stage);
        $pdf->SetFillColor($brand['r'], $brand['g'], $brand['b']);
        $pdf->Rect(0, 0, 210, 30, 'F');

        // Logo: constrain to fit without distortion.
        $logo = self::resolve_logo_path($stage);
        $logosize = 16;
        $svg = ($logo !== null && preg_match('/\.svg(\?|$)/i', $logo)) ? self::fetch_logo_svg($logo) : null;
        if ($svg !== null) {
            $pdf->ImageSVG('@' . $svg, 10, 7, $logosize, '', '', '', 'T', false);
        } else if ($logo !== null && !preg_match('/\.svg(\?|$)/i', $logo)) {
            // Use Image with max height, let width scale proportionally.
            $pdf->Image($logo, 10, 7, 0, $logosize, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            $pdf->Image('@' . self::monlau_logo($stage), 10, 7, $logosize, '', 'PNG', '', 'T', false, 300, '',
                false, false, 0, false, false, false);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 17);
        $pdf->SetXY(40, 4.5);
        $pdf->Cell(0, 8, $title, 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(40, 13.5);
        $pdf->Cell(0, 6, self::translate_title($layout, $language), 0, 1, 'L');

        // Informative line: "34 alumnos  ·  03/09/2026  ·  Nombre operador".
        $info = $count . ' ' . self::translate_word('students', $language)
            . '   ·   ' . userdate(time(), self::translate_word('dateformat', $language));
        if ($generatedby !== '') {
            $info .= '   ·   ' . $generatedby;
        }
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetXY(40, 21.5);
        $pdf->Cell(0, 5, $info, 0, 1, 'L');

        $pdf->SetTextColor(0, 0, 0);
        // Position below the coloured header bar to prevent overlap with the heading.
        $pdf->SetY(34);
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
        $endy = $pdf->GetY();
        $pdf->SetY($endy + 3);
        return $pdf->GetY();
    }

    /**
     * Render a card grid with an arbitrary number of columns and manual pagination.
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebasey Y position after header+heading on first page
     * @param array{r:int,g:int,b:int} $brand
     * @param int $columns
     */
    private static function render_grid(\TCPDF &$pdf, array $users, float $firstpagebasey, array $brand, int $columns): void {
        $pagew = 210;
        $left = $columns >= 7 ? 8.0 : ($columns >= 5 ? 9.0 : 11.0);
        $gutter = $columns >= 7 ? 2.5 : ($columns >= 5 ? 3.5 : 6.0);
        $cardw = ($pagew - $left * 2 - $gutter * ($columns - 1)) / $columns;
        $cardh = $cardw * 0.66 + 9.0;
        $rowgap = $columns >= 7 ? 2.5 : 3.0;
        $rowheight = $cardh + $rowgap;

        $pageheight = 297;
        $availableheight = $pageheight - $firstpagebasey - self::BOTTOM_MARGIN;
        $maxrows = max(1, (int) floor($availableheight / $rowheight));
        $usersperpage = $columns * $maxrows;
        $basestarty = $firstpagebasey;

        // Disable auto page break: the grid manages its own pagination.
        $pdf->SetAutoPageBreak(false);

        $indexoncurrentpage = 0;
        foreach ($users as $index => $user) {
            if ($index > 0 && $indexoncurrentpage >= $usersperpage) {
                $pdf->AddPage();
                $basestarty = self::CONTINUATION_TOP;
                $availableheight = $pageheight - $basestarty - self::BOTTOM_MARGIN;
                $maxrows = max(1, (int) floor($availableheight / $rowheight));
                $usersperpage = $columns * $maxrows;
                $indexoncurrentpage = 0;
            }

            $col = $indexoncurrentpage % $columns;
            $row = intdiv($indexoncurrentpage, $columns);

            $x = $left + ($col * ($cardw + $gutter));
            $y = $basestarty + ($row * $rowheight);

            self::render_student_card($pdf, $x, $y, $cardw, $cardh, $user, $index + 1, $brand);

            $indexoncurrentpage++;
        }

        $pdf->SetAutoPageBreak(true, self::BOTTOM_MARGIN);
    }

    /**
     * Render a directory layout (photo + name + email).
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebasey Y position after header+heading on first page
     * @param array{r:int,g:int,b:int} $brand
     * @param string $density compact|normal|large
     */
    private static function render_directory(\TCPDF &$pdf, array $users, float $firstpagebasey, array $brand,
            string $density = 'normal'): void {
        $pdf->SetTextColor(0, 0, 0);

        $columns = $density === 'large' ? 1 : 2;
        $pagew = 210;
        $left = 10;
        $gutter = 8;
        $colw = ($pagew - $left * 2 - ($columns > 1 ? $gutter : 0)) / $columns;
        $rowheight = $density === 'compact' ? 18.0 : 21.0;
        $cardh = $rowheight - 3;

        $pageheight = 297;
        $availableheight = $pageheight - $firstpagebasey - self::BOTTOM_MARGIN;
        $maxrows = max(1, (int) floor($availableheight / $rowheight));
        $usersperpage = $columns * $maxrows;
        $basestarty = $firstpagebasey;

        $pdf->SetAutoPageBreak(false);

        $indexoncurrentpage = 0;
        foreach ($users as $index => $user) {
            if ($index > 0 && $indexoncurrentpage >= $usersperpage) {
                $pdf->AddPage();
                $basestarty = self::CONTINUATION_TOP;
                $availableheight = $pageheight - $basestarty - self::BOTTOM_MARGIN;
                $maxrows = max(1, (int) floor($availableheight / $rowheight));
                $usersperpage = $columns * $maxrows;
                $indexoncurrentpage = 0;
            }

            $col = $indexoncurrentpage % $columns;
            $pagerow = intdiv($indexoncurrentpage, $columns);

            $x = $left + ($col * ($colw + $gutter));
            $y = $basestarty + ($pagerow * $rowheight);

            // Card background.
            $pdf->RoundedRect($x, $y, $colw, $cardh, 2, '1111', 'DF',
                ['width' => 0.2, 'color' => [226, 232, 240]], [248, 250, 252]);

            // Circular photo (or initials avatar) on the left of the card.
            $photod = $cardh - 5;
            self::draw_avatar($pdf, $user, $x + 3 + $photod / 2, $y + 2.5 + $photod / 2, $photod);

            $textx = $x + $photod + 9;
            $textw = $colw - $photod - 12;

            // Number badge overlapping the top-left of the photo.
            self::draw_number_badge($pdf, $x + 2.6, $y + 2.6, 3.4, 5, $index + 1, $brand);

            // Name.
            $pdf->SetXY($textx, $y + 3);
            $pdf->SetTextColor(45, 55, 72);
            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->Cell($textw, 5, self::fit_name(self::format_student_name($user), (int) round($textw * 0.62)), 0, 0, 'L');

            // Email.
            $pdf->SetXY($textx, $y + 9);
            $pdf->SetTextColor(110, 120, 135);
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->Cell($textw, 4, (string) ($user->email ?? ''), 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);

            $indexoncurrentpage++;
        }

        $pdf->SetAutoPageBreak(true, self::BOTTOM_MARGIN);
    }

    /**
     * Render a signature / attendance sheet: number, small avatar, name and a
     * ruled line to sign on, one student per row.
     *
     * @param \TCPDF $pdf
     * @param array $users
     * @param float $firstpagebasey Y position after header+heading on first page
     * @param array{r:int,g:int,b:int} $brand
     * @param string $language
     */
    private static function render_signatures(\TCPDF &$pdf, array $users, float $firstpagebasey, array $brand,
            string $language): void {
        $left = 12.0;
        $roww = 210 - $left * 2;
        $rowh = 12.5;
        $photod = 9.0;
        $namecolw = $roww * 0.48;
        $pageheight = 297;

        $headings = static function(float $y) use ($pdf, $left, $roww, $namecolw, $language): void {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($left, $y);
            $pdf->Cell(10, 5, self::translate_word('number', $language), 0, 0, 'L');
            $pdf->Cell($namecolw - 10, 5, self::translate_word('student', $language), 0, 0, 'L');
            $pdf->Cell($roww - $namecolw, 5, self::translate_word('signature', $language), 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
        };

        $pdf->SetAutoPageBreak(false);

        $headings($firstpagebasey);
        $y = $firstpagebasey + 6.5;

        foreach ($users as $index => $user) {
            if ($y + $rowh > $pageheight - self::BOTTOM_MARGIN) {
                $pdf->AddPage();
                $headings(self::CONTINUATION_TOP);
                $y = self::CONTINUATION_TOP + 6.5;
            }

            // Row separator.
            $pdf->SetDrawColor(224, 228, 234);
            $pdf->Line($left, $y + $rowh, $left + $roww, $y + $rowh);

            // Number.
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(110, 110, 110);
            $pdf->SetXY($left, $y);
            $pdf->Cell(10, $rowh, (string) ($index + 1), 0, 0, 'L', false, '', 0, false, 'T', 'M');

            // Avatar.
            self::draw_avatar($pdf, $user, $left + 10 + $photod / 2, $y + $rowh / 2, $photod);

            // Name.
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->SetXY($left + 10 + $photod + 4, $y);
            $pdf->Cell($namecolw - 14 - $photod, $rowh, self::fit_name(self::format_student_name($user), 44),
                0, 0, 'L', false, '', 0, false, 'T', 'M');

            // Signature line.
            $pdf->SetDrawColor(160, 160, 160);
            $pdf->Line($left + $namecolw, $y + $rowh - 3.5, $left + $roww, $y + $rowh - 3.5);

            $y += $rowh;
        }

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::BOTTOM_MARGIN);
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
        if ($stage === 'corporate') {
            return ['r' => 0, 'g' => 0, 'b' => 0];
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
            'corporate' => 'https://falcon-caramel42.monlau.com/pluginfile.php/1/theme_monlau/customimages/1788184148/monlaugroup.svg',
        ];

        $url = $urls[$stage] ?? $urls['fp'];
        if ($url !== '') {
            return $url;
        }

        return null;
    }

    /**
     * Fetch a remote SVG logo and strip clip-path references TCPDF cannot resolve.
     *
     * The source file points at <clipPath> ids it does not define; TCPDF then
     * emits "Undefined array key" / "foreach() on null" warnings from its SVG
     * parser without applying (or needing) the clip. Removing the references
     * keeps the same visual result and silences the warnings.
     *
     * @param string $url
     * @return string|null cleaned SVG markup, or null if it could not be fetched
     */
    private static function fetch_logo_svg(string $url): ?string {
        static $cache = [];
        if (array_key_exists($url, $cache)) {
            return $cache[$url];
        }

        $svg = null;
        try {
            $curl = new \curl();
            $response = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 5, 'CURLOPT_CONNECTTIMEOUT' => 5]);
            if (!$curl->get_errno() && is_string($response) && stripos($response, '<svg') !== false) {
                $svg = preg_replace('/<clipPath\b[^>]*>.*?<\/clipPath>/is', '', $response);
                $svg = preg_replace('/\s+clip-path\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $svg);
                $svg = preg_replace('/clip-path\s*:\s*url\([^)]*\)\s*;?/i', '', $svg);
            }
        } catch (\Throwable $e) {
            $svg = null;
        }

        return $cache[$url] = $svg;
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
     * Render a single student "card": rounded panel, circular photo (or initials
     * avatar) with a ring, a coloured number badge in the top-left corner and the
     * name underneath. All dimensions scale with the card width.
     *
     * @param \TCPDF $pdf
     * @param float $x card left
     * @param float $y card top
     * @param float $cardw card width
     * @param float $cardh card height
     * @param object $user student record with ->firstname, ->lastname, ->photo
     * @param int $number 1-based position shown in the badge
     * @param array{r:int,g:int,b:int} $brand badge fill colour
     */
    private static function render_student_card(\TCPDF &$pdf, float $x, float $y, float $cardw, float $cardh,
            object $user, int $number, array $brand): void {
        $small = $cardw < 26;
        $pad = $small ? 2.0 : 3.0;
        $imgd = max(8.0, min($cardw - $pad * 2 - 2.0, $cardh - 13.0));
        $namefont = (int) round(max(5, min(9, $cardw * 0.21)));
        $nameh = $namefont * 0.42;
        $namemaxh = $nameh * 2.4 + 1.0;
        $badger = max(2.6, min(4.2, $cardw * 0.105));
        $badgefont = (int) round(max(5, min(7, $badger * 1.7)));
        $namechars = (int) round($cardw * 0.82);

        // 1. Card panel.
        $pdf->RoundedRect($x, $y, $cardw, $cardh, $small ? 1.8 : 2.4, '1111', 'DF',
            ['width' => 0.2, 'color' => [226, 232, 240]], [248, 250, 252]);

        // 2. Avatar near the top, centred horizontally.
        $cx = $x + $cardw / 2;
        $imgy = $y + $pad + 1.0;
        self::draw_avatar($pdf, $user, $cx, $imgy + $imgd / 2, $imgd);

        // 3. Name block under the avatar (up to two lines).
        $pdf->SetFont('helvetica', 'B', $namefont);
        $pdf->SetTextColor(45, 55, 72);
        $pdf->SetXY($x + 1, $imgy + $imgd + ($small ? 1.0 : 1.6));
        $pdf->MultiCell($cardw - 2, $nameh, self::fit_name(self::format_student_name($user), $namechars),
            0, 'C', false, 0, '', '', true, 0, false, true, $namemaxh, 'T');

        // 4. Number badge in the top-left corner of the card.
        self::draw_number_badge($pdf, $x + $badger + 1.2, $y + $badger + 1.2, $badger, $badgefont, $number, $brand);

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Draw a student's circular photo, or an initials avatar when there is no photo.
     *
     * @param \TCPDF $pdf
     * @param object $user
     * @param float $cx circle centre X
     * @param float $cy circle centre Y
     * @param float $d circle diameter
     */
    private static function draw_avatar(\TCPDF &$pdf, object $user, float $cx, float $cy, float $d): void {
        if (!empty($user->photo)) {
            self::draw_circular_photo($pdf, $user->photo, $cx, $cy, $d);
        } else {
            self::draw_initials_avatar($pdf, self::student_initials($user), self::format_student_name($user), $cx, $cy, $d);
        }
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

        self::draw_avatar_ring($pdf, $cx, $cy, $r);
    }

    /**
     * Draw a coloured circle with the student's initials in white.
     *
     * @param \TCPDF $pdf
     * @param string $initials 1-2 upper-case letters
     * @param string $colourseed string the background colour is derived from
     * @param float $cx circle centre X
     * @param float $cy circle centre Y
     * @param float $d circle diameter
     */
    private static function draw_initials_avatar(\TCPDF &$pdf, string $initials, string $colourseed,
            float $cx, float $cy, float $d): void {
        $r = $d / 2;
        $colour = self::avatar_color($colourseed);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'F', [], $colour);

        $twochar = mb_strlen($initials) >= 2;
        $fontpt = (int) round($d * ($twochar ? 1.15 : 1.55));
        $pdf->SetFont('helvetica', 'B', max(6, $fontpt));
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($cx - $r, $cy - $r);
        $pdf->Cell($d, $d, $initials, 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->SetTextColor(0, 0, 0);

        self::draw_avatar_ring($pdf, $cx, $cy, $r);
    }

    /**
     * Draw the thin white + grey ring shared by photo and initials avatars.
     *
     * @param \TCPDF $pdf
     * @param float $cx
     * @param float $cy
     * @param float $r
     */
    private static function draw_avatar_ring(\TCPDF &$pdf, float $cx, float $cy, float $r): void {
        $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.5, 'color' => [255, 255, 255]]);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.2, 'color' => [205, 213, 224]]);
    }

    /**
     * Deterministic avatar background colour derived from a string.
     *
     * @param string $seed
     * @return array{0:int,1:int,2:int}
     */
    private static function avatar_color(string $seed): array {
        $palette = [
            [79, 114, 189], [39, 131, 118], [193, 116, 73], [124, 92, 176],
            [67, 145, 91], [187, 92, 128], [107, 132, 89], [74, 125, 173],
            [163, 118, 60], [96, 107, 168],
        ];
        $index = abs(crc32(mb_strtolower($seed))) % count($palette);
        return $palette[$index];
    }

    /**
     * First letter of the first name + first letter of the last name, upper-cased.
     *
     * @param object $user
     * @return string
     */
    private static function student_initials(object $user): string {
        $first = trim((string) ($user->firstname ?? ''));
        $last = trim((string) ($user->lastname ?? ''));
        $initials = mb_strtoupper(($first !== '' ? mb_substr($first, 0, 1) : '')
            . ($last !== '' ? mb_substr($last, 0, 1) : ''));
        return $initials !== '' ? $initials : '?';
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
    private static function draw_number_badge(\TCPDF &$pdf, float $cx, float $cy, float $r, int $font, int $number,
            array $brand): void {
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
     * Translate the page header subtitle based on the selected language.
     *
     * @param string $layout
     * @param string $language
     * @return string
     */
    private static function translate_title(string $layout, string $language): string {
        $map = [
            'ca' => [
                'orla' => 'Llistat fotogràfic',
                'grid6' => 'Llistat fotogràfic compacte',
                'directory' => 'Directori d’alumnes',
                'signatures' => 'Full de signatures',
                'roster' => 'Llistat fotogràfic',
            ],
            'es' => [
                'orla' => 'Listado fotográfico',
                'grid6' => 'Listado fotográfico compacto',
                'directory' => 'Directorio de alumnos',
                'signatures' => 'Hoja de firmas',
                'roster' => 'Listado fotográfico',
            ],
            'en' => [
                'orla' => 'Photo list',
                'grid6' => 'Compact photo list',
                'directory' => 'Student directory',
                'signatures' => 'Signature sheet',
                'roster' => 'Photo list',
            ],
        ];
        return $map[$language][$layout] ?? $map['ca']['orla'];
    }

    /**
     * Translate a handful of single words / patterns used inside the PDF chrome.
     *
     * @param string $key students|page|number|student|signature|dateformat
     * @param string $language
     * @return string
     */
    private static function translate_word(string $key, string $language): string {
        $map = [
            'ca' => [
                'students' => 'alumnes', 'page' => 'Pàg.', 'number' => 'Núm.',
                'student' => 'Alumne', 'signature' => 'Signatura', 'dateformat' => '%d/%m/%Y',
            ],
            'es' => [
                'students' => 'alumnos', 'page' => 'Pág.', 'number' => 'Nº',
                'student' => 'Alumno', 'signature' => 'Firma', 'dateformat' => '%d/%m/%Y',
            ],
            'en' => [
                'students' => 'students', 'page' => 'Page', 'number' => 'No.',
                'student' => 'Student', 'signature' => 'Signature', 'dateformat' => '%Y-%m-%d',
            ],
        ];
        return $map[$language][$key] ?? ($map['ca'][$key] ?? $key);
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
