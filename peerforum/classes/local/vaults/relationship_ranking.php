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

/**
 * Relationship ranking vault class.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\vaults;

defined('MOODLE_INTERNAL') || die();

/**
 * Relationship ranking vault class.
 *
 * This should be the only place that accessed the database.
 *
 * This uses the repository pattern. See:
 * https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class relationship_ranking extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_relationship_rank';

    /**
     * Get the table alias.
     *
     * @return string
     */
    protected function get_table_alias(): string {
        return 'r';
    }

    /**
     * Build the SQL to be used in get_records_sql.
     *
     * @param string|null $wheresql Where conditions for the SQL
     * @param string|null $sortsql Order by conditions for the SQL
     * @param int|null $userid The user ID
     * @return string
     */
    protected function generate_get_records_sql(string $wheresql = null, string $sortsql = null, ?int $userid = null): string {
        $table = self::TABLE;
        $alias = $this->get_table_alias();
        $fields = $alias . '.*';
        $tables = "{{$table}} {$alias}";

        $selectsql = "SELECT {$fields} FROM {$tables}";
        $selectsql .= $wheresql ? ' WHERE ' . $wheresql : '';
        $selectsql .= $sortsql ? ' ORDER BY ' . $sortsql : '';

        return $selectsql;
    }

    /**
     * Get the list of all records for a user and course.
     *
     * @param int $id
     * @param int $courseid
     * @param bool $onlynull
     * @return array
     */
    public function get_from_user_id(int $id, int $courseid, bool $onlynull = true): array {
        $alias = $this->get_table_alias();
        list($insql1, $params1) = $this->get_db()->get_in_or_equal($id, SQL_PARAMS_NAMED);
        list($insql2, $params2) = $this->get_db()->get_in_or_equal($courseid, SQL_PARAMS_NAMED);
        $wheresql = $alias . '.userid ' . $insql1 . ' AND ' . $alias . '.course ' . $insql2;
        $wheresql .= $onlynull ? ' AND ' . $alias . '.ranking IS NULL' : '';
        $sql = $this->generate_get_records_sql($wheresql);
        $records = $this->get_db()->get_records_sql($sql, $params1 + $params2);

        return training_page::turn_inside_out($records, array('otheruserid'));
    }

    /**
     * Count the list of all records for a user and course.
     *
     * @param int $id
     * @param int $courseid
     */
    public function count_pending_from_user_id(int $id, int $courseid) {
        $table = self::TABLE;
        $alias = $this->get_table_alias();
        list($insql1, $params1) = $this->get_db()->get_in_or_equal($id, SQL_PARAMS_NAMED);
        list($insql2, $params2) = $this->get_db()->get_in_or_equal($courseid, SQL_PARAMS_NAMED);
        $fields = '0, COUNT(' . $alias . '.id) AS rankings';
        $tables = "{{$table}} {$alias}";

        $sql = "SELECT {$fields} FROM {$tables}";
        $sql .= ' WHERE ' . $alias . '.userid ' . $insql1 . ' AND ' . $alias . '.course ' . $insql2;
        $sql .= ' AND ' . $alias . '.ranking IS NULL';
        return $this->get_db()->get_records_sql($sql, $params1 + $params2)[0]->rankings;
    }

    /**
     * Just sits here.
     *
     * @param array $results The DB records
     */
    protected function from_db_records(array $results) {
        // Empty.
    }
}
