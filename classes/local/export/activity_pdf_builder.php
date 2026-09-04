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
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Build the "Control d'activitat" PDF: an A4 landscape, printable roster for
 * a cohort with configurable pen-and-paper columns (attendance, transport,
 * authorisation...), meant for outings, workshops and similar activities.
 *
 * Deliberately a separate class from {@see pdf_builder} rather than a new
 * layout inside it: that class always builds a portrait ('P') 210mm-wide
 * document, while this one needs landscape 297mm plus a dynamic-width
 * table, so sharing its private rendering methods directly is not possible.
 * A handful of small visual helpers (avatar, brand colours, logo, filename
 * sanitising) are intentionally duplicated here rather than extracted into
 * a shared trait, to avoid any risk of a refactor changing the visual
 * output of the four layouts already in production.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_pdf_builder {

    /** @var float Full page width, landscape A4, in mm. */
    private const PAGE_WIDTH = 297.0;

    /** @var float Full page height, landscape A4, in mm. */
    private const PAGE_HEIGHT = 210.0;

    /** @var float Left/right page margin. */
    private const MARGIN = 10.0;

    /** @var float Width of the always-present "Núm." column. */
    private const NUM_WIDTH = 10.0;

    /** @var float Minimum width of the always-present "Alumne" column. */
    private const ALUMNE_MIN_WIDTH = 56.0;

    /** @var float Space reserved at the bottom of every page for the footer. */
    private const BOTTOM_MARGIN = 16.0;

    /** @var float Y coordinate content starts at on pages after the first. */
    private const CONTINUATION_TOP = 14.0;

    /** @var int Maximum number of columns beyond the mandatory Núm./Alumne. */
    public const MAX_EXTRA_COLUMNS = 6;

    /** @var float Minimum row height, used for large cohorts / many columns. */
    private const MIN_ROW_HEIGHT = 5.6;

    /** @var float Maximum row height, used for small cohorts. */
    private const MAX_ROW_HEIGHT = 8.6;

    /** @var float Height of the table header row. */
    private const TABLE_HEADER_HEIGHT = 6.0;

    /** @var float Height of the final "observacions generals" box, when shown. */
    private const GENERALOBS_HEIGHT = 20.0;

    /**
     * Definitions for the ten selectable standard columns: type and width (mm).
     * "observacions" has no fixed width: it always absorbs the remaining space.
     */
    private const STANDARD_COLUMN_DEFS = [
        'present' => ['type' => 'checkbox', 'width' => 16.0],
        'autoritzacio' => ['type' => 'checkbox', 'width' => 20.0],
        'transport' => ['type' => 'checkbox', 'width' => 18.0],
        'pagament' => ['type' => 'checkbox', 'width' => 18.0],
        'menu' => ['type' => 'checkbox', 'width' => 15.0],
        'epi' => ['type' => 'checkbox', 'width' => 15.0],
        'material' => ['type' => 'checkbox', 'width' => 18.0],
        'grupequip' => ['type' => 'text', 'width' => 20.0],
        'hora' => ['type' => 'text', 'width' => 16.0],
        'observacions' => ['type' => 'text', 'width' => 0.0],
    ];

    /**
     * Whether a column key is one of the ten built-in standard columns.
     *
     * @param string $key
     * @return bool
     */
    public static function is_standard_column(string $key): bool {
        return isset(self::STANDARD_COLUMN_DEFS[$key]);
    }

    /**
     * The fixed type ('checkbox'|'text') of a standard column, or null if $key isn't one.
     *
     * @param string $key
     * @return string|null
     */
    public static function standard_column_type(string $key): ?string {
        return self::STANDARD_COLUMN_DEFS[$key]['type'] ?? null;
    }

    /**
     * Build the PDF document.
     *
     * @param stdClass[] $members cohort members, each with id, firstname, lastname, picture.
     * @param string $cohortname
     * @param array $activity name, date (Y-m-d or ''), place, responsables.
     * @param array $columns ordered list of {key, label, type: checkbox|text}. Standard keys
     *     use their own translated label regardless of the label supplied; custom keys
     *     (anything not in STANDARD_COLUMN_DEFS) use the supplied label as-is.
     * @param array $options language, stage, showphotos, showgeneralobs, order, generatedby.
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $members, string $cohortname, array $activity, array $columns,
            array $options = []): array {
        global $CFG;

        require_once($CFG->libdir . '/tcpdf/tcpdf.php');

        $extracolumns = array_values(array_filter($columns, static function(array $column): bool {
            return ($column['key'] ?? '') !== '';
        }));
        if (count($extracolumns) > self::MAX_EXTRA_COLUMNS) {
            throw new moodle_exception('error_activitytoomanycolumns', 'local_profilephoto', '', self::MAX_EXTRA_COLUMNS);
        }

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = in_array($options['stage'] ?? 'fp', ['fp', 'eso', 'batx', 'corporate'], true) ? $options['stage'] : 'fp';
        $order = in_array($options['order'] ?? 'lastname', ['lastname', 'firstname', 'cohort'], true)
            ? $options['order'] : 'lastname';
        $showphotos = (bool) ($options['showphotos'] ?? true);
        $showgeneralobs = (bool) ($options['showgeneralobs'] ?? true);
        $generatedby = trim((string) ($options['generatedby'] ?? ''));

        $users = [];
        foreach ($members as $member) {
            $user = new stdClass();
            $user->id = (int) $member->id;
            $user->firstname = (string) ($member->firstname ?? '');
            $user->lastname = (string) ($member->lastname ?? '');
            $user->photo = $showphotos && ((int) ($member->picture ?? 0)) > 0
                ? self::get_icon_content($user->id) : null;
            $users[] = $user;
        }

        if ($order === 'firstname') {
            usort($users, static function($a, $b): int {
                $cmp = strcmp((string) $a->firstname, (string) $b->firstname);
                return $cmp !== 0 ? $cmp : strcmp((string) $a->lastname, (string) $b->lastname);
            });
        } else if ($order === 'lastname') {
            usort($users, static function($a, $b): int {
                $cmp = strcmp((string) $a->lastname, (string) $b->lastname);
                return $cmp !== 0 ? $cmp : strcmp((string) $a->firstname, (string) $b->firstname);
            });
        }
        // order === 'cohort': keep the order the members were supplied in.

        $count = count($users);

        [$colwidths, $alumnewidth] = self::compute_column_widths($extracolumns);

        $activitydate = self::resolve_activity_date($activity['date'] ?? '');
        $filenamedate = $activitydate !== null ? $activitydate->format('Ymd') : userdate(time(), '%Y%m%d');
        $filename = self::sanitize_filename($cohortname) . '_control-activitat_' . $filenamedate . '.pdf';

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $path = $tempdir . '/' . $filename;

        $brand = self::brand_colors($stage);

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {

            /** @var string Left-aligned footer caption (document title). */
            public $footercaption = '';

            /** @var string Right-aligned footer caption ("Pàg. x / y", already localised). */
            public $footerpagelabel = 'Pàg.';

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
        $pdf->SetMargins(self::MARGIN, 10, self::MARGIN);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetCreator('Moodle local_profilephoto');
        $pdf->SetAuthor('Moodle local_profilephoto');
        $pdf->SetTitle($cohortname . ' - ' . self::translate_word('subtitle', $language));
        $pdf->footercaption = $cohortname . ' · ' . self::translate_word('subtitle', $language);
        $pdf->footerpagelabel = self::translate_word('page', $language);

        $pdf->AddPage();

        self::render_brand_header($pdf, $cohortname, $language, $stage, $count, $activitydate, $generatedby);
        $blocktop = self::render_activity_block($pdf, $activity, $activitydate, $count, $language, $brand);

        self::render_table($pdf, $users, $extracolumns, $colwidths, $alumnewidth, $blocktop, $language, $brand,
            $showphotos, $showgeneralobs);

        $pdf->Output($path, 'F');

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $count,
        ];
    }

    /**
     * Compute the width (mm) of every extra column plus the Alumne column.
     *
     * @param array $columns ordered {key, type} list (already capped at MAX_EXTRA_COLUMNS).
     * @return array{0: array<string,float>, 1: float} widths keyed by column key, then Alumne's width.
     */
    private static function compute_column_widths(array $columns): array {
        $available = self::PAGE_WIDTH - (self::MARGIN * 2) - self::NUM_WIDTH;

        $hasobservacions = false;
        foreach ($columns as $column) {
            if (($column['key'] ?? '') === 'observacions') {
                $hasobservacions = true;
                break;
            }
        }

        $fixedsum = 0.0;
        $widths = [];
        foreach ($columns as $column) {
            $key = (string) $column['key'];
            if ($key === 'observacions') {
                continue;
            }
            $width = self::column_width($key, (string) ($column['type'] ?? 'checkbox'));
            $widths[$key] = $width;
            $fixedsum += $width;
        }

        if ($hasobservacions) {
            $alumnewidth = self::ALUMNE_MIN_WIDTH;
            $remaining = $available - $alumnewidth - $fixedsum;
            $widths['observacions'] = max($remaining, 25.0);
        } else {
            $alumnewidth = max(self::ALUMNE_MIN_WIDTH, $available - $fixedsum);
        }

        return [$widths, $alumnewidth];
    }

    /**
     * Resolve the mm width for a given column key/type.
     *
     * @param string $key
     * @param string $type checkbox|text
     * @return float
     */
    private static function column_width(string $key, string $type): float {
        if (isset(self::STANDARD_COLUMN_DEFS[$key])) {
            return self::STANDARD_COLUMN_DEFS[$key]['width'];
        }
        // Custom column: default width by type.
        return $type === 'text' ? 22.0 : 16.0;
    }

    /**
     * Parse a "Y-m-d" activity date string into a DateTime, or null when empty/invalid.
     *
     * @param string $raw
     * @return \DateTime|null
     */
    private static function resolve_activity_date(string $raw): ?\DateTime {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $raw);
        if ($date === false) {
            return null;
        }
        return $date;
    }

    /**
     * Render the coloured brand header bar (logo, cohort name, subtitle, info line).
     *
     * @param \TCPDF $pdf
     * @param string $cohortname
     * @param string $language
     * @param string $stage
     * @param int $count
     * @param \DateTime|null $activitydate
     * @param string $generatedby
     */
    private static function render_brand_header(\TCPDF &$pdf, string $cohortname, string $language, string $stage,
            int $count, ?\DateTime $activitydate, string $generatedby): void {
        $brand = self::brand_colors($stage);
        $pdf->SetFillColor($brand['r'], $brand['g'], $brand['b']);
        $pdf->Rect(0, 0, self::PAGE_WIDTH, 22, 'F');

        $logo = self::resolve_logo_path($stage);
        $logosize = 12;
        $svg = ($logo !== null && preg_match('/\.svg(\?|$)/i', $logo)) ? self::fetch_logo_svg($logo) : null;
        if ($svg !== null) {
            $pdf->ImageSVG('@' . $svg, 8, 5, $logosize, '', '', '', 'T', false);
        } else if ($logo !== null) {
            $pdf->Image($logo, 8, 5, 0, $logosize, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(30, 3.2);
        $pdf->Cell(150, 7, self::fit_text($pdf, $cohortname, 148), 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(30, 10.2);
        $pdf->Cell(150, 5, self::translate_word('subtitle', $language), 0, 1, 'L');

        $info = $count . ' ' . self::translate_word('students', $language);
        if ($activitydate !== null) {
            $info .= '   ·   ' . $activitydate->format('d/m/Y');
        }
        if ($generatedby !== '') {
            $info .= '   ·   ' . $generatedby;
        }
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(30, 15.5);
        $pdf->Cell(240, 5, self::fit_text($pdf, $info, 250), 0, 1, 'L');

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Render the compact, two-row activity info block (name/date/place, then
     * responsables/students/presents/absents), capped at ~20mm tall.
     *
     * @param \TCPDF $pdf
     * @param array $activity name, date, place, responsables (already trimmed strings).
     * @param \DateTime|null $activitydate
     * @param int $count
     * @param string $language
     * @param array{r:int,g:int,b:int} $brand
     * @return float Y coordinate the table should start below.
     */
    private static function render_activity_block(\TCPDF &$pdf, array $activity, ?\DateTime $activitydate, int $count,
            string $language, array $brand): float {
        $top = 24.0;
        $height = 19.0;
        $left = self::MARGIN;
        $width = self::PAGE_WIDTH - self::MARGIN * 2;

        $pdf->SetFillColor(246, 247, 250);
        $pdf->Rect($left, $top, $width, $height, 'F');

        $name = trim((string) ($activity['name'] ?? ''));
        $place = trim((string) ($activity['place'] ?? ''));
        $responsables = self::format_responsables((string) ($activity['responsables'] ?? ''));
        // An empty field is left blank (just the label) rather than filled with a "—"
        // placeholder, which reads as noise once several fields on the sheet are unused.
        $datestr = $activitydate !== null ? $activitydate->format('d/m/Y') : '';

        $row1y = $top + 3.0;
        $row2y = $top + 10.5;
        $padx = $left + 4.0;

        self::render_kv_row($pdf, $padx, $row1y, $width - 8.0, [
            [self::translate_word('activity', $language), $name, 78.0],
            [self::translate_word('date', $language), $datestr, 34.0],
            [self::translate_word('place', $language), $place, 60.0],
        ]);

        self::render_kv_row($pdf, $padx, $row2y, $width - 8.0, [
            [self::translate_word('responsables', $language), $responsables, 86.0],
            [ucfirst(self::translate_word('students', $language)), (string) $count, 30.0],
            [self::translate_word('present', $language), null, 28.0],
            [self::translate_word('absent', $language), null, 28.0],
        ]);

        $pdf->SetTextColor(0, 0, 0);
        return $top + $height + 2.0;
    }

    /**
     * Render one row of "Label: value" pairs, each pair advancing X by its own budget.
     *
     * @param \TCPDF $pdf
     * @param float $x
     * @param float $y
     * @param float $maxwidth
     * @param array $pairs list of [label, value, budget] tuples. A null value draws a
     *     blank line to write on (sized to fill the available width) instead of text.
     */
    private static function render_kv_row(\TCPDF &$pdf, float $x, float $y, float $maxwidth, array $pairs): void {
        // Reserved blank gap before the next field, so a value that fills its whole
        // budget (a truncated "…" or a fill_line() blank) never visually touches it.
        $gap = 3.0;

        $cursor = $x;
        $limit = $x + $maxwidth;
        foreach ($pairs as [$label, $value, $budget]) {
            if ($cursor >= $limit) {
                break;
            }
            $budget = min($budget, $limit - $cursor);
            $contentwidth = max(0.0, $budget - $gap);

            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(70, 78, 90);
            $labeltext = $label . ': ';
            // Never shrink the label below its actual rendered width: Cell() prints at
            // the exact width given, so under-allocating it here doesn't save space, it
            // just makes the value start printing before the label ends and overlap it.
            $labelw = min($pdf->GetStringWidth($labeltext) + 1.0, $contentwidth);
            $pdf->SetXY($cursor, $y);
            $pdf->Cell($labelw, 5, $labeltext, 0, 0, 'L');

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(30, 34, 40);
            $valuew = max(0.0, $contentwidth - $labelw);
            $pdf->SetXY($cursor + $labelw, $y);
            $valuetext = $value === null ? self::fill_line($pdf, $valuew) : self::fit_text($pdf, $value, $valuew);
            $pdf->Cell($valuew, 5, $valuetext, 0, 0, 'L');

            $cursor += $budget;
        }
    }

    /**
     * Build an underscore line sized to fill (without overflowing) the given width, for a
     * blank "write here" value - avoids fit_text() truncating a fixed-length placeholder
     * with an ellipsis when it doesn't happen to fit the column exactly.
     *
     * @param \TCPDF $pdf font/size already set on it.
     * @param float $maxwidth
     * @return string
     */
    private static function fill_line(\TCPDF $pdf, float $maxwidth): string {
        $line = '';
        while ($pdf->GetStringWidth($line . '_') <= $maxwidth) {
            $line .= '_';
        }
        return $line;
    }

    /**
     * Render the student table: header row (repeated on every page), one row
     * per student, and (optionally) a general-observations box at the end.
     *
     * @param \TCPDF $pdf
     * @param stdClass[] $users
     * @param array $extracolumns ordered {key, label, type} list.
     * @param array<string,float> $colwidths
     * @param float $alumnewidth
     * @param float $firstpagetop
     * @param string $language
     * @param array{r:int,g:int,b:int} $brand
     * @param bool $showphotos
     * @param bool $showgeneralobs
     */
    private static function render_table(\TCPDF &$pdf, array $users, array $extracolumns, array $colwidths,
            float $alumnewidth, float $firstpagetop, string $language, array $brand, bool $showphotos,
            bool $showgeneralobs): void {
        $count = count($users);

        $tableheaderdraw = static function(float $y) use ($pdf, $extracolumns, $colwidths, $alumnewidth, $language): void {
            self::draw_table_header($pdf, $y, $extracolumns, $colwidths, $alumnewidth, $language);
        };

        $availablefirstpage = self::PAGE_HEIGHT - $firstpagetop - self::TABLE_HEADER_HEIGHT - self::BOTTOM_MARGIN;

        $rowheight = $count > 0
            ? self::clamp($availablefirstpage / max($count, 1), self::MIN_ROW_HEIGHT, self::MAX_ROW_HEIGHT)
            : self::MAX_ROW_HEIGHT;

        $tableheaderdraw($firstpagetop);
        $y = $firstpagetop + self::TABLE_HEADER_HEIGHT;
        $pagebottom = self::PAGE_HEIGHT - self::BOTTOM_MARGIN;

        foreach ($users as $index => $user) {
            if ($y + $rowheight > $pagebottom) {
                $pdf->AddPage();
                $tableheaderdraw(self::CONTINUATION_TOP);
                $y = self::CONTINUATION_TOP + self::TABLE_HEADER_HEIGHT;
                $pagebottom = self::PAGE_HEIGHT - self::BOTTOM_MARGIN;
            }

            self::draw_table_row($pdf, $y, $rowheight, $index, $user, $extracolumns, $colwidths, $alumnewidth,
                $showphotos);
            $y += $rowheight;
        }

        if ($showgeneralobs) {
            if ($y + self::GENERALOBS_HEIGHT > $pagebottom) {
                $pdf->AddPage();
                $y = self::CONTINUATION_TOP;
            } else {
                $y += 3.0;
            }
            self::render_general_obs($pdf, $y, $language);
        }
    }

    /**
     * Draw the table header row (Núm., Alumne, then every extra column).
     *
     * @param \TCPDF $pdf
     * @param float $y
     * @param array $extracolumns
     * @param array<string,float> $colwidths
     * @param float $alumnewidth
     * @param string $language
     */
    private static function draw_table_header(\TCPDF &$pdf, float $y, array $extracolumns, array $colwidths,
            float $alumnewidth, string $language): void {
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(60, 68, 80);
        $pdf->SetFillColor(232, 236, 242);
        $pdf->SetDrawColor(200, 206, 216);

        $x = self::MARGIN;
        $h = self::TABLE_HEADER_HEIGHT;

        $pdf->SetXY($x, $y);
        $pdf->Cell(self::NUM_WIDTH, $h, self::translate_word('num', $language), 1, 0, 'C', true);
        $x += self::NUM_WIDTH;

        $pdf->SetXY($x, $y);
        $pdf->Cell($alumnewidth, $h, self::translate_word('student', $language), 1, 0, 'L', true);
        $x += $alumnewidth;

        foreach ($extracolumns as $column) {
            $key = (string) $column['key'];
            $width = $colwidths[$key] ?? self::column_width($key, (string) ($column['type'] ?? 'checkbox'));
            $label = self::column_label($column, $language);
            $pdf->SetXY($x, $y);
            $pdf->Cell($width, $h, self::fit_text($pdf, $label, $width - 1.5), 1, 0, 'C', true);
            $x += $width;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * Draw one student row: number, avatar+name, then every extra column's cell.
     *
     * @param \TCPDF $pdf
     * @param float $y
     * @param float $rowheight
     * @param int $index 0-based position
     * @param stdClass $user
     * @param array $extracolumns
     * @param array<string,float> $colwidths
     * @param float $alumnewidth
     * @param bool $showphotos
     */
    private static function draw_table_row(\TCPDF &$pdf, float $y, float $rowheight, int $index, stdClass $user,
            array $extracolumns, array $colwidths, float $alumnewidth, bool $showphotos): void {
        $fill = $index % 2 === 1;
        $pdf->SetFillColor(249, 250, 252);
        $pdf->SetDrawColor(216, 221, 229);

        $x = self::MARGIN;

        // Número.
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(90, 96, 105);
        $pdf->SetXY($x, $y);
        $pdf->Cell(self::NUM_WIDTH, $rowheight, (string) ($index + 1), 1, 0, 'C', $fill, '', 0, false, 'T', 'M');
        $x += self::NUM_WIDTH;

        // Alumne: box first, then avatar + name overlaid.
        $pdf->SetXY($x, $y);
        $pdf->Cell($alumnewidth, $rowheight, '', 1, 0, 'L', $fill);

        $textx = $x + 2.0;
        if ($showphotos) {
            $photod = min($rowheight - 1.6, 7.5);
            $photod = max($photod, 3.2);
            self::draw_avatar($pdf, $user, $x + 1.6 + $photod / 2, $y + $rowheight / 2, $photod);
            $textx = $x + 1.6 + $photod + 2.0;
        }
        $pdf->SetFont('helvetica', '', min(8.5, max(6.0, $rowheight * 0.95)));
        $pdf->SetTextColor(30, 34, 40);
        $pdf->SetXY($textx, $y);
        $pdf->Cell($alumnewidth - ($textx - $x) - 1.5, $rowheight,
            self::fit_text($pdf, self::format_student_name($user), $alumnewidth - ($textx - $x) - 1.5),
            0, 0, 'L', false, '', 0, false, 'T', 'M');
        $x += $alumnewidth;

        // draw_avatar() may have left TCPDF's active fill colour set to the initials
        // avatar's own background (Circle() with a fill colour does not restore the
        // previous one), which would otherwise bleed into the zebra-striped cells below.
        $pdf->SetFillColor(249, 250, 252);

        foreach ($extracolumns as $column) {
            $key = (string) $column['key'];
            $type = (string) ($column['type'] ?? 'checkbox');
            $width = $colwidths[$key] ?? self::column_width($key, $type);

            $pdf->SetXY($x, $y);
            $pdf->Cell($width, $rowheight, '', 1, 0, 'C', $fill);

            if ($type === 'checkbox') {
                $side = min($rowheight * 0.42, 4.2);
                $cx = $x + $width / 2;
                $cy = $y + $rowheight / 2;
                $pdf->SetDrawColor(120, 128, 140);
                $pdf->Rect($cx - $side / 2, $cy - $side / 2, $side, $side, 'D');
                $pdf->SetDrawColor(216, 221, 229);
            }
            // Text-type columns are left as an empty boxed cell: the box itself is the
            // space to write in, no extra line needed inside it.

            $x += $width;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * Resolve a column's display label: standard columns always use their own
     * translation (ignoring whatever the client sent); custom columns use the
     * operator-supplied name.
     *
     * @param array $column {key, label, type}
     * @param string $language
     * @return string
     */
    private static function column_label(array $column, string $language): string {
        $key = (string) $column['key'];
        if (isset(self::STANDARD_COLUMN_DEFS[$key])) {
            return self::translate_column($key, $language);
        }
        $label = trim((string) ($column['label'] ?? ''));
        return $label !== '' ? $label : self::translate_word('customcolumn', $language);
    }

    /**
     * Render the final "Incidències / observacions generals" box.
     *
     * @param \TCPDF $pdf
     * @param float $y
     * @param string $language
     */
    private static function render_general_obs(\TCPDF &$pdf, float $y, string $language): void {
        $left = self::MARGIN;
        $width = self::PAGE_WIDTH - self::MARGIN * 2;
        $height = self::GENERALOBS_HEIGHT;

        $pdf->SetDrawColor(190, 196, 206);
        $pdf->Rect($left, $y, $width, $height, 'D');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(90, 96, 105);
        $pdf->SetXY($left + 2.5, $y + 1.5);
        $pdf->Cell($width - 5, 4, self::translate_word('generalobs', $language), 0, 0, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * Turn a free-text "responsables" field into a "·"-separated list.
     *
     * @param string $raw
     * @return string
     */
    private static function format_responsables(string $raw): string {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $parts = array_filter(array_map('trim', preg_split('/[,;\n]+/', $raw)));
        return implode(' · ', $parts);
    }

    /**
     * Clamp a value between a minimum and a maximum.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    private static function clamp(float $value, float $min, float $max): float {
        return max($min, min($max, $value));
    }

    /**
     * Shorten text with getStringWidth-aware truncation, using the PDF's current font.
     *
     * @param \TCPDF $pdf
     * @param string $text
     * @param float $maxwidth
     * @return string
     */
    private static function fit_text(\TCPDF $pdf, string $text, float $maxwidth): string {
        if ($maxwidth <= 0 || $pdf->GetStringWidth($text) <= $maxwidth) {
            return $text;
        }
        while (mb_strlen($text) > 1 && $pdf->GetStringWidth(rtrim($text) . '…') > $maxwidth) {
            $text = mb_substr($text, 0, -1);
        }
        return rtrim($text) . '…';
    }

    /**
     * Draw a student's circular photo, or an initials avatar when there is no photo.
     *
     * @param \TCPDF $pdf
     * @param stdClass $user
     * @param float $cx
     * @param float $cy
     * @param float $d
     */
    private static function draw_avatar(\TCPDF &$pdf, stdClass $user, float $cx, float $cy, float $d): void {
        if (!empty($user->photo)) {
            $r = $d / 2;
            $pdf->StartTransform();
            $pdf->Circle($cx, $cy, $r, 0, 360, 'CNZ');
            $pdf->Image('@' . $user->photo, $cx - $r, $cy - $r, $d, $d, '', '', '', false, 300,
                '', false, false, 0, 'C', false, false);
            $pdf->StopTransform();
            $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.15, 'color' => [205, 213, 224]]);
            return;
        }

        $initials = self::student_initials($user);
        $colour = self::avatar_color(self::format_student_name($user));
        $r = $d / 2;
        $pdf->Circle($cx, $cy, $r, 0, 360, 'F', [], $colour);

        $fontpt = max(4.5, min(7, $d * ($initials !== '' && mb_strlen($initials) >= 2 ? 1.05 : 1.4)));
        $pdf->SetFont('helvetica', 'B', $fontpt);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($cx - $r, $cy - $r);
        $pdf->Cell($d, $d, $initials, 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Circle($cx, $cy, $r, 0, 360, 'D', ['width' => 0.15, 'color' => [205, 213, 224]]);
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
     * @param stdClass $user
     * @return string
     */
    private static function student_initials(stdClass $user): string {
        $first = trim((string) ($user->firstname ?? ''));
        $last = trim((string) ($user->lastname ?? ''));
        $initials = mb_strtoupper(($first !== '' ? mb_substr($first, 0, 1) : '')
            . ($last !== '' ? mb_substr($last, 0, 1) : ''));
        return $initials !== '' ? $initials : '?';
    }

    /**
     * Format a student name as "Apellido1 Apellido2, Nombre".
     *
     * @param stdClass $user
     * @return string
     */
    private static function format_student_name(stdClass $user): string {
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
     * Provide the Monlau brand palette (mirrors pdf_builder::brand_colors()).
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
     * Resolve a Monlau logo URL for the given stage (mirrors pdf_builder).
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
        return $urls[$stage] ?? $urls['fp'];
    }

    /**
     * Fetch a remote SVG logo and strip clip-path references TCPDF cannot resolve.
     *
     * @param string $url
     * @return string|null
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
     * Sanitize a cohort name for use in a filename.
     *
     * @param string $name
     * @return string
     */
    private static function sanitize_filename(string $name): string {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($name));
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        $name = substr($name, 0, 50);
        return $name ?: 'export';
    }

    /**
     * Translate a standard column key to its display label.
     *
     * @param string $key
     * @param string $language
     * @return string
     */
    private static function translate_column(string $key, string $language): string {
        $map = [
            'ca' => [
                'present' => 'Present', 'autoritzacio' => 'Autorització', 'transport' => 'Transport',
                'pagament' => 'Pagament', 'menu' => 'Menú', 'epi' => 'EPI', 'material' => 'Material',
                'grupequip' => 'Grup / Equip', 'hora' => 'Hora', 'observacions' => 'Observacions',
            ],
            'es' => [
                'present' => 'Presente', 'autoritzacio' => 'Autorización', 'transport' => 'Transporte',
                'pagament' => 'Pago', 'menu' => 'Menú', 'epi' => 'EPI', 'material' => 'Material',
                'grupequip' => 'Grupo / Equipo', 'hora' => 'Hora', 'observacions' => 'Observaciones',
            ],
            'en' => [
                'present' => 'Present', 'autoritzacio' => 'Authorisation', 'transport' => 'Transport',
                'pagament' => 'Payment', 'menu' => 'Menu', 'epi' => 'PPE', 'material' => 'Material',
                'grupequip' => 'Group / Team', 'hora' => 'Time', 'observacions' => 'Notes',
            ],
        ];
        return $map[$language][$key] ?? ($map['ca'][$key] ?? $key);
    }

    /**
     * Translate a handful of single words / labels used inside this PDF's chrome.
     *
     * @param string $key
     * @param string $language
     * @return string
     */
    private static function translate_word(string $key, string $language): string {
        $map = [
            'ca' => [
                'subtitle' => 'Control d’activitat', 'students' => 'alumnes', 'page' => 'Pàg.',
                'num' => 'Núm.', 'student' => 'Alumne', 'activity' => 'Activitat', 'date' => 'Data',
                'place' => 'Lloc', 'responsables' => 'Responsables', 'present' => 'Presents',
                'absent' => 'Absents', 'generalobs' => 'Incidències / observacions generals',
                'customcolumn' => 'Columna',
            ],
            'es' => [
                'subtitle' => 'Control de actividad', 'students' => 'alumnos', 'page' => 'Pág.',
                'num' => 'Nº', 'student' => 'Alumno', 'activity' => 'Actividad', 'date' => 'Fecha',
                'place' => 'Lugar', 'responsables' => 'Responsables', 'present' => 'Presentes',
                'absent' => 'Ausentes', 'generalobs' => 'Incidencias / observaciones generales',
                'customcolumn' => 'Columna',
            ],
            'en' => [
                'subtitle' => 'Activity control sheet', 'students' => 'students', 'page' => 'Page',
                'num' => 'No.', 'student' => 'Student', 'activity' => 'Activity', 'date' => 'Date',
                'place' => 'Place', 'responsables' => 'Staff in charge', 'present' => 'Present',
                'absent' => 'Absent', 'generalobs' => 'Incidents / general notes',
                'customcolumn' => 'Column',
            ],
        ];
        return $map[$language][$key] ?? ($map['ca'][$key] ?? $key);
    }
}
