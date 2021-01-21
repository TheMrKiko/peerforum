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
 * Privacy Subsystem implementation for core_peergrades.
 *
 * @package    core_peergrade
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_peergrade\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/peergrade/lib.php');

/**
 * Privacy Subsystem implementation for core_peergrades.
 *
 * @copyright  2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // The peergrades subsystem contains data.
        \core_privacy\local\metadata\provider,

        // The peergrades subsystem is only ever used to store data for other components.
        // It does not store any data of its own and does not need to implement the \core_privacy\local\request\subsystem\provider
        // as a result.

        // The peergrades subsystem provides a data service to other components.
        \core_privacy\local\request\subsystem\plugin_provider,
        \core_privacy\local\request\shared_userlist_provider {

    /**
     * Returns metadata about the peergrades subsystem.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through the subsystem.
     */
    public static function get_metadata(collection $collection): collection {
        // The table 'peergrade' cotains data that a user has entered.
        // It stores the user-entered peergrade alongside a mapping to describe what was mapped.
        $collection->add_database_table('peergrade', [
                'peergrade' => 'privacy:metadata:peergrade:peergrade',
                'userid' => 'privacy:metadata:peergrade:userid',
                'timecreated' => 'privacy:metadata:peergrade:timecreated',
                'timemodified' => 'privacy:metadata:peergrade:timemodified',
        ], 'privacy:metadata:peergrade');

        return $collection;
    }

    /**
     * Export all peergrades which match the specified component, areaid, and itemid.
     *
     * If requesting peergrades for a users own content, and you wish to include all peergrades of that content, specify
     * $onlyuser as false.
     *
     * When requesting peergrades for another users content, you should only export the peergrades that the specified user
     * made themselves.
     *
     * @param int $userid The user whose information is to be exported
     * @param \context $context The context being stored.
     * @param array $subcontext The subcontext within the context to export this information
     * @param string $component The component to fetch data from
     * @param string $peergradearea The peergradearea that the data was stored in within the component
     * @param int $itemid The itemid within that peergradearea
     * @param bool $onlyuser Whether to only export peergrades that the current user has made, or all peergrades
     */
    public static function export_area_peergrades(
            int $userid,
            \context $context,
            array $subcontext,
            string $component,
            string $peergradearea,
            int $itemid,
            bool $onlyuser = true
    ) {
        global $DB;

        $rm = new \peergrade_manager();
        $peergrades = $rm->get_all_peergrades_for_item((object) [
                'context' => $context,
                'component' => $component,
                'peergradearea' => $peergradearea,
                'itemid' => $itemid,
        ]);

        if ($onlyuser) {
            $peergrades = array_filter($peergrades, function($peergrade) use ($userid) {
                return ($peergrade->userid == $userid);
            });
        }

        if (empty($peergrades)) {
            return;
        }

        $toexport = array_map(function($peergrade) {
            return (object) [
                    'peergrade' => $peergrade->peergrade,
                    'author' => $peergrade->userid,
            ];
        }, $peergrades);

        $writer = \core_privacy\local\request\writer::with_context($context)
                ->export_related_data($subcontext, 'peergrade', $toexport);
    }

    /**
     * Get the SQL required to find all submission items where this user has had any involvements.
     *
     * If possible an inner join should be used.
     *
     * @param string $alias The name of the table alias to use.
     * @param string $component The na eof the component to fetch peergrades for.
     * @param string $peergradearea The peergrade area to fetch results for.
     * @param string $itemidjoin The right-hand-side of the JOIN ON clause.
     * @param int $userid The ID of the user being stored.
     * @param bool $innerjoin Whether to use an inner join (preferred)
     * @return  \stdClass
     */
    public static function get_sql_join($alias, $component, $peergradearea, $itemidjoin, $userid, $innerjoin = false) {
        static $count = 0;
        $count++;

        $userwhere = '';

        if ($innerjoin) {
            // Join the peergrade table with the specified alias and the relevant join params.
            $join = "JOIN {peergrade} {$alias} ON ";
            $join .= "{$alias}.itemid = {$itemidjoin}";

            $userwhere .= "{$alias}.userid = :peergradeuserid{$count} AND ";
            $userwhere .= "{$alias}.component = :peergradecomponent{$count} AND ";
            $userwhere .= "{$alias}.peergradearea = :peergradearea{$count}";
        } else {
            // Join the peergrade table with the specified alias and the relevant join params.
            $join = "LEFT JOIN {peergrade} {$alias} ON ";
            $join .= "{$alias}.userid = :peergradeuserid{$count} AND ";
            $join .= "{$alias}.component = :peergradecomponent{$count} AND ";
            $join .= "{$alias}.peergradearea = :peergradearea{$count} AND ";
            $join .= "{$alias}.itemid = {$itemidjoin}";

            // Match against the specified user.
            $userwhere = "{$alias}.id IS NOT NULL";
        }

        $params = [
                'peergradecomponent' . $count => $component,
                'peergradearea' . $count => $peergradearea,
                'peergradeuserid' . $count => $userid,
        ];

        $return = (object) [
                'join' => $join,
                'params' => $params,
                'userwhere' => $userwhere,
        ];
        return $return;
    }

    /**
     * Deletes all peergrades for a specified context, component, peergradearea and itemid.
     *
     * Only delete peergrades when the item itself was deleted.
     *
     * We never delete peergrades for one user but not others - this may affect grades, therefore peergrades
     * made by particular user are not considered personal information.
     *
     * @param \context $context Details about which context to delete peergrades for.
     * @param string $component Component to delete.
     * @param string $peergradearea PeerGrade area to delete.
     * @param int $itemid The item ID for use with deletion.
     */
    public static function delete_peergrades(\context $context, string $component = null,
            string $peergradearea = null, int $itemid = null) {
        global $DB;

        $options = ['contextid' => $context->id];
        if ($component) {
            $options['component'] = $component;
        }
        if ($peergradearea) {
            $options['peergradearea'] = $peergradearea;
        }
        if ($itemid) {
            $options['itemid'] = $itemid;
        }

        $DB->delete_records('peerforum_peergrade', $options);
    }

    /**
     * Deletes all tag instances for given context, component, itemtype using subquery for itemids
     *
     * In most situations you will want to specify $userid as null. Per-user tag instances
     * are possible in Tags API, however there are no components or standard plugins that actually use them.
     *
     * @param \context $context Details about which context to delete peergrades for.
     * @param string $component Component to delete.
     * @param string $peergradearea PeerGrade area to delete.
     * @param string $itemidstest an SQL fragment that the itemid must match. Used
     *      in the query like WHERE itemid $itemidstest. Must use named parameters,
     *      and may not use named parameters called contextid, component or peergradearea.
     * @param array $params any query params used by $itemidstest.
     */
    public static function delete_peergrades_select(\context $context, string $component,
            string $peergradearea, $itemidstest, $params = []) {
        global $DB;
        $params += ['contextid' => $context->id, 'component' => $component, 'peergradearea' => $peergradearea];
        $DB->delete_records_select('peerforum_peergrade',
                'contextid = :contextid AND component = :component AND peergradearea = :peergradearea AND itemid ' . $itemidstest,
                $params);
    }

    /**
     * Add the list of users who have peergraded in the specified constraints.
     *
     * @param userlist $userlist The userlist to add the users to.
     * @param string $alias An alias prefix to use for peergrade selects to avoid interference with your own sql.
     * @param string $component The component to check.
     * @param string $area The peergrade area to check.
     * @param string $insql The SQL to use in a sub-select for the itemid query.
     * @param array $params The params required for the insql.
     */
    public static function get_users_in_context_from_sql(
            userlist $userlist, string $alias, string $component, string $area, string $insql, $params) {
        // Discussion authors.
        $sql = "SELECT {$alias}.userid
                  FROM {peergrade} {$alias}
                 WHERE {$alias}.component = :{$alias}component
                   AND {$alias}.peergradearea = :{$alias}peergradearea
                   AND {$alias}.itemid IN ({$insql})";

        $params["{$alias}component"] = $component;
        $params["{$alias}peergradearea"] = $area;

        $userlist->add_from_sql('userid', $sql, $params);
    }
}
