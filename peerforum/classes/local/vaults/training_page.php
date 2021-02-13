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
 * Training page vault classes.
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
 * Training page vault class.
 *
 * This should be the only place that accessed the database.
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
     * @var training_exercise $exercisevault Exercises
     */
    private $exercisevault;

    /**
     * @var training_criteria $criteriavault Criterias
     */
    private $criteriavault;

    /**
     * @var training_right_grade $rightgradevault Correct grades
     */
    private $rightgradevault;

    /**
     * @var training_feedback $feedbackvault Feedback
     */
    private $feedbackvault;

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
        $this->exercisevault = new training_exercise($db, $entityfactory, $legacyfactory);
        $this->criteriavault = new training_criteria($db, $entityfactory, $legacyfactory);
        $this->rightgradevault = new training_right_grade($db, $entityfactory, $legacyfactory);
        $this->feedbackvault = new training_feedback($db, $entityfactory, $legacyfactory);
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
     * Get the list of all records for a peerforum.
     *
     * @param int $id
     * @return array
     */
    public function get_from_peerforum_id(int $id): array {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($id);
        $wheresql = $alias . '.peerforum ' . $insql;
        $sortsql = $alias . '.id DESC';
        $sql = $this->generate_get_records_sql($wheresql, $sortsql);
        return $this->get_db()->get_records_sql($sql, $params);
    }

    /**
     * Convert the DB records into training pages with more info.
     *
     * @param array $results The DB records
     * @return array
     */
    protected function from_db_records(array $results) {
        return array_map(function(array $result) {
            ['record' => $page] = $result;
            $exercises = $this->exercisevault->get_from_page_id($page->id);
            $criteria = $this->criteriavault->get_from_page_id($page->id);
            $correctgrades = $this->rightgradevault->get_from_page_id($page->id);
            $feedback = $this->feedbackvault->get_from_page_id($page->id);

            foreach ($feedback as $f) {
                $f->n = $exercises[$f->exid]->n;
            }
            foreach ($correctgrades as $c) {
                $c->n = $exercises[$c->exid]->n;
            }

            $page->feedback = $this->turn_inside_out($feedback, array('grade', 'criteriaid', 'n'));
            $page->correctgrades = $this->turn_inside_out($correctgrades, array('criteriaid', 'n'));
            $page->criteria = $this->turn_inside_out($criteria, array('n'));
            $page->exercise = $this->turn_inside_out($exercises, array('n'));
            return $page;
        }, $results);
    }

    /**
     * Turns a list of objects with the same keys, in an object with nested lists for each key.
     *
     * @author Daniel Fernandes <3
     *
     * @param array $inners the list of object with same keys
     * @param array $order the nesting order
     * @return array
     */
    public static function turn_inside_out(array $inners, array $order): array {
        $parent = array();
        foreach ($inners as $obj) {
            foreach ($obj as $key => $v) {
                $parent[$key] = self::cena($obj, $order, $v, 0, $parent[$key] ?? array());
            }
        }
        return $parent;
    }

    /**
     * Helper recursive function for turn_inside_out.
     *
     * @param object $obj object from the list
     * @param array $order the nesting order
     * @param mixed $v value at the list
     * @param int $i the depth at the nesting
     * @param array $existent the build nested array until now at a level
     * @return mixed
     */
    protected static function cena(object $obj, array $order, $v, int $i, array $existent) {
        if ($i == count($order)) {
            return $v;
        }
        $key = $obj->{$order[$i]};
        $existent[$key] = self::cena($obj, $order, $v, $i + 1, $existent[$key] ?? array());
        return $existent;
    }

    /**
     * Turns an object with nested lists for each key in a list of objects with the same keys.
     * Exactly the reverse as turn_inside_out, i hope.
     *
     * @param array $outers the object with the nested values
     * @param array $order the nesting order given before
     * @return array
     */
    public static function turn_outside_in(?array $outers, array $order) : array {
        if (empty($outers)) {
            return array();
        }
        $parent = array();
        $paths = array();
        foreach ($outers as $key => $seq) {
            $paths = self::turn_outside_in_helper($seq, count($order));
            break;
        }

        foreach ($paths as $path) {
            $path = array_reverse($path);
            $newobj = new stdClass();
            foreach ($outers as $key => $seq) {
                foreach ($path as $i => $p) {
                    $newobj->{$order[$i]} = $p;
                    $seq = $seq[$p];
                }
                $newobj->$key = $seq;
            }
            $parent[] = $newobj;
        }
        return $parent;
    }

    /**
     * Helper recursive function for turn_outside_in.
     *
     * @param $seq nested array
     * @param int $count the size of the nesting order
     * @return array|array[]
     */
    protected static function turn_outside_in_helper($seq, int $count) : array {
        if (!$count) {
            return array(array());
        }
        $ret = array();
        foreach ($seq as $k => $v) {
            $res = self::turn_outside_in_helper($v, $count - 1);
            foreach ($res as $r) {
                $r[] = $k;
                $ret[] = $r;
            }
        }
        return $ret;
    }

}

class training_criteria extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_criteria';

    /**
     * Get the table alias.
     *
     * @return string
     */
    protected function get_table_alias(): string {
        return 'c';
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
     * Get the list of entities for the given ids.
     *
     * @param int $id Identifier
     * @return array
     */
    public function get_from_page_id(int $id) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($id);
        $wheresql = $alias . '.pageid ' . $insql;
        $orderby = $alias . '.n ASC';
        $sql = $this->generate_get_records_sql($wheresql, $orderby);
        return $this->get_db()->get_records_sql($sql, $params);
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

class training_exercise extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_exercise';

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
     * Get the list of entities for the given ids.
     *
     * @param int $id Identifier
     * @return array
     */
    public function get_from_page_id(int $id) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($id);
        $wheresql = $alias . '.pageid ' . $insql;
        $orderby = $alias . '.n ASC';
        $sql = $this->generate_get_records_sql($wheresql, $orderby);
        $records = $this->get_db()->get_records_sql($sql, $params);

        // Kinda ugly but sure.
        return array_map(function($exercise) {
            $newdesc = new stdClass();

            $newdesc->descriptionformat = $exercise->descriptionformat;
            $newdesc->descriptiontrust = $exercise->descriptiontrust;
            $newdesc->description = $exercise->description;

            $exercise->description = $newdesc;
            unset($exercise->descriptionformat, $exercise->descriptiontrust);

            return $exercise;
        }, $records);
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

class training_feedback extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_feedback';

    /**
     * Get the table alias.
     *
     * @return string
     */
    protected function get_table_alias(): string {
        return 'f';
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
     * Get the list of entities for the given ids.
     *
     * @param int $id Identifier
     * @return array
     */
    public function get_from_page_id(int $id) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($id);
        $wheresql = $alias . '.pageid ' . $insql;
        $orderby = $alias . '.exid ASC';
        $sql = $this->generate_get_records_sql($wheresql, $orderby);
        return $this->get_db()->get_records_sql($sql, $params);
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

class training_right_grade extends db_table_vault {
    /** The table for this vault */
    private const TABLE = 'peerforum_training_rgh_grade';

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
     * Get the list of entities for the given ids.
     *
     * @param int $id Identifier
     * @return array
     */
    public function get_from_page_id(int $id) {
        $alias = $this->get_table_alias();
        list($insql, $params) = $this->get_db()->get_in_or_equal($id);
        $wheresql = $alias . '.pageid ' . $insql;
        $orderby = $alias . '.exid ASC';
        $sql = $this->generate_get_records_sql($wheresql, $orderby);
        return $this->get_db()->get_records_sql($sql, $params);
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


