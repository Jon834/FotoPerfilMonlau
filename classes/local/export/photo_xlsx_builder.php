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

use core\user as core_user_class;
use moodle_exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

defined('MOODLE_INTERNAL') || die();

/**
 * The Excel (.xlsx) counterpart of {@see pdf_builder}'s orla/grid6/directory/
 * signatures layouts: one row per student with an embedded photo (or initials
 * avatar), same brand header and title as the PDF. Reuses {@see pdf_builder}'s
 * user-fetch/sort and formatting/translation helpers; only the sheet layout
 * itself is specific to this class.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class photo_xlsx_builder {

    /** @var string Header/table-header fill colour, matches the activity Excel's header grey. */
    private const HEADER_FILL = 'E8ECF2';

    /**
     * Build the spreadsheet.
     *
     * @param int[] $userids
     * @param string $title course/cohort title shown at the top of the sheet.
     * @param string $layout orla|grid6|directory|signatures (roster kept as alias for orla).
     * @param array $options language, stage, heading, generatedby.
     * @return array{path: string, filename: string, count: int}
     */
    public static function build(array $userids, string $title, string $layout = 'orla', array $options = []): array {
        self::require_phpspreadsheet();

        if ($layout === 'roster') {
            $layout = 'orla';
        }
        if (!in_array($layout, ['orla', 'grid6', 'directory', 'signatures'], true)) {
            $layout = 'orla';
        }

        $language = in_array($options['language'] ?? 'ca', ['ca', 'es', 'en'], true) ? $options['language'] : 'ca';
        $stage = branding::normalise_stage($options['stage'] ?? 'fp');
        $heading = trim((string) ($options['heading'] ?? ''));

        $users = [];
        foreach ($userids as $userid) {
            $user = core_user_class::get_user((int) $userid, 'id, firstname, lastname, picture, email', IGNORE_MISSING);
            if (!$user) {
                continue;
            }
            $user->photo = ((int) $user->picture) > 0 ? xlsx_avatar::get_icon_content((int) $user->id) : null;
            $users[] = $user;
        }

        usort($users, static function($a, $b): int {
            $lastname = strcmp((string) ($a->lastname ?? ''), (string) ($b->lastname ?? ''));
            return $lastname !== 0 ? $lastname : strcmp((string) ($a->firstname ?? ''), (string) ($b->firstname ?? ''));
        });

        $count = count($users);

        $layoutname = match ($layout) {
            'grid6' => 'orla6',
            'directory' => 'directorio',
            'signatures' => 'firmas',
            default => 'orla',
        };
        $filename = pdf_builder::sanitize_filename($title) . '_' . $layoutname . '_' . userdate(time(), '%Y%m%d_%H%M') . '.xlsx';

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $path = $tempdir . '/' . $filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(xlsx_common::sheet_title($title, 'Export'));

        // A=Núm., B=Foto, C=Alumne, plus one extra column for directory (Correu) / signatures (Signatura).
        $hasextracol = in_array($layout, ['directory', 'signatures'], true);
        $lastcol = $hasextracol ? 'D' : 'C';
        $brandhex = xlsx_common::rgb_hex(branding::palette($stage));

        self::render_brand_rows($sheet, $lastcol, $brandhex, $stage, $title,
            pdf_builder::translate_title($layout, $language) . ' · ' . $count . ' '
                . pdf_builder::translate_word('students', $language), $heading);

        $headerrow = 6;
        self::render_table_header($sheet, $headerrow, $lastcol, $layout, $language);
        self::render_student_rows($sheet, $headerrow + 1, $lastcol, $users, $layout);

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
     * Load PhpSpreadsheet's autoloader - see {@see activity_xlsx_builder::require_phpspreadsheet()}
     * for the full rationale (kept as a separate copy: both classes need to be usable
     * independently of one another).
     */
    private static function require_phpspreadsheet(): void {
        global $CFG;

        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return;
        }

        $candidates = [
            $CFG->libdir . '/phpspreadsheet/vendor/autoload.php',
            $CFG->dirroot . '/vendor/autoload.php',
            dirname($CFG->dirroot) . '/vendor/autoload.php',
            dirname($CFG->dirroot) . '/lib/phpspreadsheet/vendor/autoload.php',
        ];
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                require_once($path);
                if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                    return;
                }
            }
        }

        throw new moodle_exception('error_phpspreadsheetmissing', 'local_profilephoto');
    }

    /**
     * Rows 1-5: brand-coloured title + subtitle/count bar, an optional heading line, spacers.
     *
     * @param Worksheet $sheet
     * @param string $lastcol
     * @param string $brandhex RRGGBB, no leading '#'.
     * @param string $stage
     * @param string $title
     * @param string $subtitle
     * @param string $heading optional free text, left blank when empty.
     */
    private static function render_brand_rows(Worksheet $sheet, string $lastcol, string $brandhex, string $stage,
            string $title, string $subtitle, string $heading): void {
        // Column A is left unmerged (brand-filled, no text) so the stage logo has its own
        // slot at the left of the header, matching the PDF's logo-then-title layout.
        $sheet->getStyle("A1:{$lastcol}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brandhex]],
        ]);
        $sheet->mergeCells("B1:{$lastcol}1");
        $sheet->setCellValue('B1', $title);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $sheet->getStyle("A2:{$lastcol}2")->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $brandhex]],
        ]);
        $sheet->mergeCells("B2:{$lastcol}2");
        $sheet->setCellValue('B2', $subtitle);

        xlsx_avatar::embed_logo($sheet, 'A1', $stage);

        $sheet->getRowDimension(3)->setRowHeight(4);

        if ($heading !== '') {
            $sheet->mergeCells("A4:{$lastcol}4");
            $sheet->setCellValue('A4', $heading);
        }

        $sheet->getRowDimension(5)->setRowHeight(4);
    }

    /**
     * The table header row: Núm., Foto, Alumne, plus Correu (directory) or Signatura (signatures).
     *
     * @param Worksheet $sheet
     * @param int $row
     * @param string $lastcol
     * @param string $layout
     * @param string $language
     */
    private static function render_table_header(Worksheet $sheet, int $row, string $lastcol,
            string $layout, string $language): void {
        $sheet->setCellValue('A' . $row, pdf_builder::translate_word('number', $language));
        // A bit wider than a bare "Núm." column needs, so the header rows' logo (column A,
        // unmerged - see render_brand_rows()) has room to sit in.
        $sheet->getColumnDimension('A')->setWidth(9);

        $sheet->setCellValue('B' . $row, pdf_builder::translate_word('photo', $language));
        $sheet->getColumnDimension('B')->setWidth(6);

        $sheet->setCellValue('C' . $row, pdf_builder::translate_word('student', $language));
        $sheet->getColumnDimension('C')->setWidth(28);

        if ($layout === 'directory') {
            $sheet->setCellValue('D' . $row, pdf_builder::translate_word('email', $language));
            $sheet->getColumnDimension('D')->setWidth(28);
        } else if ($layout === 'signatures') {
            $sheet->setCellValue('D' . $row, pdf_builder::translate_word('signature', $language));
            $sheet->getColumnDimension('D')->setWidth(24);
        }

        $sheet->getStyle("A{$row}:{$lastcol}{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_FILL]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    /**
     * One bordered row per student: Núm., embedded avatar, name, and (directory/signatures
     * only) a fourth column - the student's email, or a blank cell for signatures.
     *
     * @param Worksheet $sheet
     * @param int $firstrow
     * @param string $lastcol
     * @param object[] $users
     * @param string $layout
     */
    private static function render_student_rows(Worksheet $sheet, int $firstrow, string $lastcol,
            array $users, string $layout): void {
        $row = $firstrow;
        foreach ($users as $index => $user) {
            $sheet->setCellValue('A' . $row, $index + 1);
            xlsx_avatar::embed($sheet, 'B' . $row, $user->photo ?? null,
                (string) ($user->firstname ?? ''), (string) ($user->lastname ?? ''));
            $sheet->setCellValue('C' . $row, pdf_builder::format_student_name($user));
            $sheet->getRowDimension($row)->setRowHeight(34);

            if ($layout === 'directory') {
                $sheet->setCellValue('D' . $row, (string) ($user->email ?? ''));
            }
            // signatures: column D is left blank (border-only), same convention as the
            // Control d'activitat Excel's blank checkbox/text cells.

            $row++;
        }

        if ($row > $firstrow) {
            $sheet->getStyle("A{$firstrow}:{$lastcol}" . ($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }
    }
}
