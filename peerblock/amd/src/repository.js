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
 * PeerForum repository class to encapsulate all of the AJAX requests that subscribe or unsubscribe
 * can be sent for peerforum.
 *
 * @module     block_peerblock/repository
 * @package    block_peerblock
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax'], function(Ajax) {
    /**
     * Get the posts for the discussion ID provided.
     *
     * @param {number} discussionId
     * @param {String} sortby
     * @param {String} sortdirection
     * @return {*|Promise}
     */
    var getDiscussionPosts = function(discussionId, sortby = 'created', sortdirection = 'ASC') {
        var request = {
            methodname: 'block_peerblock_get_discussion_posts',
            args: {
                discussionid: discussionId,
                sortby: sortby,
                sortdirection: sortdirection,
            },
        };
        return Ajax.call([request])[0];
    };

    return {
        getDiscussionPosts: getDiscussionPosts,
    };
});
