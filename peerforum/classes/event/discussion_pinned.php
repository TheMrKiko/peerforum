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
 * The mod_peerforum discussion pinned event.
 *
 * @package    mod_peerforum
 * @copyright  2014 Charles Fulton <fultonc@lafayette.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_peerforum discussion pinned event.
 *
 * @package    mod_peerforum
 * @copyright  2014 Charles Fulton <fultonc@lafayette.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussion_pinned extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'peerforum_discussions';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user {$this->userid} has pinned the discussion {$this->objectid} in the peerforum {$this->other['peerforumid']}";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdiscussionpinned', 'mod_peerforum');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/peerforum/discuss.php', array('d' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        // The legacy log table expects a relative path to /mod/peerforum/.
        $logurl = substr($this->get_url()->out_as_local_url(), strlen('/mod/peerforum/'));
        return array($this->courseid, 'peerforum', 'pin discussion', $logurl, $this->objectid, $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['peerforumid'])) {
            throw new \coding_exception('peerforumid must be set in $other.');
        }
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context passed must be module context.');
        }
        if (!isset($this->objectid)) {
            throw new \coding_exception('objectid must be set to the discussionid.');
        }
    }

    /**
     * PeerForum discussion object id mappings.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return array('db' => 'peerforum_discussions', 'restore' => 'peerforum_discussion');
    }

    /**
     * PeerForum id mappings.
     *
     * @return array
     */
    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['peerforumid'] = array('db' => 'peerforum', 'restore' => 'peerforum');

        return $othermapped;
    }
}
