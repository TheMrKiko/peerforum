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
 * Post vault class.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\vaults;

defined('MOODLE_INTERNAL') || die();

use mod_peerforum\local\factories\entity as entity_factory;
use moodle_database;
use stdClass;

/**
 * Post vault class.
 *
 * This should be the only place that accessed the database.
 *
 * This class should not return any objects other than post_entity objects. The class
 * may contain some utility count methods which return integers.
 *
 * This uses the repository pattern. See:
 * https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class training_page extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_page';

    /**
     * @var training_example $examplevault Examples
     */
    private $examplevault;

    /**
     * Constructor.
     *
     * @param moodle_database $db A moodle database
     * @param entity_factory $entityfactory Entity factory
     * @param object $legacyfactory Legacy factory
     */
    public function __construct(
            moodle_database $db,
            entity_factory $entityfactory,
            $legacyfactory
    ) {
        $this->examplevault = new training_example($db, $entityfactory, $legacyfactory);
        parent::__construct($db, $entityfactory, $legacyfactory);
    }

    /**
     * Get the table alias.
     *
     * @return string
     */
    protected function get_table_alias(): string {
        return 't';
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
     * Convert the DB records into post entities.
     *
     * @param array $results The DB records
     * @return post_entity[]
     */
    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            ['record' => $record] = $result;
            return $entityfactory->get_post_from_stdclass($record);
        }, $results);
    }

    /**
     * Get the list of entities for the given ids.
     *
     * @param int[] $ids Identifiers
     * @return array
     */
    public function get_from_ids(array $ids) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($ids);
        $wheresql = $alias . '.id ' . $insql;
        $sql = $this->generate_get_records_sql($wheresql);
        $records = $this->get_db()->get_records_sql($sql, $params);

        $examples = $this->examplevault->get_from_page_ids($ids);

        $records = $this->create_structure($records, $examples, '_eg');

        return $records;
    }

    protected function create_structure(array $pages, array $inner, string $sufix) {
        return array_map(function($page) use ($inner, $sufix) {
            foreach ($inner as $k => $i) {
                foreach ($i as $key => $v) {
                    $page->{$key . $sufix}[] = $v;
                }
            }
            return $page;
        }, $pages);
    }

}

class training_example extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_example';

    /**
     * Get the table alias.
     *
     * @return string
     */
    protected function get_table_alias(): string {
        return 'e';
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
     * Convert the DB records into post entities.
     *
     * @param array $results The DB records
     * @return post_entity[]
     */
    protected function from_db_records(array $results) {
        $entityfactory = $this->get_entity_factory();

        return array_map(function(array $result) use ($entityfactory) {
            ['record' => $record] = $result;
            return $entityfactory->get_post_from_stdclass($record);
        }, $results);
    }

    /**
     * Get the list of entities for the given ids.
     *
     * @param int[] $ids Identifiers
     * @return array
     */
    public function get_from_page_ids(array $ids) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($ids);
        $wheresql = $alias . '.pageid ' . $insql;
        $orderby = $alias . '.n ASC';
        $sql = $this->generate_get_records_sql($wheresql, $orderby);
        $records = $this->get_db()->get_records_sql($sql, $params);

        return array_map(function($example) {
            $newdesc = new stdClass();

            $newdesc->descriptionformat = $example->descriptionformat;
            $newdesc->descriptiontrust = $example->descriptiontrust;
            $newdesc->description = $example->description;

            $example->description = $newdesc;
            unset($example->descriptionformat, $example->descriptiontrust);

            return $example;
        }, $records);
    }
}