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
 * This file contains a custom renderer class used by the peerforum module.
 *
 * @package   mod_peerforum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom renderer classes (render_peergrade and render_rating) that extends the plugin_renderer_base and
 * is used by the peerforum module.
 *
 * @package   mod_peerforum
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
require_once($CFG->dirroot . '/peergrading/peer_ranking.php');

class mod_peerforum_renderer extends plugin_renderer_base {

    //----------- New renders of PeerForum --------------//

    /**
     * Produces the html that represents this rating in the UI
     *
     * @param rating $rating the page object on which this rating will appear
     * @return string
     */
    public function render_rating(rating $rating) {
        // Disclaimer: this is an adaptation of the render_rating() from moodle outputrenderer.php ...
        global $CFG, $USER, $PAGE, $DB, $COURSE;
        // TODO Tirar $PAGE e chamadas à $DB daqui. Só usar o obj que nos dão (CFG e USEER tambem pode)

        if ($rating->settings->aggregationmethod == RATING_AGGREGATE_NONE) {
            return null;//ratings are turned off
        }

        $ratingmanager = new rating_manager();
        // Initialise the JavaScript so ratings can be done by AJAX.
        $ratingmanager->initialise_rating_javascript($PAGE);

        $strrate = get_string("rate", "peerforum");
        $ratinghtml = ''; //the string we'll return

        // variables
        $post_topeergrade = $rating->itemid;
        $peerforum_id = $rating->peerforum;
        $user_login = $USER->id;

        // Get info from database
        $peerforum = $DB->get_record('peerforum', array('id' => $peerforum_id));

        //Check if POST AUTHOR is student
        $post_author = $DB->get_record('peerforum_posts', array('id' => $post_topeergrade))->userid;
        $cContext = context_course::instance($COURSE->id);
        //check if postAuthor is student
        $isStudent = current(get_user_roles($cContext, $post_author))->shortname == 'student' ? true : false;
        if ($isStudent != 1) {
            return null;
        }

        if (has_capability('mod/peerforum:viewallratings', $PAGE->context)) {
            $isstudent = false;
        } else {
            $isstudent = true;
        }

        //Verify if peer grade can only be shown after rating is done
        $showafterpeergrade = $peerforum->showafterpeergrade;

        //If showing ratings is restricted...
        //Case 1 - Verify if post has expired for all assigned peergraders
        if ($showafterpeergrade) {

            //Some variables
            $posthasexpired = false;
            $expiredposts = 0;

            //Gather info
            $post_info = $DB->get_record("peerforum_posts", array('id' => $post_topeergrade));
            $peergraders = explode(";", $post_info->peergraders);
            $peergrades = $DB->get_records('peerforum_peergrade', array('itemid' => $post_topeergrade));

            if (!empty($peergraders)) {
                for ($i = 0; $i < count($peergraders); $i++) {
                    $post_time = verify_post_expired($post_topeergrade, $peerforum, $peergraders[$i], $COURSE->id);
                    if ($post_time->post_expired) {
                        $expiredposts++;
                    }
                }
                if ($expiredposts == count($peergraders)) {
                    $posthasexpired = true;
                }
            }
        }

        //Case 2 - Verify if mininum number of peergraders have already graded this post
        if ($showafterpeergrade) {
            $canseerating = end_peergrade_post($post_topeergrade, $peerforum);
        } else {
            $canseerating = true;
        }

        // permissions check - can they view the aggregate?
        if ($isstudent && $peerforum->showratings && ($canseerating || $posthasexpired) || !$isstudent) {
        // if ($rating->user_can_view_aggregate()) { SHOULD BE TODO

            $aggregatelabel = $ratingmanager->get_aggregate_label($rating->settings->aggregationmethod);
            $aggregatelabel = html_writer::tag('span', $aggregatelabel, array('class' => 'rating-aggregate-label'));
            $aggregatestr = $rating->get_aggregate_string();

            $aggregatehtml = html_writer::tag('span', $aggregatestr,
                            array('id' => 'ratingaggregate' . $rating->itemid, 'class' => 'ratingaggregate')) . ' ';
            if ($rating->count > 0) {
                $countstr = "({$rating->count})";
            } else {
                $countstr = '-';
            }
            $aggregatehtml .= html_writer::tag('span', $countstr,
                            array('id' => "ratingcount{$rating->itemid}", 'class' => 'ratingcount')) . ' ';

            if ($rating->settings->permissions->viewall && $rating->settings->pluginpermissions->viewall) {

                $nonpopuplink = $rating->get_view_ratings_url();
                $popuplink = $rating->get_view_ratings_url(true);

                $action = new popup_action('click', $popuplink, 'ratings', array('height' => 400, 'width' => 600));
                $aggregatehtml = $this->action_link($nonpopuplink, $aggregatehtml, $action);
            }

            $ratinghtml .= html_writer::tag('span', $aggregatelabel . $aggregatehtml, array('class' => 'rating-aggregate-container'));
        }

        $formstart = null;
        // if the item doesn't belong to the current user, the user has permission to rate
        // and we're within the assessable period
        if ($rating->user_can_rate()) {

            $rateurl = $rating->get_rate_url();
            $inputs = $rateurl->params();

            //start the rating form
            $formattrs = array(
                    'id' => "postrating{$rating->itemid}",
                    'class' => 'postratingform',
                    'method' => 'post',
                    'action' => $rateurl->out_omit_querystring()
            );
            $formstart = html_writer::start_tag('form', $formattrs);
            $formstart .= html_writer::start_tag('div', array('class' => 'ratingform'));

            // add the hidden inputs
            foreach ($inputs as $name => $value) {
                $attributes = array('type' => 'hidden', 'class' => 'ratinginput', 'name' => $name, 'value' => $value);
                $formstart .= html_writer::empty_tag('input', $attributes);
            }

            if (empty($ratinghtml)) {
                $ratinghtml .= $strrate . ': ';
            }
            $ratinghtml = $formstart . $ratinghtml;

            $scalearray = array(RATING_UNSET_RATING => $strrate . '...') + $rating->settings->scale->scaleitems;
            $scaleattrs = array('class' => 'postratingmenu ratinginput', 'id' => 'menurating' . $rating->itemid);
            $ratinghtml .= html_writer::label($rating->rating, 'menurating' . $rating->itemid, false,
                    array('class' => 'accesshide'));
            $ratinghtml .= html_writer::select($scalearray, 'rating', $rating->rating, false, $scaleattrs);

            //output submit button
            $ratinghtml .= html_writer::start_tag('span', array('class' => "ratingsubmit"));

            $attributes = array('type' => 'submit', 'class' => 'postratingmenusubmit',
                    'id' => 'postratingsubmit' . $rating->itemid, 'value' => s(get_string('rate', 'peerforum')));
            $ratinghtml .= html_writer::empty_tag('input', $attributes);

            if (!$rating->settings->scale->isnumeric) {
                // If a global scale, try to find current course ID from the context
                if (empty($rating->settings->scale->courseid) and
                        $coursecontext = $rating->context->get_course_context(false)) {
                    $courseid = $coursecontext->instanceid;
                } else {
                    $courseid = $rating->settings->scale->courseid;
                }
                $ratinghtml .= $this->help_icon_scale($courseid, $rating->settings->scale);
            }
            $ratinghtml .= html_writer::end_tag('span');
            $ratinghtml .= html_writer::end_tag('div');
            $ratinghtml .= html_writer::end_tag('form');
        }

        return $ratinghtml;
    }

    /**
     * Produces the html that represents this peergrade in the UI
     *
     * @param peergrade $peergrade the page object on which this rating will appear
     * @return string
     */
    public function render_peergrade(peergrade $peergrade) {
        global $CFG;

        if ($peergrade->settings->aggregationmethod == PEERGRADE_AGGREGATE_NONE) {
            return null; // Peergrades are turned off.
        }
        $edititemid = optional_param('id', 0, PARAM_INT);

        $peergrademanager = new peergrade_manager();
        // Initialise the JavaScript so peergrades can be done by AJAX.
        $peergrademanager->initialise_peergrade_javascript($this->page);

        $peergradeoptions = new stdClass;
        $peergradeoptions->context = $peergrade->context;
        $peergradeoptions->component = $peergrade->component;
        $peergradeoptions->peergradearea = $peergrade->peergradearea;
        $peergradeoptions->itemid = $peergrade->itemid;
        $allpeergrades = $peergrademanager->get_all_peergrades_for_item($peergradeoptions);

        $vaultfactory = \mod_peerforum\local\container::get_vault_factory();
        $urlfactory = \mod_peerforum\local\container::get_url_factory();
        $entityfactory = \mod_peerforum\local\container::get_entity_factory();
        $peerforumentity = $vaultfactory->get_peerforum_vault()->get_from_post_id($peergrade->itemid);

        $strpeergrade = get_string("peergrade", "peerforum");
        $peergradehtml = ''; // The string we'll return.

        if (!$peergrade->exists()) {
            return $peergradehtml;
        }

        /*------------------- SHOW AGGREGATE -----------------------------------*/

        // Permissions check - can they view the aggregate?
        if ($peergrade->user_can_view_aggregate()) {

            $aggregatelabel = $peergrademanager->get_aggregate_label($peergrade->settings->aggregationmethod);
            $aggregatelabel = html_writer::tag('span', $aggregatelabel, array('class' => 'peergrade-aggregate-label'));
            $aggregatestr = $peergrade->get_aggregate_string();

            $aggregatehtml = html_writer::tag('span', $aggregatestr,
                array('id' => 'peergradeaggregate' . $peergrade->itemid, 'class' => 'peergradeaggregate')) . ' ';
            if ($peergrade->count > 0) {
                $countstr = "({$peergrade->count})";
            } else {
                $countstr = '-';
            }
            $aggregatehtml .= html_writer::tag('span', $countstr,
                            array('id' => "peergradecount{$peergrade->itemid}", 'class' => 'peergradecount')).' ';

            if ($peergrade->user_can_view_peergrades()) {

                $nonpopuplink = $peergrade->get_view_peergrades_url();
                $popuplink = $peergrade->get_view_peergrades_url(true);

                $action = new popup_action('click', $popuplink, 'peergrades', array('height' => 400, 'width' => 600));
                $aggregatehtml = $this->output->action_link($nonpopuplink, $aggregatehtml, $action);
            }

            $peergradehtml .= html_writer::tag('span', $aggregatelabel . $aggregatehtml,
                    array('class' => 'peergrade-aggregate-container'));
        } else if ($peergrade->can_peergrades_be_shown()) {
            $peergradehtml .= html_writer::tag('p', "There is a peer grading activity in progress.",
                    array('style' => 'color: #6699ff;'));
        }

        /*------------------- GIVE PEERGRADE -----------------------------------*/
        $formstart = null;
        // If the item doesn't belong to the current user, the user has permission to peergrade
        // and we're within the assessable period.
        if ($peergrade->user_can_peergrade()) {

            $peergradeurl = $peergrade->get_peergrade_url();
            $inputs = $peergradeurl->params();

            // Start the peergrade form.
            $formattrs = array(
                    'id' => "postpeergrade{$peergrade->itemid}",
                    'class' => 'postpeergradeform',
                    'method' => 'post',
                    'action' => $peergradeurl->out_omit_querystring()
            );
            $formstart  = html_writer::start_tag('form', $formattrs);
            $formstart .= html_writer::start_tag('div', array('class' => 'peergradeform'));

            // Add the hidden inputs.
            foreach ($inputs as $name => $value) {
                $attributes =
                        array('type' => 'hidden', 'class' => 'peergradeinput', 'name' => $name, 'value' => $value);
                $formstart .= html_writer::empty_tag('input', $attributes);
            }

            if (empty($peergradehtml)) {
                $peergradehtml .= $strpeergrade . ': ';
            }
            $peergradehtml = $formstart . $peergradehtml;

            $scalearray = array(PEERGRADE_UNSET_PEERGRADE => $strpeergrade . '...') +
                    $peergrade->settings->peergradescale->peergradescaleitems;
            $scaleattrs = array('class' => 'postpeergrademenu peergradeinput', 'id' => 'menupeergrade' . $peergrade->itemid);
            $peergradehtml .= html_writer::label($peergrade->peergrade, 'menupeergrade' . $peergrade->itemid,
                    false, array('class' => 'accesshide'));

            // Time left to peergrade.
            $timeleft = $peergrade->get_time_to_expire();
            $peergradehtml .= html_writer::empty_tag('br');
            $peergradehtml .= html_writer::tag('span', 'Time left to peergrade: ' . $timeleft,
                    array('style' => 'color: #ff6666;')); // Or color: #6699ff; .
            $peergradehtml .= html_writer::tag('hr', '',
                    array('style' => 'height: 2px; width: 98%; background-color: #e3e3e3;'));

            $peergradehtml .= html_writer::tag('span', 'Select a grade: ',
                    array('style' => 'color: black;')); // Or color: #6699ff; .
            $peergradehtml .= html_writer::select($scalearray, 'peergrade', $peergrade->peergrade, false, $scaleattrs);

            // Output the text feedback.
            if ($peergrade->settings->enablefeedback) {
                $attributes = array('name' => 'feedbacktext' . $peergrade->itemid,
                        'form' => "postpeergrade{$peergrade->itemid}",
                        'class' => 'feedbacktext', 'id' => 'feedbacktext' . $peergrade->itemid,
                        'wrap' => 'virtual', 'style' => 'height: 100%; width: 98%; max-width: 98%;',
                        'rows' => '5', 'cols' => '5',
                        'placeholder' => get_string('writefeedback', 'peerforum'));

                    $peergradehtml .= html_writer::tag('textarea', $peergrade->feedback, $attributes);
            }

            // Feedback autor.
            $anonymouspeergrader = $peergrade->settings->remainanonymous;
            $grader = $anonymouspeergrader ? '[Your peergrade to this post is anonymous]' :
                    '[Your peergrade to this post is public]';

            // Output submit button.
            $peergradehtml .= html_writer::start_tag('span', array('class' => "peergradesubmit"));

            $attributes = array('type' => 'submit',
                    'name' => 'postpeergrademenusubmit' . $peergrade->itemid,
                    'class' => 'postpeergrademenusubmit',
                    'id' => 'postpeergradesubmit' . $peergrade->itemid,
                    'value' => s(get_string('peergrade', 'peerforum')));
            $peergradehtml .= html_writer::empty_tag('input', $attributes);

            $peergradehtml .= html_writer::tag('div', $grader, array('class' => 'author')); // Author.

            if (!$peergrade->settings->peergradescale->isnumeric) {
                // If a global scale, try to find current course ID from the context.
                if (empty($peergrade->settings->peergradescale->courseid) &&
                        $coursecontext = $peergrade->context->get_course_context(false)) {
                    $courseid = $coursecontext->instanceid;
                } else {
                    $courseid = $peergrade->settings->peergradescale->courseid;
                }
                $peergradehtml .= $this->output->help_icon_scale($courseid, $peergrade->settings->peergradescale);
            }
            $peergradehtml .= html_writer::end_tag('span');
            $peergradehtml .= html_writer::end_tag('div');
            $peergradehtml .= html_writer::end_tag('form');
        } else if ($peergrade->is_expired_for_user()) {
            $peergradehtml .= html_writer::tag('p', "Your time to peer grade this post has expired!",
                    array('style' => 'color: #6699ff;'));
        } else if ($peergrade->get_self_assignment()) {
            $peergradehtml .= html_writer::tag('p', "The activity of peer grading this post has ended.",
                    array('style' => 'color: #6699ff;'));
        }

        /*--------------- DISPLAY PEERGRADE ------------- */

        // Permissions check - can they view the peer grades?
        if ($peergrade->user_can_view_peergrades($allpeergrades, false)) {
            $expandhtml = '';
            $pgsmissing = count($allpeergrades);

            foreach ($allpeergrades as $k => $pg) {

                if ($peergrade->user_can_view_peergrades(array($pg))) {
                    $peergradeurl = $peergrade->get_peergrade_url();
                    $inputs = $peergradeurl->params();

                    // Start the peergrade form.
                    $formattrs = array(
                            'id' => "postpeergrade{$peergrade->itemid}",
                            'class' => 'postpeergradeform',
                            'method' => 'post',
                            'action' => $peergradeurl->out_omit_querystring()
                    );

                    /*[FORM - postpeergradeform*/
                    $expandhtml .= html_writer::start_tag('form', $formattrs);

                    // Add the hidden inputs.
                    foreach ($inputs as $name => $value) {
                        $attributes =
                                array('type' => 'hidden', 'class' => 'peergradeinput', 'name' => $name, 'value' => $value);
                        $expandhtml .= html_writer::empty_tag('input', $attributes);
                    }

                    /*[DIV - peergradeform_feedbacks*/
                    $expandhtml .= html_writer::start_tag('div', array('class' => 'peergradeform_feedbacks'));
                    /*[DIV - peerforumpostseefeedback*/
                    $expandhtml .= html_writer::start_tag('div', array('class' => 'peerforumpostseefeedback clearfix',
                            'role' => 'region',
                            'aria-label' => get_string('givefeedback', 'peerforum')));

                    /*[DIV - row header*/
                    $expandhtml .= html_writer::start_tag('div', array('class' => 'row header'));
                    /*[DIV - topic*/
                    $expandhtml .= html_writer::start_tag('div', array('class' => 'topic'));

                    // Feedback author.
                    global $USER;
                    if (!$peergrade->settings->remainanonymous || $pg->userid == $USER->id) {
                        $userfields = user_picture::unalias($pg, ['deleted'], 'userid');
                        $authorentity = $entityfactory->get_author_from_stdclass($userfields);

                        $expandhtml .= $this->output->user_picture($userfields);
                        $profileurl = $urlfactory->get_author_profile_url($authorentity, $peerforumentity->get_course_id());
                        $name = html_writer::link($profileurl, $authorentity->get_full_name());
                    } else {
                        $expandhtml .= $this->output->pix_icon('user', 'user_anonymous', 'mod_peerforum',
                                array('style' => 'width: 32px; height: 32px;', 'class' => 'icon', 'align' => 'left'));

                        $name = 'Grader ' . $k;
                    }
                    $authorshipstring = ['name' => $name, 'date' => userdate($pg->timemodified)];

                    $expandhtml .= html_writer::tag('div', get_string('bynameondate', 'peerforum', $authorshipstring),
                            array('class' => 'author', 'role' => 'heading', 'aria-level' => '2',
                                    'style' => 'position: relative; top: 8px; left: 3px;'));

                    $expandhtml .= html_writer::tag('span', 'Peer grade: ',
                            array('id' => 'outfeedback', 'class' => 'outfeedback', 'style' => 'font-weight: bold'));
                    $expandhtml .= html_writer::tag('span', $pg->peergrade,
                            array('id' => 'outfeedback', 'class' => 'outfeedback'));

                    if ($peergrade->settings->enablefeedback) {
                        $expandhtml .= html_writer::tag('span', 'Feedback: ',
                                array('id' => 'outfeedback', 'class' => 'outfeedback', 'style' => "font-weight:bold"));
                        $expandhtml .= html_writer::tag('span', $pg->feedback,
                                array('id' => 'outfeedback', 'class' => 'outfeedback'));
                    }

                    /*DIV - topic]*/
                    $expandhtml .= html_writer::end_tag('div');
                    /*DIV - row header]*/
                    $expandhtml .= html_writer::end_tag('div'); // Row.

                    // Edit peergrade.
                    if ($peergrade->can_edit()) {
                        $expandhtml .= html_writer::empty_tag('br');
                        $editbutton =
                                array('type' => 'submit', 'name' => 'editpeergrade' . $peergrade->itemid,
                                        'class' => 'editpeergrade',
                                        'id' => 'editpeergrade' . $peergrade->itemid,
                                        'value' => s(get_string('editpeergrade', 'peerforum')));
                        $expandhtml .= html_writer::empty_tag('input', $editbutton);
                    }

                    /*DIV - peerforumpostseefeedback]*/
                    $expandhtml .= html_writer::end_tag('div');
                    /*DIV - peergradeform_feedbacks]*/
                    $expandhtml .= html_writer::end_tag('div');
                    /*FORM - postpeergradeform]*/
                    $expandhtml .= html_writer::end_tag('form');

                    $pgsmissing--;
                }
            }

            if (!empty($expandhtml)) {
                $expandstr = 'Expand all peergrades';
                $peergradehtml .= html_writer::link('#', $expandstr, array('data-action' => 'peergrade-collapsible-link'));

                $peergradehtml .= html_writer::start_tag('div',
                        array('id' => 'peergradefeedbacks' . $peergrade->itemid,
                                'class' => 'peergradefeedbacks',
                                'style' => 'display: none;',
                                'data-content' => 'peergrade-list-content'));
                $peergradehtml .= $expandhtml;

                if ($pgsmissing) {
                    $peergradehtml .= html_writer::tag('p', "There are still some peer grades hidden.",
                            array('style' => 'color: #6699ff;'));
                }

                /*DIV - peergradefeedbacks]*/
                $peergradehtml .= html_writer::end_tag('div');

            } else if (!$peergrade->can_peergrades_be_shown()) {
                $peergradehtml .= html_writer::tag('p', "Peer grades will be shown when peer grading ends.",
                        array('style' => 'color: #6699ff;'));
            } else {
                $peergradehtml .= html_writer::tag('p', "Peer grading for this item is in progress!",
                        array('style' => 'color: #6699ff;'));
            }
        }

        /*--------------- PROFESSOR OPTIONS ------------- */
        if (has_capability('mod/peerforum:professorpeergrade', $peergrade->context)) {
            $expandstr = 'Expand controls';
            $peergradehtml .= html_writer::empty_tag('br');
            $peergradehtml .= html_writer::link('#', $expandstr, array('data-action' => 'peergrade-collapsible-config-link'));

            /*DIV - peergradeconfig*/
            $peergradehtml .= html_writer::start_tag('div', array(
                    'id' => 'peergradeconfig' . $peergrade->itemid,
                    'class' => 'peergradeconfig',
                    'data-content' => 'peergrade-config-content',
                    'style' => 'display: none;'));

            $peergraders = $peergrade->usersassigned;

            if (empty($peergraders)) {
                /*
                //assign parent peers if is not discussion topic
                $parent = $DB->get_record('peerforum_posts', array('id' => $peergrade->itemid))->parent;
                if ($parent != 0) {
                    /*[DIV - nonepeers
                    $peergradehtml .= html_writer::start_tag('div',
                            array('id' => 'nonepeers' . $peergrade->itemid, 'class' => 'nonepeers', 'style' => 'display:block;'));

                    $PAGE->requires->js('/mod/peerforum/assignpeersparent.js');
                    $assignpeersparentstr = get_string('assignpeergradersparent', 'peerforum');
                    $peergradehtml .= $OUTPUT->action_link($CFG->dirroot . '/mod/peerforum/assignpeersparent.php',
                            $assignpeersparentstr, new component_action('click', 'peerforum_assignpeersparent',
                                    array('itemid' => $peergrade->itemid, 'courseid' => $courseid, 'postauthor' => $post_author)),
                            array('id' => 'actionlinkpeers' . $peergrade->itemid));
                    $peergradehtml .= html_writer::end_tag('div');
                    /*DIV - nonepeers]]
                }
                */
            } else {
                $peersnames = array_map(static function ($assign) use ($peergrade) {
                    $name = fullname($assign->userinfo);
                    $color = !empty($assign->peergraded) ? '#339966' : '#cc3300';
                    return html_writer::tag('span', $name, array(
                            'id' => 'peersassigned' . $peergrade->itemid,
                            'style' => 'color: ' . $color . ';'));
                }, $peergraders);
                $peersnames = implode(
                        $peersnames,
                        html_writer::tag('span', '; ', array('style' => 'color: grey;'))
                );

                $peergradehtml .= html_writer::tag('span', "Students assigned to peer grade this post: ",
                        array('style' => 'color: grey;')); // Color: #6699ff;.

                $peergradehtml .= html_writer::tag('span', $peersnames, array('id' => 'peersassigned' . $peergrade->itemid));
            }

            // Show options about assign/remove peers.
            // if ($peerforum->showdetails == 1) { TODO deprecate!

            // TODO replace!
            $courseid = $peerforumentity->get_course_id();
            $possstudents = get_students_can_be_assigned($courseid, $peergrade->itemid, $peergrade->itemuserid);
            $students = array();
            foreach ($possstudents as $key => $value) {
                $id = $possstudents[$key]->id;
                $students[$id] = $id;
            }
            $students = get_students_name($students);

            $peergrademanager->initialise_assignpeer_javascript($this->page);

            // Students assigned to peer grade this post.
            $assignpeerurl = new moodle_url('/mod/peerforum/assignpeer.php',
                    array('itemid' => $peergrade->itemid, 'courseid' => $courseid, 'postauthor' => $peergrade->itemuserid));
            $formattrs = array(
                    'id' => "menuassignpeerform{$peergrade->itemid}",
                    'class' => 'menuassignpeerform',
                    'method' => 'post',
                    'action' => $assignpeerurl->out_omit_querystring()
            );

            /*[FORM - menuassignpeerform*/
            $peergradehtml .= html_writer::start_tag('form', $formattrs);

            // Add the hidden inputs.
            $inputs = $assignpeerurl->params();
            foreach ($inputs as $name => $value) {
                $attributes = array('type' => 'hidden', 'class' => 'studentinput', 'name' => $name, 'value' => $value);
                $peergradehtml .= html_writer::empty_tag('input', $attributes);
            }

            $selectstudentrandom = get_string('selectstudentrandom', 'peerforum');
            $assignstudentstr = get_string('assignstudentstr', 'peerforum');
            $studentsarray =
                    array(UNSET_STUDENT_SELECT => $assignstudentstr, UNSET_STUDENT => $selectstudentrandom) + $students;

            $peergradehtml .= html_writer::tag('span', "Select student to ASSIGN this post to peer grade: ",
                    array('style' => 'color: grey;')); // Color: #6699ff;.

            // Assign peer grader.
            $studentattrs = array('class' => 'menuassignpeer studentinput', 'id' => 'menuassignpeer' . $peergrade->itemid);
            $peergradehtml .= html_writer::select($studentsarray, 'menuassignpeer' . $peergrade->itemid,
                    $studentsarray[UNSET_STUDENT_SELECT], false, $studentattrs);

            /*FORM - menuassignpeerform]*/
            $peergradehtml .= html_writer::end_tag('form');

            // Remove peer grader.
            $peergrademanager->initialise_removepeer_javascript($this->page);

            $studenturlrmv = new moodle_url('/peergrade/removestudent.php');
            $formattrsrmv = array(
                    'id' => "poststudentmenurmv{$peergrade->itemid}",
                    'class' => 'poststudentmenurmv',
                    'method' => 'post',
                    'action' => $studenturlrmv->out_omit_querystring()
            );

            /*[FORM - poststudentmenurmv*/
            $peergradehtml .= html_writer::start_tag('form', $formattrsrmv);

            $peergradeurl = $peergrade->get_peergrade_url();
            $inputsrmv = $peergradeurl->params();

            // Add the hidden inputs.
            foreach ($inputsrmv as $name => $value) {
                $attributesrmv = array('type' => 'hidden', 'class' => 'peergradeinput', 'name' => $name, 'value' => $value);
                $peergradehtml .= html_writer::empty_tag('input', $attributesrmv);
            }

            $studentsassignedrmv = get_assigned_users($peergrade->itemid);

            $studentsrmv = get_students_name($studentsassignedrmv);

            // Remove peer grader.
            $removepeerurl = new moodle_url('/mod/peerforum/removepeer.php',
                    array('itemid' => $peergrade->itemid, 'courseid' => $courseid, 'postauthor' => $peergrade->itemuserid));
            $formattrs = array(
                    'id' => "menuremovepeerform{$peergrade->itemid}",
                    'class' => 'menuremovepeerform',
                    'method' => 'post',
                    'action' => $removepeerurl->out_omit_querystring()
            );

            /*[FORM - menuremovepeerform*/
            $peergradehtml .= html_writer::start_tag('form', $formattrs);
            $inputs = $removepeerurl->params();

            // Add the hidden inputs.
            foreach ($inputs as $name => $value) {
                $attributes = array('type' => 'hidden', 'class' => 'studentinput', 'name' => $name, 'value' => $value);
                $peergradehtml .= html_writer::empty_tag('input', $attributes);
            }

            $removestudentstr = get_string('removestudent', 'peerforum');
            $studentsarrayrmv =
                    array(UNSET_STUDENT_SELECT => $removestudentstr, UNSET_STUDENT => $selectstudentrandom) + $studentsrmv;

            $peergradehtml .= html_writer::tag('span', "Select student to REMOVE this post to peer grade: ",
                    array('style' => 'color: grey;')); // Color: #6699ff;.

            $studentattrsrmv = array('class' => 'menuremovepeer studentinput', 'id' => 'menuremovepeer' . $peergrade->itemid);
            $peergradehtml .= html_writer::select($studentsarrayrmv, 'menuremovepeer' . $peergrade->itemid,
                    $studentsarrayrmv[UNSET_STUDENT], false, $studentattrsrmv);

            /*FORM - menuremovepeerform]*/
            $peergradehtml .= html_writer::end_tag('form');
            /*FORM - poststudentmenurmv]*/
            $peergradehtml .= html_writer::end_tag('form');
            /*DIV - peergradeconfig]*/
            $peergradehtml .= html_writer::end_tag('div');
        }
        return $peergradehtml;
    }

    //------------------------------------------------//

    /**
     * Returns the navigation to the previous and next discussion.
     *
     * @param mixed $prev Previous discussion record, or false.
     * @param mixed $next Next discussion record, or false.
     * @return string The output.
     */
    public function neighbouring_discussion_navigation($prev, $next) {
        $html = '';
        if ($prev || $next) {
            $html .= html_writer::start_tag('div', array('class' => 'discussion-nav clearfix'));
            $html .= html_writer::start_tag('ul');
            if ($prev) {
                $url = new moodle_url('/mod/peerforum/discuss.php', array('d' => $prev->id));
                $html .= html_writer::start_tag('li', array('class' => 'prev-discussion'));
                $html .= html_writer::link($url, $this->output->larrow() . ' ' . format_string($prev->name),
                        array('aria-label' => get_string('prevdiscussiona', 'mod_peerforum', format_string($prev->name)),
                                'class' => 'btn btn-link'));
                $html .= html_writer::end_tag('li');
            }
            if ($next) {
                $url = new moodle_url('/mod/peerforum/discuss.php', array('d' => $next->id));
                $html .= html_writer::start_tag('li', array('class' => 'next-discussion'));
                $html .= html_writer::link($url, format_string($next->name) . ' ' . $this->output->rarrow(),
                        array('aria-label' => get_string('nextdiscussiona', 'mod_peerforum', format_string($next->name)),
                                'class' => 'btn btn-link'));
                $html .= html_writer::end_tag('li');
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_tag('div');
        }
        return $html;
    }

    /**
     * This method is used to generate HTML for a subscriber selection form that
     * uses two user_selector controls
     *
     * @param user_selector_base $existinguc
     * @param user_selector_base $potentialuc
     * @return string
     */
    public function subscriber_selection_form(user_selector_base $existinguc, user_selector_base $potentialuc) {
        $output = '';
        $formattributes = array();
        $formattributes['id'] = 'subscriberform';
        $formattributes['action'] = '';
        $formattributes['method'] = 'post';
        $output .= html_writer::start_tag('form', $formattributes);
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

        $existingcell = new html_table_cell();
        $existingcell->text = $existinguc->display(true);
        $existingcell->attributes['class'] = 'existing';
        $actioncell = new html_table_cell();
        $actioncell->text = html_writer::start_tag('div', array());
        $actioncell->text .= html_writer::empty_tag('input',
                array('type' => 'submit', 'name' => 'subscribe', 'value' => $this->page->theme->larrow . ' ' . get_string('add'),
                        'class' => 'actionbutton'));
        $actioncell->text .= html_writer::empty_tag('br', array());
        $actioncell->text .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'unsubscribe',
                'value' => $this->page->theme->rarrow . ' ' . get_string('remove'), 'class' => 'actionbutton'));
        $actioncell->text .= html_writer::end_tag('div', array());
        $actioncell->attributes['class'] = 'actions';
        $potentialcell = new html_table_cell();
        $potentialcell->text = $potentialuc->display(true);
        $potentialcell->attributes['class'] = 'potential';

        $table = new html_table();
        $table->attributes['class'] = 'subscribertable boxaligncenter';
        $table->data = array(new html_table_row(array($existingcell, $actioncell, $potentialcell)));
        $output .= html_writer::table($table);

        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * This function generates HTML to display a subscriber overview, primarily used on
     * the subscribers page if editing was turned off
     *
     * @param array $users
     * @param object $peerforum
     * @param object $course
     * @return string
     */
    public function subscriber_overview($users, $peerforum, $course) {
        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (!$users || !is_array($users) || count($users) === 0) {
            $output .= $this->output->heading(get_string("nosubscribers", "peerforum"));
        } else if (!isset($modinfo->instances['peerforum'][$peerforum->id])) {
            $output .= $this->output->heading(get_string("invalidmodule", "error"));
        } else {
            $cm = $modinfo->instances['peerforum'][$peerforum->id];
            $canviewemail = in_array('email', get_extra_user_fields(context_module::instance($cm->id)));
            $strparams = new stdclass();
            $strparams->name = format_string($peerforum->name);
            $strparams->count = count($users);
            $output .= $this->output->heading(get_string("subscriberstowithcount", "peerforum", $strparams));
            $table = new html_table();
            $table->cellpadding = 5;
            $table->cellspacing = 5;
            $table->tablealign = 'center';
            $table->data = array();
            foreach ($users as $user) {
                $info = array($this->output->user_picture($user, array('courseid' => $course->id)), fullname($user));
                if ($canviewemail) {
                    array_push($info, $user->email);
                }
                $table->data[] = $info;
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * This is used to display a control containing all of the subscribed users so that
     * it can be searched
     *
     * @param user_selector_base $existingusers
     * @return string
     */
    public function subscribed_users(user_selector_base $existingusers) {
        $output = $this->output->box_start('subscriberdiv boxaligncenter');
        $output .= html_writer::tag('p', get_string('forcesubscribed', 'peerforum'));
        $output .= $existingusers->display(true);
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Generate the HTML for an icon to be displayed beside the subject of a timed discussion.
     *
     * @param object $discussion
     * @param bool $visiblenow Indicicates that the discussion is currently
     * visible to all users.
     * @return string
     */
    public function timed_discussion_tooltip($discussion, $visiblenow) {
        $dates = array();
        if ($discussion->timestart) {
            $dates[] = get_string('displaystart', 'mod_peerforum') . ': ' . userdate($discussion->timestart);
        }
        if ($discussion->timeend) {
            $dates[] = get_string('displayend', 'mod_peerforum') . ': ' . userdate($discussion->timeend);
        }

        $str = $visiblenow ? 'timedvisible' : 'timedhidden';
        $dates[] = get_string($str, 'mod_peerforum');

        $tooltip = implode("\n", $dates);
        return $this->pix_icon('i/calendar', $tooltip, 'moodle', array('class' => 'smallicon timedpost'));
    }

    /**
     * Display a peerforum post in the relevant context.
     *
     * @param \mod_peerforum\output\peerforum_post $post The post to display.
     * @return string
     */
    public function render_peerforum_post_email(\mod_peerforum\output\peerforum_post_email $post) {
        $data = $post->export_for_template($this, $this->target === RENDERER_TARGET_TEXTEMAIL);
        // $data = $post->export_for_template($this); Jessica
        return $this->render_from_template('mod_peerforum/' . $this->peerforum_post_template(), $data);
    }

    /**
     * The template name for this renderer.
     *
     * @return string
     */
    public function peerforum_post_template() {
        return 'peerforum_post';
    }

    /**
     * Create the inplace_editable used to select peerforum digest options.
     *
     * @param stdClass $peerforum The peerforum to create the editable for.
     * @param int $value The current value for this user
     * @return  inplace_editable
     */
    public function render_digest_options($peerforum, $value) {
        $options = peerforum_get_user_digest_options();
        $editable = new \core\output\inplace_editable(
                'mod_peerforum',
                'digestoptions',
                $peerforum->id,
                true,
                $options[$value],
                $value
        );

        $editable->set_type_select($options);

        return $editable;
    }

    /**
     * Render quick search form.
     *
     * @param \mod_peerforum\output\quick_search_form $form The renderable.
     * @return string
     */
    public function render_quick_search_form(\mod_peerforum\output\quick_search_form $form) {
        return $this->render_from_template('mod_peerforum/quick_search_form', $form->export_for_template($this));
    }

    /**
     * Render big search form.
     *
     * @param \mod_peerforum\output\big_search_form $form The renderable.
     * @return string
     */
    public function render_big_search_form(\mod_peerforum\output\big_search_form $form) {
        return $this->render_from_template('mod_peerforum/big_search_form', $form->export_for_template($this));
    }
}
