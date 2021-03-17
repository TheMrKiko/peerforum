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
 * Handle discussion subscription toggling on a discussion list in
 * the peerforum view.
 *
 * @module     block_peerblock/truncate_text
 * @package    block_peerblock
 * @copyright  2019 Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/notification',
    'block_peerblock/repository',
    'block_peerblock/selectors',
], function(
    $,
    Notification,
    Repository,
    Selectors,
) {

    /**
     * Register event listeners for the subscription toggle.
     *
     */
    var registerEventListeners = function() {
        const root = $('.table');
        const tt = root.find(Selectors.short.collapsableText);
        const observer = new ResizeObserver(entries => {
            for (let entry of entries) {
                entry.target.classList[entry.target.scrollHeight > entry.contentRect.height ? 'add' : 'remove']('truncated');
            }
        });

        tt.each((i) => {
            observer.observe(tt.get(i));
        });

        /*root.on('click', Selectors.subscription.toggle, function(e) {
            e.preventDefault();
            var toggleElement = $(this);
            var peerforumId = toggleElement.data('peerforumid');
            var discussionId = toggleElement.data('discussionid');
            var subscriptionState = toggleElement.data('targetstate');

            Repository.setDiscussionSubscriptionState(peerforumId, discussionId, subscriptionState)
                .then()
                .catch(Notification.exception);
        });*/
    };

    return {
        init: registerEventListeners
    };
});
