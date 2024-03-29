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
 * @package   mod_peerforum
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/peerforum/lib.php');

    $settings->add(new admin_setting_configselect('peerforum_displaymode', get_string('displaymode', 'peerforum'),
            get_string('configdisplaymode', 'peerforum'), PEERFORUM_MODE_NESTED, peerforum_get_layout_modes()));

    // Less non-HTML characters than this is short
    $settings->add(new admin_setting_configtext('peerforum_shortpost', get_string('shortpost', 'peerforum'),
            get_string('configshortpost', 'peerforum'), 300, PARAM_INT));

    // More non-HTML characters than this is long
    $settings->add(new admin_setting_configtext('peerforum_longpost', get_string('longpost', 'peerforum'),
            get_string('configlongpost', 'peerforum'), 600, PARAM_INT));

    // Number of discussions on a page
    $settings->add(new admin_setting_configtext('peerforum_manydiscussions', get_string('manydiscussions', 'peerforum'),
            get_string('configmanydiscussions', 'peerforum'), 100, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $maxbytes = 0;
        if (isset($CFG->peerforum_maxbytes)) {
            $maxbytes = $CFG->peerforum_maxbytes;
        }
        $settings->add(new admin_setting_configselect('peerforum_maxbytes', get_string('maxattachmentsize', 'peerforum'),
                get_string('configmaxbytes', 'peerforum'), 512000, get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)));
    }

    // Default number of attachments allowed per post in all peerforums
    $settings->add(new admin_setting_configtext('peerforum_maxattachments', get_string('maxattachments', 'peerforum'),
            get_string('configmaxattachments', 'peerforum'), 9, PARAM_INT));

    // Default Subscription mode setting.
    $options = peerforum_get_subscriptionmode_options();
    $settings->add(new admin_setting_configselect('peerforum_subscription', get_string('subscriptionmode', 'peerforum'),
            get_string('configsubscriptiontype', 'peerforum'), PEERFORUM_CHOOSESUBSCRIBE, $options));

    // Default Read Tracking setting.
    $options = array();
    $options[PEERFORUM_TRACKING_OPTIONAL] = get_string('trackingoptional', 'peerforum');
    $options[PEERFORUM_TRACKING_OFF] = get_string('trackingoff', 'peerforum');
    $options[PEERFORUM_TRACKING_FORCED] = get_string('trackingon', 'peerforum');
    $settings->add(new admin_setting_configselect('peerforum_trackingtype', get_string('trackingtype', 'peerforum'),
            get_string('configtrackingtype', 'peerforum'), PEERFORUM_TRACKING_OPTIONAL, $options));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('peerforum_trackreadposts', get_string('trackforum', 'peerforum'),
            get_string('configtrackreadposts', 'peerforum'), 1));

    // Default whether user needs to mark a post as read.
    $settings->add(new admin_setting_configcheckbox('peerforum_allowforcedreadtracking',
            get_string('forcedreadtracking', 'peerforum'),
            get_string('forcedreadtracking_desc', 'peerforum'), 0));

    // Default number of days that a post is considered old
    $settings->add(new admin_setting_configtext('peerforum_oldpostdays', get_string('oldpostdays', 'peerforum'),
            get_string('configoldpostdays', 'peerforum'), 14, PARAM_INT));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('peerforum_usermarksread', get_string('usermarksread', 'peerforum'),
            get_string('configusermarksread', 'peerforum'), 0));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d", $i);
    }
    // Default time (hour) to execute 'clean_read_records' cron
    $settings->add(new admin_setting_configselect('peerforum_cleanreadtime', get_string('cleanreadtime', 'peerforum'),
            get_string('configcleanreadtime', 'peerforum'), 2, $options));

    // Default time (hour) to send digest email
    $settings->add(new admin_setting_configselect('digestmailtime', get_string('digestmailtime', 'peerforum'),
            get_string('configdigestmailtime', 'peerforum'), 17, $options));

    if (empty($CFG->enablerssfeeds)) {
        $options = array(0 => get_string('rssglobaldisabled', 'admin'));
        $str = get_string('configenablerssfeeds', 'peerforum') . '<br />' . get_string('configenablerssfeedsdisabled2', 'admin');

    } else {
        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $str = get_string('configenablerssfeeds', 'peerforum');
    }
    $settings->add(new admin_setting_configselect('peerforum_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
            $str, 0, $options));

    if (!empty($CFG->enablerssfeeds)) {
        $options = array(
                0 => get_string('none'),
                1 => get_string('discussions', 'peerforum'),
                2 => get_string('posts', 'peerforum')
        );
        $settings->add(new admin_setting_configselect('peerforum_rsstype', get_string('rsstypedefault', 'peerforum'),
                get_string('configrsstypedefault', 'peerforum'), 0, $options));
        $settings->hide_if('peerforum_rsstype', 'peerforum_enablerssfeeds', 'neq', '1');

        $options = array(
                0 => '0',
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
                10 => '10',
                15 => '15',
                20 => '20',
                25 => '25',
                30 => '30',
                40 => '40',
                50 => '50'
        );
        $settings->add(new admin_setting_configselect('peerforum_rssarticles', get_string('rssarticles', 'peerforum'),
                get_string('configrssarticlesdefault', 'peerforum'), 0, $options));
        $settings->hide_if('peerforum_rssarticles', 'peerforum_enablerssfeeds', 'neq', '1');
    }

    $settings->add(new admin_setting_configcheckbox('peerforum_enabletimedposts', get_string('timedposts', 'peerforum'),
            get_string('configenabletimedposts', 'peerforum'), 1));

}

