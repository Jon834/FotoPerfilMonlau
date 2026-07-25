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

namespace local_profilephoto\local\search;

use local_profilephoto\local\access\scope;

defined('MOODLE_INTERNAL') || die();

/**
 * Fast, paginated, priority-ordered user search.
 *
 * Designed for 10k+ user installs (encargo section 11): never loads the
 * full user table into PHP/JS, always filters in SQL on indexed columns,
 * and stops as soon as the requested limit of results has been collected,
 * querying the highest-priority tier first (exact idnumber/email/username)
 * before falling back to partial name matches.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_search {

    /** @var int Hard ceiling on results returned in a single call. */
    const MAX_LIMIT = 50;

    /**
     * Search for candidate users, respecting the operator's scope.
     *
     * @param string $query raw search text as typed by the operator.
     * @param int $operatorid user performing the search.
     * @param int $limit maximum rows to return (capped at self::MAX_LIMIT).
     * @return array list of stdClass user rows, priority-ordered, deduplicated.
     */
    public static function search(string $query, int $operatorid, int $limit = 20): array {
        global $DB;

        $query = trim($query);
        if (\core_text::strlen($query) < 2) {
            return [];
        }

        $limit = max(1, min($limit, self::MAX_LIMIT));

        $courseids = scope::get_allowed_courseids($operatorid);
        if ($courseids !== null && empty($courseids)) {
            // Operator has capture capability nowhere: no results, no query needed.
            return [];
        }

        [$scopesql, $scopeparams] = self::build_scope_sql($courseids);
        $visibilitysql = self::build_visibility_sql($operatorid);

        $fields = 'u.id, u.firstname, u.lastname, u.email, u.username, u.idnumber, ' .
            'u.picture, u.imagealt, u.suspended, u.auth';
        $basefrom = '{user} u WHERE u.deleted = 0' . $visibilitysql . $scopesql;

        $results = [];
        $seenids = [];

        // Tier 1-4: exact match on idnumber, email, username (priority order).
        $exactfields = ['idnumber', 'email', 'username'];
        foreach ($exactfields as $field) {
            if (count($results) >= $limit) {
                break;
            }
            $equalsql = $DB->sql_equal('u.' . $field, ':exactvalue', false, true);
            $params = array_merge($scopeparams, ['exactvalue' => $query]);
            $sql = "SELECT {$fields} FROM {$basefrom} AND {$equalsql} AND u.{$field} <> ''
                    ORDER BY u.lastname ASC, u.firstname ASC";
            $remaining = $limit - count($results);
            self::append_results($results, $seenids, $DB->get_records_sql($sql, $params, 0, $remaining), $limit);
        }

        // Tier 5-6: partial match on full name, email, username, idnumber.
        if (count($results) < $limit) {
            $like = '%' . $DB->sql_like_escape($query) . '%';
            $namelike = $DB->sql_like('u.firstname', ':like1', false, false) . ' OR ' .
                $DB->sql_like('u.lastname', ':like2', false, false) . ' OR ' .
                $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':like3', false, false) . ' OR ' .
                $DB->sql_like('u.email', ':like4', false, false) . ' OR ' .
                $DB->sql_like('u.username', ':like5', false, false) . ' OR ' .
                $DB->sql_like('u.idnumber', ':like6', false, false);
            $params = array_merge($scopeparams, [
                'like1' => $like, 'like2' => $like, 'like3' => $like,
                'like4' => $like, 'like5' => $like, 'like6' => $like,
            ]);
            $sql = "SELECT {$fields} FROM {$basefrom} AND ({$namelike})
                    ORDER BY u.lastname ASC, u.firstname ASC";
            $remaining = $limit - count($results);
            self::append_results($results, $seenids, $DB->get_records_sql($sql, $params, 0, $remaining), $limit);
        }

        return array_values($results);
    }

    /**
     * Merge a resultset into the accumulator, skipping duplicates already found.
     *
     * @param array $results accumulator, passed by reference.
     * @param array $seenids map of userid => true, passed by reference.
     * @param iterable $rows rows to merge in.
     * @param int $limit maximum size of $results.
     */
    private static function append_results(array &$results, array &$seenids, iterable $rows, int $limit): void {
        foreach ($rows as $row) {
            if (count($results) >= $limit) {
                return;
            }
            if (isset($seenids[$row->id])) {
                continue;
            }
            $seenids[$row->id] = true;
            $results[] = $row;
        }
    }

    /**
     * Build the SQL/params restricting results to enrolled-in-allowed-courses.
     *
     * @param array|null $courseids null = unrestricted scope.
     * @return array [string $sql, array $params]
     */
    private static function build_scope_sql(?array $courseids): array {
        global $DB;

        if ($courseids === null) {
            return ['', []];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'crs');
        $sql = " AND EXISTS (
                    SELECT 1
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.userid = u.id
                       AND ue.status = 0
                       AND e.status = 0
                       AND e.courseid {$insql}
                )";
        return [$sql, $params];
    }

    /**
     * Build the SQL restricting suspended users, unless the operator can see them.
     *
     * @param int $operatorid
     * @return string
     */
    private static function build_visibility_sql(int $operatorid): string {
        if (scope::can_view_suspended($operatorid)) {
            return '';
        }
        return ' AND u.suspended = 0';
    }
}
