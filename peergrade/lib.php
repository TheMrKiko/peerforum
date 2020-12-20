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
 * A class representing a single peergrade and containing some static methods for manipulating peergrades
 *
 * @package    core_peergrade
 * @subpackage peergrade
 * @copyright  2010 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('PEERGRADE_UNSET_PEERGRADE', -999);

define('PEERGRADE_AGGREGATE_NONE', 0); //no peergrades
define('PEERGRADE_AGGREGATE_AVERAGE', 1);
define('PEERGRADE_AGGREGATE_COUNT', 2);
define('PEERGRADE_AGGREGATE_MAXIMUM', 3);
define('PEERGRADE_AGGREGATE_MINIMUM', 4);
define('PEERGRADE_AGGREGATE_SUM', 5);

define('PEERGRADE_DEFAULT_SCALE', 5);

/**
 * The peergrade class represents a single peergrade by a single user
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade implements renderable {

    /**
     * @var stdClass The context in which this peergrade exists
     */
    public $context;

    /**
     * @var string The component using peergrades. For example "mod_forum"
     */
    public $component;

    /**
     * @var string The peergrade area to associate this peergrade with
     *             This allows a plugin to peergrade more than one thing by specifying different peergrade areas
     */
    public $peergradearea = null;

    /**
     * @var int The id of the item (forum post, glossary item etc) being peergraded
     */
    public $itemid;

    /**
     * @var int The id scale (1-5, 0-100) that was in use when the peergrade was submitted
     */
    public $scaleid;

    /**
     * @var int The id of the user who submitted the peergrade
     */
    public $userid;

    /**
     * @var stdclass settings for this peergrade. Necessary to render the peergrade.
     */
    public $settings;

    /**
     * @var int The Id of this peergrade within the peergrade table. This is only set if the peergrade already exists
     */
    public $id = null;

    /**
     * @var int The aggregate of the combined peergrades for the associated item. This is only set if the peergrade already exists
     */
    public $aggregate = null;

    /**
     * @var int The total number of peergrades for the associated item. This is only set if the peergrade already exists
     */
    public $count = 0;

    /**
     * @var int The peergrade the associated user gave the associated item. This is only set if the peergrade already exists
     */
    public $peergrade = null;

    /**
     * @var int The time the associated item was created
     */
    public $itemtimecreated = null;

    /**
     * @var int The id of the user who submitted the peergrade
     */
    public $itemuserid = null;

    /**
     * Constructor.
     *
     * @param stdClass $options {
     *            context => context context to use for the peergrade [required]
     *            component => component using peergrades ie mod_forum [required]
     *            peergradearea => peergradearea to associate this peergrade with [required]
     *            itemid  => int the id of the associated item (forum post, glossary item etc) [required]
     *            scaleid => int The scale in use when the peergrade was submitted [required]
     *            userid  => int The id of the user who submitted the peergrade [required]
     *            settings => Settings for the peergrade object [optional]
     *            id => The id of this peergrade (if the peergrade is from the db) [optional]
     *            aggregate => The aggregate for the peergrade [optional]
     *            count => The number of peergrades [optional]
     *            peergrade => The peergrade given by the user [optional]
     * }
     */
    public function __construct($options) {
        $this->context = $options->context;
        $this->component = $options->component;
        $this->peergradearea = $options->peergradearea;
        $this->itemid = $options->itemid;
        $this->scaleid = $options->scaleid;
        $this->userid = $options->userid;

        if (isset($options->settings)) {
            $this->settings = $options->settings;
        }
        if (isset($options->id)) {
            $this->id = $options->id;
        }
        if (isset($options->aggregate)) {
            $this->aggregate = $options->aggregate;
        }
        if (isset($options->count)) {
            $this->count = $options->count;
        }
        if (isset($options->peergrade)) {
            $this->peergrade = $options->peergrade;
        }
    }

    /**
     * Update this peergrade in the database
     *
     * @param int $peergrade the integer value of this peergrade
     */
    public function update_peergrade($peergrade) {
        global $DB;

        $time = time();

        $data = new stdClass;
        $data->peergrade = $peergrade;
        $data->timemodified = $time;

        $item = new stdclass();
        $item->id = $this->itemid;
        $items = array($item);

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $this->context;
        $peergradeoptions->component = $this->component;
        $peergradeoptions->peergradearea = $this->peergradearea;
        $peergradeoptions->items = $items;
        $peergradeoptions->aggregate = PEERGRADE_AGGREGATE_AVERAGE;//we dont actually care what aggregation method is applied
        $peergradeoptions->scaleid = $this->scaleid;
        $peergradeoptions->userid = $this->userid;

        $rm = new peergrade_manager();
        $items = $rm->get_peergrades($peergradeoptions);
        $firstitem = $items[0]->peergrade;

        if (empty($firstitem->id)) {
            // Insert a new peergrade
            $data->contextid = $this->context->id;
            $data->component = $this->component;
            $data->peergradearea = $this->peergradearea;
            $data->peergrade = $peergrade;
            $data->scaleid = $this->scaleid;
            $data->userid = $this->userid;
            $data->itemid = $this->itemid;
            $data->timecreated = $time;
            $data->timemodified = $time;
            $DB->insert_record('peergrade', $data);
        } else {
            // Update the peergrade
            $data->id = $firstitem->id;
            $DB->update_record('peergrade', $data);
        }
    }

    /**
     * Retreive the integer value of this peergrade
     *
     * @return int the integer value of this peergrade object
     */
    public function get_peergrade() {
        return $this->peergrade;
    }

    /**
     * Returns this peergrades aggregate value as a string.
     *
     * @return string peergrades aggregate value
     */
    public function get_aggregate_string() {

        $aggregate = $this->aggregate;
        $method = $this->settings->aggregationmethod;

        // only display aggregate if aggregation method isn't COUNT
        $aggregatestr = '';
        if ($aggregate && $method != PEERGRADE_AGGREGATE_COUNT) {
            if ($method != PEERGRADE_AGGREGATE_SUM && !$this->settings->scale->isnumeric) {
                $aggregatestr .= $this->settings->scale->scaleitems[round($aggregate)]; //round aggregate as we're using it as an index
            } else { // aggregation is SUM or the scale is numeric
                $aggregatestr .= round($aggregate, 1);
            }
        }

        return $aggregatestr;
    }

    /**
     * Returns true if the user is able to peergrade this peergrade object
     *
     * @param int $userid Current user assumed if left empty
     * @return bool true if the user is able to peergrade this peergrade object
     */
    public function user_can_peergrade($userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        // You can't peergrade your item
        if ($this->itemuserid == $userid) {
            return false;
        }
        // You can't peergrade if you don't have the system cap
        if (!$this->settings->permissions->peergrade) {
            return false;
        }
        // You can't peergrade if you don't have the plugin cap
        if (!$this->settings->pluginpermissions->peergrade) {
            return false;
        }

        // You can't peergrade if the item was outside of the assessment times
        $timestart = $this->settings->assesstimestart;
        $timefinish = $this->settings->assesstimefinish;
        $timecreated = $this->itemtimecreated;
        if (!empty($timestart) && !empty($timefinish) && ($timecreated < $timestart || $timecreated > $timefinish)) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if the user is able to view the aggregate for this peergrade object.
     *
     * @param int|null $userid If left empty the current user is assumed.
     * @return bool true if the user is able to view the aggregate for this peergrade object
     */
    public function user_can_view_aggregate($userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        // if the item doesnt belong to anyone or its another user's items and they can see the aggregate on items they don't own
        // Note that viewany doesnt mean you can see the aggregate or peergrades of your own items
        if ((empty($this->itemuserid) or $this->itemuserid != $userid) && $this->settings->permissions->viewany &&
                $this->settings->pluginpermissions->viewany) {
            return true;
        }

        // if its the current user's item and they have permission to view the aggregate on their own items
        if ($this->itemuserid == $userid && $this->settings->permissions->view && $this->settings->pluginpermissions->view) {
            return true;
        }

        return false;
    }

    /**
     * Returns a URL to view all of the peergrades for the item this peergrade is for.
     *
     * If this is a peergrade of a post then this URL will take the user to a page that shows all of the peergrades for the post
     * (this one included).
     *
     * @param bool $popup whether of not the URL should be loaded in a popup
     * @return moodle_url URL to view all of the peergrades for the item this peergrade is for.
     */
    public function get_view_peergrades_url($popup = false) {
        $attributes = array(
                'contextid' => $this->context->id,
                'component' => $this->component,
                'peergradearea' => $this->peergradearea,
                'itemid' => $this->itemid,
                'scaleid' => $this->settings->scale->id
        );
        if ($popup) {
            $attributes['popup'] = 1;
        }
        return new moodle_url('/peergrade/index.php', $attributes);
    }

    /**
     * Returns a URL that can be used to peergrade the associated item.
     *
     * @param int|null $peergrade The peergrade to give the item, if null then no peergrade param is added.
     * @param moodle_url|string $returnurl The URL to return to.
     * @return moodle_url can be used to peergrade the associated item.
     */
    public function get_peergrade_url($peergrade = null, $returnurl = null) {
        if (empty($returnurl)) {
            if (!empty($this->settings->returnurl)) {
                $returnurl = $this->settings->returnurl;
            } else {
                global $PAGE;
                $returnurl = $PAGE->url;
            }
        }
        $args = array(
                'contextid' => $this->context->id,
                'component' => $this->component,
                'peergradearea' => $this->peergradearea,
                'itemid' => $this->itemid,
                'scaleid' => $this->settings->scale->id,
                'returnurl' => $returnurl,
                'peergradeduserid' => $this->itemuserid,
                'aggregation' => $this->settings->aggregationmethod,
                'sesskey' => sesskey()
        );
        if (!empty($peergrade)) {
            $args['peergrade'] = $peergrade;
        }
        $url = new moodle_url('/peergrade/peergrade.php', $args);
        return $url;
    }

    /**
     * Remove this peergrade from the database
     *
     * @return void
     */
    //public function delete_peergrade() {
    //todo implement this if its actually needed
    //}
} //end peergrade class definition

/**
 * The peergrade_manager class provides the ability to retrieve sets of peergrades from the database
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade_manager {

    /**
     * @var array An array of calculated scale options to save us genepeergrade them for each request.
     */
    protected $scales = array();

    /**
     * Delete one or more peergrades. Specify either a peergrade id, an item id or just the context id.
     *
     * @param stdClass $options {
     *            contextid => int the context in which the peergrades exist [required]
     *            peergradeid => int the id of an individual peergrade to delete [optional]
     *            userid => int delete the peergrades submitted by this user. May be used in conjuction with itemid [optional]
     *            itemid => int delete all peergrades attached to this item [optional]
     *            component => string The component to delete peergrades from [optional]
     *            peergradearea => string The peergradearea to delete peergrades from [optional]
     * }
     * @global moodle_database $DB
     */
    public function delete_peergrades($options) {
        global $DB;

        if (empty($options->contextid)) {
            throw new coding_exception('The context option is a required option when deleting peergrades.');
        }

        $conditions = array('contextid' => $options->contextid);
        $possibleconditions = array(
                'peergradeid' => 'id',
                'userid' => 'userid',
                'itemid' => 'itemid',
                'component' => 'component',
                'peergradearea' => 'peergradearea'
        );
        foreach ($possibleconditions as $option => $field) {
            if (isset($options->{$option})) {
                $conditions[$field] = $options->{$option};
            }
        }
        $DB->delete_records('peergrade', $conditions);
    }

    /**
     * Returns an array of peergrades for a given item (forum post, glossary entry etc). This returns all users peergrades for a
     * single item
     *
     * @param stdClass $options {
     *            context => context the context in which the peergrades exists [required]
     *            component => component using peergrades ie mod_forum [required]
     *            peergradearea => peergradearea to associate this peergrade with [required]
     *            itemid  =>  int the id of the associated item (forum post, glossary item etc) [required]
     *            sort    => string SQL sort by clause [optional]
     * }
     * @return array an array of peergrades
     */
    public function get_all_peergrades_for_item($options) {
        global $DB;

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when getting peergrades for an item.');
        }
        if (!isset($options->itemid)) {
            throw new coding_exception('The itemid option is a required option when getting peergrades for an item.');
        }
        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when getting peergrades for an item.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required option when getting peergrades for an item.');
        }

        $sortclause = '';
        if (!empty($options->sort)) {
            $sortclause = "ORDER BY $options->sort";
        }

        $params = array(
                'contextid' => $options->context->id,
                'itemid' => $options->itemid,
                'component' => $options->component,
                'peergradearea' => $options->peergradearea,
        );
        $userfields = user_picture::fields('u', null, 'userid');
        $sql = "SELECT r.id, r.peergrade, r.itemid, r.userid, r.timemodified, r.component, r.peergradearea, $userfields
                  FROM {peergrade} r
             LEFT JOIN {user} u ON r.userid = u.id
                 WHERE r.contextid = :contextid AND
                       r.itemid  = :itemid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
                       {$sortclause}";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Adds peergrade objects to an array of items (forum posts, glossary entries etc). Rating objects are available at
     * $item->peergrade
     *
     * @param stdClass $options {
     *            context          => context the context in which the peergrades exists [required]
     *            component        => the component name ie mod_forum [required]
     *            peergradearea       => the peergradearea we are interested in [required]
     *            items            => array an array of items such as forum posts or glossary items. They must have an 'id' member
     *         ie $items[0]->id[required] aggregate        => int what aggregation method should be applied.
     *         PEERGRADE_AGGREGATE_AVERAGE, PEERGRADE_AGGREGATE_MAXIMUM etc [required] scaleid          => int the scale from which
     *         the user can select a peergrade [required] userid           => int the id of the current user [optional] returnurl
     *         => string the url to return the user to after submitting a peergrade. Can be left null for ajax requests [optional]
     *         assesstimestart  => int only allow peergrade of items created after this timestamp [optional] assesstimefinish =>
     *         int
     *         only allow peergrade of items created before this timestamp [optional]
     * @return array the array of items with their peergrades attached at $items[0]->peergrade
     */
    public function get_peergrades($options) {
        global $DB, $USER;

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when getting peergrades.');
        }

        if (!isset($options->component)) {
            throw new coding_exception('The component option is a required option when getting peergrades.');
        }

        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is a required option when getting peergrades.');
        }

        if (!isset($options->scaleid)) {
            throw new coding_exception('The scaleid option is a required option when getting peergrades.');
        }

        if (!isset($options->items)) {
            throw new coding_exception('The items option is a required option when getting peergrades.');
        } else if (empty($options->items)) {
            return array();
        }

        if (!isset($options->aggregate)) {
            throw new coding_exception('The aggregate option is a required option when getting peergrades.');
        } else if ($options->aggregate == PEERGRADE_AGGREGATE_NONE) {
            // Ratings arn't enabled.
            return $options->items;
        }
        $aggregatestr = $this->get_aggregation_method($options->aggregate);

        // Default the userid to the current user if it is not set
        if (empty($options->userid)) {
            $userid = $USER->id;
        } else {
            $userid = $options->userid;
        }

        // Get the item table name, the item id field, and the item user field for the given peergrade item
        // from the related component.
        list($type, $name) = core_component::normalize_component($options->component);
        $default = array(null, 'id', 'userid');
        list($itemtablename, $itemidcol, $itemuseridcol) =
                plugin_callback($type, $name, 'peergrade', 'get_item_fields', array($options), $default);

        // Create an array of item ids
        $itemids = array();
        foreach ($options->items as $item) {
            $itemids[] = $item->{$itemidcol};
        }

        // get the items from the database
        list($itemidtest, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
        $params['contextid'] = $options->context->id;
        $params['userid'] = $userid;
        $params['component'] = $options->component;
        $params['peergradearea'] = $options->peergradearea;

        $sql = "SELECT r.id, r.itemid, r.userid, r.scaleid, r.peergrade AS userspeergrade
                  FROM {peergrade} r
                 WHERE r.userid = :userid AND
                       r.contextid = :contextid AND
                       r.itemid {$itemidtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
              ORDER BY r.itemid";
        $userpeergrades = $DB->get_records_sql($sql, $params);

        $sql = "SELECT r.itemid, $aggregatestr(r.peergrade) AS aggrpeergrade, COUNT(r.peergrade) AS numpeergrades
                  FROM {peergrade} r
                 WHERE r.contextid = :contextid AND
                       r.itemid {$itemidtest} AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
              GROUP BY r.itemid, r.component, r.peergradearea, r.contextid
              ORDER BY r.itemid";
        $aggregatepeergrades = $DB->get_records_sql($sql, $params);

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $options->context;
        $peergradeoptions->component = $options->component;
        $peergradeoptions->peergradearea = $options->peergradearea;
        $peergradeoptions->settings = $this->genepeergrade_peergrade_settings_object($options);
        foreach ($options->items as $item) {
            $founduserpeergrade = false;
            foreach ($userpeergrades as $userpeergrade) {
                //look for an existing peergrade from this user of this item
                if ($item->{$itemidcol} == $userpeergrade->itemid) {
                    // Note: rec->scaleid = the id of scale at the time the peergrade was submitted
                    // may be different from the current scale id
                    $peergradeoptions->scaleid = $userpeergrade->scaleid;
                    $peergradeoptions->userid = $userpeergrade->userid;
                    $peergradeoptions->id = $userpeergrade->id;
                    $peergradeoptions->peergrade = min($userpeergrade->userspeergrade, $peergradeoptions->settings->scale->max);

                    $founduserpeergrade = true;
                    break;
                }
            }
            if (!$founduserpeergrade) {
                $peergradeoptions->scaleid = null;
                $peergradeoptions->userid = null;
                $peergradeoptions->id = null;
                $peergradeoptions->peergrade = null;
            }

            if (array_key_exists($item->{$itemidcol}, $aggregatepeergrades)) {
                $rec = $aggregatepeergrades[$item->{$itemidcol}];
                $peergradeoptions->itemid = $item->{$itemidcol};
                $peergradeoptions->aggregate = min($rec->aggrpeergrade, $peergradeoptions->settings->scale->max);
                $peergradeoptions->count = $rec->numpeergrades;
            } else {
                $peergradeoptions->itemid = $item->{$itemidcol};
                $peergradeoptions->aggregate = null;
                $peergradeoptions->count = 0;
            }

            $peergrade = new peergrade($peergradeoptions);
            $peergrade->itemtimecreated = $this->get_item_time_created($item);
            if (!empty($item->{$itemuseridcol})) {
                $peergrade->itemuserid = $item->{$itemuseridcol};
            }
            $item->peergrade = $peergrade;
        }

        return $options->items;
    }

    /**
     * Genepeergrades a peergrade settings object based upon the options it is provided.
     *
     * @param stdClass $options {
     *      context           => context the context in which the peergrades exists [required]
     *      component         => string The component the items belong to [required]
     *      peergradearea        => string The peergradearea the items belong to [required]
     *      aggregate         => int what aggregation method should be applied. PEERGRADE_AGGREGATE_AVERAGE,
     *         PEERGRADE_AGGREGATE_MAXIMUM etc [required] scaleid           => int the scale from which the user can select a
     *         peergrade [required] returnurl
     *           => string the url to return the user to after submitting a peergrade. Can be left null for ajax requests
     *         [optional]
     *         assesstimestart   => int only allow peergrade of items created after this timestamp [optional] assesstimefinish  =>
     *         int only allow peergrade of items created before this timestamp [optional] plugintype        => string plugin type
     *         ie 'mod' Used to find the permissions callback [optional] pluginname        => string plugin name ie 'forum' Used to
     *         find the permissions callback [optional]
     * }
     * @return stdClass peergrade settings object
     */
    protected function genepeergrade_peergrade_settings_object($options) {

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when genepeergrade a peergrade settings object.');
        }
        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when genepeergrade a peergrade settings object.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required option when genepeergrade a peergrade settings object.');
        }
        if (!isset($options->aggregate)) {
            throw new coding_exception('The aggregate option is now a required option when genepeergrade a peergrade settings object.');
        }
        if (!isset($options->scaleid)) {
            throw new coding_exception('The scaleid option is now a required option when genepeergrade a peergrade settings object.');
        }

        // settings that are common to all peergrades objects in this context
        $settings = new stdClass;
        $settings->scale = $this->genepeergrade_peergrade_scale_object($options->scaleid); // the scale to use now
        $settings->aggregationmethod = $options->aggregate;
        $settings->assesstimestart = null;
        $settings->assesstimefinish = null;

        // Collect options into the settings object
        if (!empty($options->assesstimestart)) {
            $settings->assesstimestart = $options->assesstimestart;
        }
        if (!empty($options->assesstimefinish)) {
            $settings->assesstimefinish = $options->assesstimefinish;
        }
        if (!empty($options->returnurl)) {
            $settings->returnurl = $options->returnurl;
        }

        // check site capabilities
        $settings->permissions = new stdClass;
        $settings->permissions->view =
                has_capability('moodle/peergrade:view',
                        $options->context); // can view the aggregate of peergrades of their own items
        $settings->permissions->viewany = has_capability('moodle/peergrade:viewany',
                $options->context); // can view the aggregate of peergrades of other people's items
        $settings->permissions->viewall =
                has_capability('moodle/peergrade:viewall', $options->context); // can view individual peergrades
        $settings->permissions->peergrade =
                has_capability('moodle/peergrade:peergrade', $options->context); // can submit peergrades

        // check module capabilities (mostly for backwards compatability with old modules that previously implemented their own peergrades)
        $pluginpermissionsarray =
                $this->get_plugin_permissions_array($options->context->id, $options->component, $options->peergradearea);
        $settings->pluginpermissions = new stdClass;
        $settings->pluginpermissions->view = $pluginpermissionsarray['view'];
        $settings->pluginpermissions->viewany = $pluginpermissionsarray['viewany'];
        $settings->pluginpermissions->viewall = $pluginpermissionsarray['viewall'];
        $settings->pluginpermissions->peergrade = $pluginpermissionsarray['peergrade'];

        return $settings;
    }

    /**
     * Genepeergrades a scale object that can be returned
     *
     * @param int $scaleid scale-type identifier
     * @return stdClass scale for peergrades
     * @global moodle_database $DB moodle database object
     */
    protected function genepeergrade_peergrade_scale_object($scaleid) {
        global $DB;
        if (!array_key_exists('s' . $scaleid, $this->scales)) {
            $scale = new stdClass;
            $scale->id = $scaleid;
            $scale->name = null;
            $scale->courseid = null;
            $scale->scaleitems = array();
            $scale->isnumeric = true;
            $scale->max = $scaleid;

            if ($scaleid < 0) {
                // It is a proper scale (not numeric)
                $scalerecord = $DB->get_record('scale', array('id' => abs($scaleid)));
                if ($scalerecord) {
                    // We need to genepeergrade an array with string keys starting at 1
                    $scalearray = explode(',', $scalerecord->scale);
                    $c = count($scalearray);
                    for ($i = 0; $i < $c; $i++) {
                        // treat index as a string to allow sorting without changing the value
                        $scale->scaleitems[(string) ($i + 1)] = $scalearray[$i];
                    }
                    krsort($scale->scaleitems); // have the highest grade scale item appear first
                    $scale->isnumeric = false;
                    $scale->name = $scalerecord->name;
                    $scale->courseid = $scalerecord->courseid;
                    $scale->max = count($scale->scaleitems);
                }
            } else {
                //genepeergrade an array of values for numeric scales
                for ($i = 0; $i <= (int) $scaleid; $i++) {
                    $scale->scaleitems[(string) $i] = $i;
                }
            }
            $this->scales['s' . $scaleid] = $scale;
        }
        return $this->scales['s' . $scaleid];
    }

    /**
     * Gets the time the given item was created
     *
     * TODO: MDL-31511 - Find a better solution for this, its not ideal to test for fields really we should be
     * asking the component the item belongs to what field to look for or even the value we
     * are looking for.
     *
     * @param stdClass $item
     * @return int|null return null if the created time is unavailable, otherwise return a timestamp
     */
    protected function get_item_time_created($item) {
        if (!empty($item->created)) {
            return $item->created;//the forum_posts table has created instead of timecreated
        } else if (!empty($item->timecreated)) {
            return $item->timecreated;
        } else {
            return null;
        }
    }

    /**
     * Returns an array of grades calculated by aggregating item peergrades.
     *
     * @param stdClass $options {
     *            userid => int the id of the user whose items have been peergraded. NOT the user who submitted the peergrades. 0
     *         to update all. [required] aggregationmethod => int the aggregation method to apply when calculating grades ie
     *         PEERGRADE_AGGREGATE_AVERAGE [required] scaleid => int the scale from which the user can select a peergrade. Used for
     *         bounds checking. [required] itemtable => int the table containing the items [required] itemtableusercolum => int the
     *         column of the user table containing the item owner's user id [required] component => The component for the
     *         peergrades
     *         [required] peergradearea => The peergradearea for the peergrades [required] contextid => int the context in which
     *         the peergraded items exist [optional] modulename => string the name of the module [optional] moduleid => int the id
     *         of the module instance [optional]
     * }
     * @return array the array of the user's grades
     */
    public function get_user_grades($options) {
        global $DB;

        $contextid = null;

        if (!isset($options->component)) {
            throw new coding_exception('The component option is now a required option when getting user grades from peergrades.');
        }
        if (!isset($options->peergradearea)) {
            throw new coding_exception('The peergradearea option is now a required option when getting user grades from peergrades.');
        }

        //if the calling code doesn't supply a context id we'll have to figure it out
        if (!empty($options->contextid)) {
            $contextid = $options->contextid;
        } else if (!empty($options->cmid)) {
            //not implemented as not currently used although cmid is potentially available (the forum supplies it)
            //Is there a convenient way to get a context id from a cm id?
            //$cmidnumber = $options->cmidnumber;
        } else if (!empty($options->modulename) && !empty($options->moduleid)) {
            $modulename = $options->modulename;
            $moduleid = intval($options->moduleid);

            // Going direct to the db for the context id seems wrong.
            $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
            $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel)";
            $sql = "SELECT cm.* $ctxselect
                      FROM {course_modules} cm
                 LEFT JOIN {modules} mo ON mo.id = cm.module
                 LEFT JOIN {{$modulename}} m ON m.id = cm.instance $ctxjoin
                     WHERE mo.name=:modulename AND
                           m.id=:moduleid";
            $params = array('modulename' => $modulename, 'moduleid' => $moduleid, 'contextlevel' => CONTEXT_MODULE);
            $contextrecord = $DB->get_record_sql($sql, $params, '*', MUST_EXIST);
            $contextid = $contextrecord->ctxid;
        }

        $params = array();
        $params['contextid'] = $contextid;
        $params['component'] = $options->component;
        $params['peergradearea'] = $options->peergradearea;
        $itemtable = $options->itemtable;
        $itemtableusercolumn = $options->itemtableusercolumn;
        $scaleid = $options->scaleid;
        $aggregationstring = $this->get_aggregation_method($options->aggregationmethod);

        //if userid is not 0 we only want the grade for a single user
        $singleuserwhere = '';
        if ($options->userid != 0) {
            $params['userid1'] = intval($options->userid);
            $singleuserwhere = "AND i.{$itemtableusercolumn} = :userid1";
        }

        //MDL-24648 The where line used to be "WHERE (r.contextid is null or r.contextid=:contextid)"
        //r.contextid will be null for users who haven't been peergraded yet
        //no longer including users who haven't been peergraded to reduce memory requirements
        $sql = "SELECT u.id as id, u.id AS userid, $aggregationstring(r.peergrade) AS rawgrade
                  FROM {user} u
             LEFT JOIN {{$itemtable}} i ON u.id=i.{$itemtableusercolumn}
             LEFT JOIN {peergrade} r ON r.itemid=i.id
                 WHERE r.contextid = :contextid AND
                       r.component = :component AND
                       r.peergradearea = :peergradearea
                       $singleuserwhere
              GROUP BY u.id";
        $results = $DB->get_records_sql($sql, $params);

        if ($results) {

            $scale = null;
            $max = 0;
            if ($options->scaleid >= 0) {
                //numeric
                $max = $options->scaleid;
            } else {
                //custom scales
                $scale = $DB->get_record('scale', array('id' => -$options->scaleid));
                if ($scale) {
                    $scale = explode(',', $scale->scale);
                    $max = count($scale);
                } else {
                    debugging('peergrade_manager::get_user_grades() received a scale ID that doesnt exist');
                }
            }

            // it could throw off the grading if count and sum returned a rawgrade higher than scale
            // so to prevent it we review the results and ensure that rawgrade does not exceed the scale, if it does we set rawgrade = scale (i.e. full credit)
            foreach ($results as $rid => $result) {
                if ($options->scaleid >= 0) {
                    //numeric
                    if ($result->rawgrade > $options->scaleid) {
                        $results[$rid]->rawgrade = $options->scaleid;
                    }
                } else {
                    //scales
                    if (!empty($scale) && $result->rawgrade > $max) {
                        $results[$rid]->rawgrade = $max;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Returns array of aggregate types. Used by peergrades.
     *
     * @return array aggregate types
     */
    public function get_aggregate_types() {
        return array(PEERGRADE_AGGREGATE_NONE => get_string('aggregatenone', 'peergrade'),
                PEERGRADE_AGGREGATE_AVERAGE => get_string('aggregateavg', 'peergrade'),
                PEERGRADE_AGGREGATE_COUNT => get_string('aggregatecount', 'peergrade'),
                PEERGRADE_AGGREGATE_MAXIMUM => get_string('aggregatemax', 'peergrade'),
                PEERGRADE_AGGREGATE_MINIMUM => get_string('aggregatemin', 'peergrade'),
                PEERGRADE_AGGREGATE_SUM => get_string('aggregatesum', 'peergrade'));
    }

    /**
     * Converts an aggregation method constant into something that can be included in SQL
     *
     * @param int $aggregate An aggregation constant. For example, PEERGRADE_AGGREGATE_AVERAGE.
     * @return string an SQL aggregation method
     */
    public function get_aggregation_method($aggregate) {
        $aggregatestr = null;
        switch ($aggregate) {
            case PEERGRADE_AGGREGATE_AVERAGE:
                $aggregatestr = 'AVG';
                break;
            case PEERGRADE_AGGREGATE_COUNT:
                $aggregatestr = 'COUNT';
                break;
            case PEERGRADE_AGGREGATE_MAXIMUM:
                $aggregatestr = 'MAX';
                break;
            case PEERGRADE_AGGREGATE_MINIMUM:
                $aggregatestr = 'MIN';
                break;
            case PEERGRADE_AGGREGATE_SUM:
                $aggregatestr = 'SUM';
                break;
            default:
                $aggregatestr = 'AVG'; // Default to this to avoid real breakage - MDL-22270
                debugging('Incorrect call to get_aggregation_method(), was called with incorrect aggregate method ' . $aggregate,
                        DEBUG_DEVELOPER);
        }
        return $aggregatestr;
    }

    /**
     * Looks for a callback like forum_peergrade_permissions() to retrieve permissions from the plugin whose items are being
     * peergraded
     *
     * @param int $contextid The current context id
     * @param string $component the name of the component that is using peergrades ie 'mod_forum'
     * @param string $peergradearea The area the peergrade is associated with
     * @return array peergrade related permissions
     */
    public function get_plugin_permissions_array($contextid, $component, $peergradearea) {
        $pluginpermissionsarray = null;
        $defaultpluginpermissions =
                array('peergrade' => false, 'view' => false, 'viewany' => false, 'viewall' => false);//deny by default
        if (!empty($component)) {
            list($type, $name) = core_component::normalize_component($component);
            $pluginpermissionsarray =
                    plugin_callback($type, $name, 'peergrade', 'permissions', array($contextid, $component, $peergradearea),
                            $defaultpluginpermissions);
        } else {
            $pluginpermissionsarray = $defaultpluginpermissions;
        }
        return $pluginpermissionsarray;
    }

    /**
     * Validates a submitted peergrade
     *
     * @param array $params submitted data
     *            context => object the context in which the peergraded items exists [required]
     *            component => The component the peergrade belongs to [required]
     *            peergradearea => The peergradearea the peergrade is associated with [required]
     *            itemid => int the ID of the object being peergraded [required]
     *            scaleid => int the scale from which the user can select a peergrade. Used for bounds checking. [required]
     *            peergrade => int the submitted peergrade
     *            peergradeduserid => int the id of the user whose items have been peergraded. NOT the user who submitted the
     *         peergrades. 0 to update all. [required] aggregation => int the aggregation method to apply when calculating grades
     *         ie PEERGRADE_AGGREGATE_AVERAGE [optional]
     * @return boolean true if the peergrade is valid. False if callback wasnt found and will throw peergrade_exception if
     *         peergrade is invalid
     */
    public function check_peergrade_is_valid($params) {

        if (!isset($params['context'])) {
            throw new coding_exception('The context option is a required option when checking peergrade validity.');
        }
        if (!isset($params['component'])) {
            throw new coding_exception('The component option is now a required option when checking peergrade validity');
        }
        if (!isset($params['peergradearea'])) {
            throw new coding_exception('The peergradearea option is now a required option when checking peergrade validity');
        }
        if (!isset($params['itemid'])) {
            throw new coding_exception('The itemid option is now a required option when checking peergrade validity');
        }
        if (!isset($params['scaleid'])) {
            throw new coding_exception('The scaleid option is now a required option when checking peergrade validity');
        }
        if (!isset($params['peergradeduserid'])) {
            throw new coding_exception('The peergradeduserid option is now a required option when checking peergrade validity');
        }

        list($plugintype, $pluginname) = core_component::normalize_component($params['component']);

        //this looks for a function like forum_peergrade_validate() in mod_forum lib.php
        //wrapping the params array in another array as call_user_func_array() expands arrays into multiple arguments
        $isvalid = plugin_callback($plugintype, $pluginname, 'peergrade', 'validate', array($params), null);

        //if null then the callback doesn't exist
        if ($isvalid === null) {
            $isvalid = false;
            debugging('peergrade validation callback not found for component ' . clean_param($component, PARAM_ALPHANUMEXT));
        }
        return $isvalid;
    }

    /**
     * Initialises JavaScript to enable AJAX peergrades on the provided page
     *
     * @param moodle_page $page
     * @return true always returns true
     */
    public function initialise_peergrade_javascript(moodle_page $page) {
        global $CFG;

        //only needs to be initialized once
        static $done = false;
        if ($done) {
            return true;
        }

        if (!empty($CFG->enableajax)) {
            $page->requires->js_init_call('M.core_peergrade.init');
        }
        $done = true;

        return true;
    }

    /**
     * Returns a string that describes the aggregation method that was provided.
     *
     * @param string $aggregationmethod
     * @return string describes the aggregation method that was provided
     */
    public function get_aggregate_label($aggregationmethod) {
        $aggregatelabel = '';
        switch ($aggregationmethod) {
            case PEERGRADE_AGGREGATE_AVERAGE :
                $aggregatelabel .= get_string("aggregateavg", "peergrade");
                break;
            case PEERGRADE_AGGREGATE_COUNT :
                $aggregatelabel .= get_string("aggregatecount", "peergrade");
                break;
            case PEERGRADE_AGGREGATE_MAXIMUM :
                $aggregatelabel .= get_string("aggregatemax", "peergrade");
                break;
            case PEERGRADE_AGGREGATE_MINIMUM :
                $aggregatelabel .= get_string("aggregatemin", "peergrade");
                break;
            case PEERGRADE_AGGREGATE_SUM :
                $aggregatelabel .= get_string("aggregatesum", "peergrade");
                break;
        }
        $aggregatelabel .= get_string('labelsep', 'langconfig');
        return $aggregatelabel;
    }

}//end peergrade_manager class definition

/**
 * The peergrade_exception class provides the ability to genepeergrade exceptions that can be easily identified as coming from the
 * peergrades system
 *
 * @package   core_peergrade
 * @category  peergrade
 * @copyright 2010 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class peergrade_exception extends moodle_exception {
    /**
     * @var string The message to accompany the thrown exception
     */
    public $message;

    /**
     * Genepeergrade exceptions that can be easily identified as coming from the peergrades system
     *
     * @param string $errorcode the error code to genepeergrade
     */
    function __construct($errorcode) {
        $this->errorcode = $errorcode;
        $this->message = get_string($errorcode, 'error');
    }
}
