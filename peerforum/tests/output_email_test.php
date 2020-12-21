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
 * The module peerforums tests
 *
 * @package    mod_peerforum
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the peerforum output/email class.
 *
 * @copyright  2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerforum_output_email_testcase extends advanced_testcase {
    /**
     * Data provider for the postdate function tests.
     */
    public function postdate_provider() {
        return array(
                'Timed discussions disabled, timestart unset' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 0,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(),
                        'expectation' => 1000,
                ),
                'Timed discussions disabled, timestart set and newer' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 0,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(
                                'timestart' => 2000,
                        ),
                        'expectation' => 1000,
                ),
                'Timed discussions disabled, timestart set but older' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 0,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(
                                'timestart' => 500,
                        ),
                        'expectation' => 1000,
                ),
                'Timed discussions enabled, timestart unset' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 1,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(),
                        'expectation' => 1000,
                ),
                'Timed discussions enabled, timestart set and newer' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 1,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(
                                'timestart' => 2000,
                        ),
                        'expectation' => 2000,
                ),
                'Timed discussions enabled, timestart set but older' => array(
                        'globalconfig' => array(
                                'peerforum_enabletimedposts' => 1,
                        ),
                        'peerforumconfig' => array(),
                        'postconfig' => array(
                                'modified' => 1000,
                        ),
                        'discussionconfig' => array(
                                'timestart' => 500,
                        ),
                        'expectation' => 1000,
                ),
        );
    }

    /**
     * Test for the peerforum email renderable postdate.
     *
     * @dataProvider postdate_provider
     *
     * @param array $globalconfig The configuration to set on $CFG
     * @param array $peerforumconfig The configuration for this peerforum
     * @param array $postconfig The configuration for this post
     * @param array $discussionconfig The configuration for this discussion
     * @param string $expectation The expected date
     */
    public function test_postdate($globalconfig, $peerforumconfig, $postconfig, $discussionconfig, $expectation) {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        // Apply the global configuration.
        foreach ($globalconfig as $key => $value) {
            $CFG->$key = $value;
        }

        // Create the fixture.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $peerforum = $this->getDataGenerator()->create_module('peerforum', (object) array('course' => $course->id));
        $cm = get_coursemodule_from_instance('peerforum', $peerforum->id, $course->id, false, MUST_EXIST);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a new discussion.
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_peerforum')->create_discussion(
                (object) array_merge($discussionconfig, array(
                        'course' => $course->id,
                        'peerforum' => $peerforum->id,
                        'userid' => $user->id,
                )));

        // Apply the discussion configuration.
        // Some settings are ignored by the generator and must be set manually.
        $discussion = $DB->get_record('peerforum_discussions', array('id' => $discussion->id));
        foreach ($discussionconfig as $key => $value) {
            $discussion->$key = $value;
        }
        $DB->update_record('peerforum_discussions', $discussion);

        // Apply the post configuration.
        // Some settings are ignored by the generator and must be set manually.
        $post = $DB->get_record('peerforum_posts', array('discussion' => $discussion->id));
        foreach ($postconfig as $key => $value) {
            $post->$key = $value;
        }
        $DB->update_record('peerforum_posts', $post);

        // Create the renderable.
        $renderable = new mod_peerforum\output\peerforum_post_email(
                $course,
                $cm,
                $peerforum,
                $discussion,
                $post,
                $user,
                $user,
                true
        );

        // Check the postdate matches our expectations.
        $this->assertEquals(userdate($expectation, "", \core_date::get_user_timezone($user)), $renderable->get_postdate());
    }
}
