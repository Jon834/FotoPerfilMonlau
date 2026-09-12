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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Build the "Control d'activitat" roster as an .xlsx spreadsheet: same
 * columns, order and branding as {@see activity_pdf_builder}'s PDF, but
 * fillable digitally instead of printed - no page orientation, no photos.
 *
 * Column model / label / formatting helpers are reused from
 * {@see activity_pdf_builder} rather than duplicated; only the sheet layout
 * (rows, cells, styling) is specific to this class, using PhpSpreadsheet,
 * which Moodle core already bundles.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_xlsx_builder {

    /** @var string Header/table-header fill colour, matches the PDF's header grey. */
    private const HEADER_FILL = 'E8ECF2';

    /**
     * Build the spreadsheet.
     *
     * @param stdClass[] $members cohort members, each with id, firstname, lastname, email.
     * @param string $cohortname
     * @param array $activity name, date (Y-m-d or ''), place, responsables.
     * @param array $columns ordered list of {key, label, type: checkbox|text|value}.
     * @param array $options language, stage, showgeneralobs, order, generatedby.
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $members, string $cohortname, array $activity, array $columns,
            array $options = []): array {
        global $CFG;

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $extracolumns = array_values(array_filter($columns, static function(array $column): bool {
            return ($column['key'] ?? '') !== '';
        }));

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = branding::normalise_stage($options['stage'] ?? 'fp');
        $order = in_array($options['order'] ?? 'lastname', ['lastname', 'firstname', 'cohort'], true)
            ? $options['order'] : 'lastname';
        $showgeneralobs = (bool) ($options['showgeneralobs'] ?? true);

        $users = [];
        foreach ($members as $member) {
            $user = new stdClass();
            $user->firstname = (string) ($member->firstname ?? '');
            $user->lastname = (string) ($member->lastname ?? '');
            $user->email = (string) ($member->email ?? '');
            $users[] = $user;
        }
        $users = activity_pdf_builder::sort_users($users, $order);
        $count = count($users);

        $activitydate = activity_pdf_builder::resolve_activity_date($activity['date'] ?? '');
        $filenamedate = $activitydate !== null ? $activitydate->format('Ymd') : userdate(time(), '%Y%m%d');
        $filename = activity_pdf_builder::sanitize_filename($cohortname) . '_control-activitat_' . $filenamedate . '.xlsx';

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $path = $tempdir . '/' . $filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::sheet_title($cohortname));

        $lastcol = Coordinate::stringFromColumnIndex(2 + count($extracolumns));
        $brandhex = self::rgb_hex(branding::palette($stage));

        self::render_brand_rows($sheet, $lastcol, $brandhex, $cohortname,
            activity_pdf_builder::translate_word('subtitle', $language) . ' · ' . $count . ' '
                . activity_pdf_builder::translate_word('students', $language));

        self::render_activity_rows($sheet, $activity, $activitydate, $count, $language);

        $headerrow = 7;
        self::render_table_header($sheet, $headerrow, $lastcol, $extracolumns, $language);

        $lastrow = self::render_student_rows($sheet, $headerrow + 1, $lastcol, $users, $extracolumns);

        if ($showgeneralobs) {
            $lastrow = self::render_general_obs($sheet, $lastrow + 1, $lastcol, $language);
        }
        unset($lastrow);

        $sheet->freezePane('A' . ($headerrow + 1));

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $count,
        ];
    }

    /**
     * Rows 1-2: the brand-coloured cohort name + subtitle/count bar.
     *
     * @param Worksheet $sheet
     * @param string $lastcol
     * @param string $brandhex RRGGBB, no leading '#'.
     * @param string $cohortname
     * @param string $subtitle
     */
    private static function render_brand_rows(Worksheet $sheet, string $lastcol, string $brandhex,
            string $cohortname, string $subtitle): void {
        $sheet->mergeCells("A1:{$lastcol}1");
        $sheet->setCellValue('A1', $cohortname);
        $sheet->getStyle("A1:{$lastcol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brandhex]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->mergeCells("A2:{$lastcol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle("A2:{$lastcol}2")->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brandhex]],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(4);
    }

    /**
     * Rows 4-6: Activitat/Data/Lloc, then Responsables/Alumnes, then a spacer.
     *
     * @param Worksheet $sheet
     * @param array $activity name, place, responsables (raw strings).
     * @param \DateTime|null $activitydate
     * @param int $count
     * @param string $language
     */
    private static function render_activity_rows(Worksheet $sheet, array $activity, ?\DateTime $activitydate,
            int $count, string $language): void {
        $name = trim((string) ($activity['name'] ?? ''));
        $place = trim((string) ($activity['place'] ?? ''));
        $datestr = $activitydate !== null ? $activitydate->format('d/m/Y') : '';
        $responsables = activity_pdf_builder::format_responsables((string) ($activity['responsables'] ?? ''));

        $sheet->setCellValue('A4', activity_pdf_builder::translate_word('activity', $language) . ':');
        $sheet->setCellValue('B4', $name);
        $sheet->setCellValue('C4', activity_pdf_builder::translate_word('date', $language) . ':');
        $sheet->setCellValue('D4', $datestr);
        $sheet->setCellValue('E4', activity_pdf_builder::translate_word('place', $language) . ':');
        $sheet->setCellValue('F4', $place);

        $sheet->setCellValue('A5', activity_pdf_builder::translate_word('responsables', $language) . ':');
        $sheet->mergeCells('B5:C5');
        $sheet->setCellValue('B5', $responsables);
        $sheet->setCellValue('D5', ucfirst(activity_pdf_builder::translate_word('students', $language)) . ':');
        $sheet->setCellValue('E5', $count);

        foreach (['A4', 'C4', 'E4', 'A5', 'D5'] as $labelcell) {
            $sheet->getStyle($labelcell)->getFont()->setBold(true);
        }

        $sheet->getRowDimension(6)->setRowHeight(4);
    }

    /**
     * The table header row (Núm., Alumne, then every extra column).
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastcol
     * @param array $extracolumns ordered {key, label, type} list.
     * @param string $language
     */
    private static function render_table_header(Worksheet $sheet, int $row, string $lastcol,
            array $extracolumns, string $language): void {
        $sheet->setCellValue('A' . $row, activity_pdf_builder::translate_word('num', $language));
        $sheet->getColumnDimension('A')->setWidth(6);

        $sheet->setCellValue('B' . $row, activity_pdf_builder::translate_word('student', $language));
        $sheet->getColumnDimension('B')->setWidth(28);

        $colindex = 3;
        foreach ($extracolumns as $column) {
            $letter = Coordinate::stringFromColumnIndex($colindex);
            $key = (string) $column['key'];
            $type = (string) ($column['type'] ?? 'checkbox');
            $sheet->setCellValue($letter . $row, activity_pdf_builder::column_label($column, $language));
            $sheet->getColumnDimension($letter)->setWidth(self::column_width($key, $type));
            $colindex++;
        }

        $sheet->getStyle("A{$row}:{$lastcol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_FILL]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * One bordered row per student: Núm., name, then a cell per extra column -
     * blank (fillable) for checkbox/text columns, the real value for "value" columns.
     *
     * @param Worksheet $sheet
     * @param int $firstrow
     * @param string $lastcol
     * @param stdClass[] $users
     * @param array $extracolumns
     * @return int the last row written (equals $firstrow - 1 when there are no users).
     */
    private static function render_student_rows(Worksheet $sheet, int $firstrow, string $lastcol,
            array $users, array $extracolumns): int {
        $row = $firstrow;
        foreach ($users as $index => $user) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, activity_pdf_builder::format_student_name($user));

            $colindex = 3;
            foreach ($extracolumns as $column) {
                $type = (string) ($column['type'] ?? 'checkbox');
                if ($type === 'value') {
                    $letter = Coordinate::stringFromColumnIndex($colindex);
                    $sheet->setCellValue($letter . $row,
                        activity_pdf_builder::user_field_value($user, (string) $column['key']));
                }
                // checkbox/text columns: left blank, border-only, to fill in by hand.
                $colindex++;
            }
            $row++;
        }

        if ($row > $firstrow) {
            $sheet->getStyle("A{$firstrow}:{$lastcol}" . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        return $row - 1;
    }

    /**
     * The "Incidències / observacions generals" label + one tall blank bordered row.
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastcol
     * @param string $language
     * @return int the last row written.
     */
    private static function render_general_obs(Worksheet $sheet, int $row, string $lastcol, string $language): int {
        $sheet->mergeCells("A{$row}:{$lastcol}{$row}");
        $sheet->setCellValue('A' . $row, activity_pdf_builder::translate_word('generalobs', $language));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->mergeCells("A{$row}:{$lastcol}{$row}");
        $sheet->getStyle("A{$row}:{$lastcol}{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);

        return $row;
    }

    /**
     * Column width (Excel character units) for a given key/type.
     *
     * @param string $key
     * @param string $type checkbox|text|value
     * @return float
     */
    private static function column_width(string $key, string $type): float {
        if ($key === 'observacions') {
            return 30.0;
        }
        if ($type === 'value') {
            return 26.0;
        }
        return $type === 'text' ? 14.0 : 10.0;
    }

    /**
     * Convert an {r,g,b} triple into an "RRGGBB" hex string for PhpSpreadsheet fills.
     *
     * @param array{r:int,g:int,b:int} $rgb
     * @return string
     */
    private static function rgb_hex(array $rgb): string {
        return sprintf('%02X%02X%02X', $rgb['r'], $rgb['g'], $rgb['b']);
    }

    /**
     * Sanitise a cohort name into a valid Excel sheet title: no \ / ? * [ ] : and
     * capped at Excel's 31-character limit.
     *
     * @param string $name
     * @return string
     */
    private static function sheet_title(string $name): string {
        $name = trim((string) preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name));
        $name = mb_substr($name, 0, 31);
        return $name !== '' ? $name : 'Control activitat';
    }
}
