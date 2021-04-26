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
 * PeerForum class.
 *
 * @package    mod_peerforum
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_peerforum\local\entities;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/peerforum/lib.php');
require_once($CFG->dirroot . '/rating/lib.php');

use mod_peerforum\local\entities\discussion as discussion_entity;
use context;
use stdClass;

/**
 * PeerForum class.
 *
 * @copyright  2019 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class peerforum {
    /** @var context $context The peerforum module context */
    private $context;
    /** @var stdClass $coursemodule The peerforum course module record */
    private $coursemodule;
    /** @var stdClass $course The peerforum course record */
    private $course;
    /** @var int $effectivegroupmode The effective group mode */
    private $effectivegroupmode;
    /** @var int $id ID */
    private $id;
    /** @var int $courseid Id of the course this peerforum is in */
    private $courseid;
    /** @var string $type The peerforum type, e.g. single, qanda, etc */
    private $type;
    /** @var string $name Name of the peerforum */
    private $name;
    /** @var string $intro Intro text */
    private $intro;
    /** @var int $introformat Format of the intro text */
    private $introformat;
    /** @var int $assessed The peerforum rating aggregate */
    private $assessed;
    /** @var int $assesstimestart Timestamp to begin assessment */
    private $assesstimestart;
    /** @var int $assesstimefinish Timestamp to end assessment */
    private $assesstimefinish;
    /** @var int $scale The rating scale */
    private $scale;
    /** @var int $gradepeerforum The grade for the peerforum when grading holistically */
    private $gradepeerforum;
    /** @var bool $gradepeerforumnotify Whether to notify students when the peerforum is graded holistically */
    private $gradepeerforumnotify;
    /** @var int $maxbytes Maximum attachment size */
    private $maxbytes;
    /** @var int $maxattachments Maximum number of attachments */
    private $maxattachments;
    /** @var int $forcesubscribe Does the peerforum force users to subscribe? */
    private $forcesubscribe;
    /** @var int $trackingtype Tracking type */
    private $trackingtype;
    /** @var int $rsstype RSS type */
    private $rsstype;
    /** @var int $rssarticles RSS articles */
    private $rssarticles;
    /** @var int $timemodified Timestamp when the peerforum was last modified */
    private $timemodified;
    /** @var int $warnafter Warn after */
    private $warnafter;
    /** @var int $blockafter Block after */
    private $blockafter;
    /** @var int $blockperiod Block period */
    private $blockperiod;
    /** @var int $completiondiscussions Completion discussions */
    private $completiondiscussions;
    /** @var int $completionreplies Completion replies */
    private $completionreplies;
    /** @var int $completionposts Completion posts */
    private $completionposts;
    /** @var bool $displaywordcounts Should display word counts in posts */
    private $displaywordcounts;
    /** @var bool $lockdiscussionafter Timestamp after which discussions should be locked */
    private $lockdiscussionafter;
    /** @var int $duedate Timestamp that represents the due date for peerforum posts */
    private $duedate;
    /** @var int $cutoffdate Timestamp after which peerforum posts will no longer be accepted */
    private $cutoffdate;
    /** @var int $peergradescale peergradescale */
    private $peergradescale;
    /** @var int $peergradeassessed peergradeassessed */
    private $peergradeassessed;
    /** @var int $peergradeassesstimestart peergradeassesstimestart */
    private $peergradeassesstimestart;
    /** @var int $peergradeassesstimefinish peergradeassesstimefinish */
    private $peergradeassesstimefinish;
    /** @var string $peergradesvisibility peergradesvisibility */
    private $peergradesvisibility;
    /** @var int $whenpeergrades whenpeergrades */
    private $whenpeergrades;
    /** @var string $feedbackvisibility feedbackvisibility */
    private $feedbackvisibility;
    /** @var string $whenfeedback whenfeedback */
    private $whenfeedback;
    /** @var int $enablefeedback enablefeedback */
    private $enablefeedback;
    /** @var int $remainanonymous remainanonymous */
    private $remainanonymous;
    /** @var int $selectpeergraders selectpeergraders */
    private $selectpeergraders;
    /** @var int $minpeergraders minpeergraders */
    private $minpeergraders;
    /** @var int $finishpeergrade finishpeergrade */
    private $finishpeergrade;
    /** @var int $timetopeergrade timetopeergrade */
    private $timetopeergrade;
    /** @var int $finalgrademode finalgrademode */
    private $finalgrademode;
    /** @var int $studentpercentage studentpercentage */
    private $studentpercentage;
    /** @var int $professorpercentage professorpercentage */
    private $professorpercentage;
    /** @var int $allowpeergrade allowpeergrade */
    private $allowpeergrade;
    /** @var int $expirepeergrade expirepeergrade */
    private $expirepeergrade;
    /** @var int $gradeprofessorpost gradeprofessorpost */
    private $gradeprofessorpost;
    /** @var int $showpeergrades showpeergrades */
    private $showpeergrades;
    /** @var int $outdetectvalue outdetectvalue */
    private $outdetectvalue;
    /** @var int $blockoutliers blockoutliers */
    private $blockoutliers;
    /** @var int $seeoutliers seeoutliers */
    private $seeoutliers;
    /** @var string $outlierdetection outlierdetection */
    private $outlierdetection;
    /** @var int $warningoutliers warningoutliers */
    private $warningoutliers;
    /** @var int $showafterrating showafterrating */
    private $showafterrating;
    /** @var int $showratings showratings */
    private $showratings;
    /** @var int $showafterpeergrade showafterpeergrade */
    private $showafterpeergrade;
    /** @var int $showpostid showpostid */
    private $showpostid;
    /** @var int $showdetails showdetails */
    private $showdetails;
    /** @var int $autoassignreplies autoassignreplies */
    private $autoassignreplies;
    /** @var int $hidereplies hidereplies */
    private $hidereplies;
    /** @var int $peernominations peernominations */
    private $peernominations;
    /** @var int $peerrankings peerrankings */
    private $peerrankings;
    /** @var int $peernominationsfields peernominationsfields */
    private $peernominationsfields;
    /** @var int $peernominationsaddfields peernominationsaddfields */
    private $peernominationsaddfields;
    /** @var int $randomdistribution random_distribution */
    private $randomdistribution;
    /** @var int $training training */
    private $training;
    /** @var int $threadedgrading threaded_grading */
    private $threadedgrading;
    /** @var int $advpeergrading adv_peergrading */
    private $advpeergrading;

    /**
     * Constructor
     *
     * @param context $context The peerforum module context
     * @param stdClass $coursemodule The peerforum course module record
     * @param stdClass $course The peerforum course record
     * @param int $effectivegroupmode The effective group mode
     * @param int $id ID
     * @param int $courseid Id of the course this peerforum is in
     * @param string $type The peerforum type, e.g. single, qanda, etc
     * @param string $name Name of the peerforum
     * @param string $intro Intro text
     * @param int $introformat Format of the intro text
     * @param int $assessed The peerforum rating aggregate
     * @param int $assesstimestart Timestamp to begin assessment
     * @param int $assesstimefinish Timestamp to end assessment
     * @param int $scale The rating scale
     * @param int $gradepeerforum The holistic grade
     * @param bool $gradepeerforumnotify Default for whether to notify students when grade holistically
     * @param int $maxbytes Maximum attachment size
     * @param int $maxattachments Maximum number of attachments
     * @param int $forcesubscribe Does the peerforum force users to subscribe?
     * @param int $trackingtype Tracking type
     * @param int $rsstype RSS type
     * @param int $rssarticles RSS articles
     * @param int $timemodified Timestamp when the peerforum was last modified
     * @param int $warnafter Warn after
     * @param int $blockafter Block after
     * @param int $blockperiod Block period
     * @param int $completiondiscussions Completion discussions
     * @param int $completionreplies Completion replies
     * @param int $completionposts Completion posts
     * @param bool $displaywordcount Should display word counts in posts
     * @param int $lockdiscussionafter Timestamp after which discussions should be locked
     * @param int $duedate Timestamp that represents the due date for peerforum posts
     * @param int $cutoffdate Timestamp after which peerforum posts will no longer be accepted
     * @param int $peergradescale
     * @param int $peergradeassessed
     * @param int $peergradeassesstimestart
     * @param int $peergradeassesstimefinish
     * @param string $peergradesvisibility
     * @param int $whenpeergrades
     * @param string $feedbackvisibility
     * @param string $whenfeedback
     * @param int $enablefeedback
     * @param int $remainanonymous
     * @param int $selectpeergraders
     * @param int $minpeergraders
     * @param int $finishpeergrade
     * @param int $timetopeergrade
     * @param int $finalgrademode
     * @param int $studentpercentage
     * @param int $professorpercentage
     * @param int $allowpeergrade
     * @param int $expirepeergrade
     * @param int $gradeprofessorpost
     * @param int $showpeergrades
     * @param int $outdetectvalue
     * @param int $blockoutliers
     * @param int $seeoutliers
     * @param string $outlierdetection
     * @param int $warningoutliers
     * @param int $showafterrating
     * @param int $showratings
     * @param int $showafterpeergrade
     * @param int $showpostid
     * @param int $showdetails
     * @param int $autoassignreplies
     * @param int $hidereplies
     * @param int $peernominations
     * @param int $peerrankings
     * @param int $peernominationsfields
     * @param int $peernominationsaddfields
     * @param int $randomdistribution
     * @param int $training
     * @param int $threadedgrading
     * @param int $advpeergrading
     */
    public function __construct(
            context $context,
            stdClass $coursemodule,
            stdClass $course,
            int $effectivegroupmode,
            int $id,
            int $courseid,
            string $type,
            string $name,
            string $intro,
            int $introformat,
            int $assessed,
            int $assesstimestart,
            int $assesstimefinish,
            int $scale,
            int $gradepeerforum,
            bool $gradepeerforumnotify,
            int $maxbytes,
            int $maxattachments,
            int $forcesubscribe,
            int $trackingtype,
            int $rsstype,
            int $rssarticles,
            int $timemodified,
            int $warnafter,
            int $blockafter,
            int $blockperiod,
            int $completiondiscussions,
            int $completionreplies,
            int $completionposts,
            bool $displaywordcount,
            int $lockdiscussionafter,
            int $duedate,
            int $cutoffdate,
            int $peergradescale,
            int $peergradeassessed,
            int $peergradeassesstimestart,
            int $peergradeassesstimefinish,
            string $peergradesvisibility,
            int $whenpeergrades,
            string $feedbackvisibility,
            string $whenfeedback,
            int $enablefeedback,
            int $remainanonymous,
            int $selectpeergraders,
            int $minpeergraders,
            int $finishpeergrade,
            int $timetopeergrade,
            int $finalgrademode,
            int $studentpercentage,
            int $professorpercentage,
            int $allowpeergrade,
            int $expirepeergrade,
            int $gradeprofessorpost,
            int $showpeergrades,
            int $outdetectvalue,
            int $blockoutliers,
            int $seeoutliers,
            string $outlierdetection,
            int $warningoutliers,
            int $showafterrating,
            int $showratings,
            int $showafterpeergrade,
            int $showpostid,
            int $showdetails,
            int $autoassignreplies,
            int $hidereplies,
            int $peernominations,
            int $peerrankings,
            int $peernominationsfields,
            int $peernominationsaddfields,
            int $randomdistribution,
            int $training,
            int $threadedgrading,
            int $advpeergrading
    ) {
        $this->context = $context;
        $this->coursemodule = $coursemodule;
        $this->course = $course;
        $this->effectivegroupmode = $effectivegroupmode;
        $this->id = $id;
        $this->courseid = $courseid;
        $this->type = $type;
        $this->name = $name;
        $this->intro = $intro;
        $this->introformat = $introformat;
        $this->assessed = $assessed;
        $this->assesstimestart = $assesstimestart;
        $this->assesstimefinish = $assesstimefinish;
        $this->scale = $scale;
        $this->gradepeerforum = $gradepeerforum;
        $this->gradepeerforumnotify = $gradepeerforumnotify;
        $this->maxbytes = $maxbytes;
        $this->maxattachments = $maxattachments;
        $this->forcesubscribe = $forcesubscribe;
        $this->trackingtype = $trackingtype;
        $this->rsstype = $rsstype;
        $this->rssarticles = $rssarticles;
        $this->timemodified = $timemodified;
        $this->warnafter = $warnafter;
        $this->blockafter = $blockafter;
        $this->blockperiod = $blockperiod;
        $this->completiondiscussions = $completiondiscussions;
        $this->completionreplies = $completionreplies;
        $this->completionposts = $completionposts;
        $this->displaywordcount = $displaywordcount;
        $this->lockdiscussionafter = $lockdiscussionafter;
        $this->duedate = $duedate;
        $this->cutoffdate = $cutoffdate;
        $this->peergradescale = $peergradescale;
        $this->peergradeassessed = $peergradeassessed;
        $this->peergradeassesstimestart = $peergradeassesstimestart;
        $this->peergradeassesstimefinish = $peergradeassesstimefinish;
        $this->peergradesvisibility = $peergradesvisibility;
        $this->whenpeergrades = $whenpeergrades;
        $this->feedbackvisibility = $feedbackvisibility;
        $this->whenfeedback = $whenfeedback;
        $this->enablefeedback = $enablefeedback;
        $this->remainanonymous = $remainanonymous;
        $this->selectpeergraders = $selectpeergraders;
        $this->minpeergraders = $minpeergraders;
        $this->finishpeergrade = $finishpeergrade;
        $this->timetopeergrade = $timetopeergrade;
        $this->finalgrademode = $finalgrademode;
        $this->studentpercentage = $studentpercentage;
        $this->professorpercentage = $professorpercentage;
        $this->allowpeergrade = $allowpeergrade;
        $this->expirepeergrade = $expirepeergrade;
        $this->gradeprofessorpost = $gradeprofessorpost;
        $this->showpeergrades = $showpeergrades;
        $this->outdetectvalue = $outdetectvalue;
        $this->blockoutliers = $blockoutliers;
        $this->seeoutliers = $seeoutliers;
        $this->outlierdetection = $outlierdetection;
        $this->warningoutliers = $warningoutliers;
        $this->showafterrating = $showafterrating;
        $this->showratings = $showratings;
        $this->showafterpeergrade = $showafterpeergrade;
        $this->showpostid = $showpostid;
        $this->showdetails = $showdetails;
        $this->autoassignreplies = $autoassignreplies;
        $this->hidereplies = $hidereplies;
        $this->peernominations = $peernominations;
        $this->peerrankings = $peerrankings;
        $this->peernominationsfields = $peernominationsfields;
        $this->peernominationsaddfields = $peernominationsaddfields;
        $this->randomdistribution = $randomdistribution;
        $this->training = $training;
        $this->threadedgrading = $threadedgrading;
        $this->advpeergrading = $advpeergrading;
    }

    /**
     * Get the peerforum module context.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Get the peerforum course module record
     *
     * @return stdClass
     */
    public function get_course_module_record(): stdClass {
        return $this->coursemodule;
    }

    /**
     * Get the effective group mode.
     *
     * @return int
     */
    public function get_effective_group_mode(): int {
        return $this->effectivegroupmode;
    }

    /**
     * Check if the peerforum is set to group mode.
     *
     * @return bool
     */
    public function is_in_group_mode(): bool {
        return $this->get_effective_group_mode() !== NOGROUPS;
    }

    /**
     * Get the course record.
     *
     * @return stdClass
     */
    public function get_course_record(): stdClass {
        return $this->course;
    }

    /**
     * Get the peerforum id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get the id of the course that the peerforum belongs to.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->courseid;
    }

    /**
     * Get the peerforum type.
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get the peerforum name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get the peerforum intro text.
     *
     * @return string
     */
    public function get_intro(): string {
        return $this->intro;
    }

    /**
     * Get the peerforum intro text format.
     *
     * @return int
     */
    public function get_intro_format(): int {
        return $this->introformat;
    }

    /**
     * Get the rating aggregate.
     *
     * @return int
     */
    public function get_rating_aggregate(): int {
        return $this->assessed;
    }

    /**
     * Does the peerforum have a rating aggregate?
     *
     * @return bool
     */
    public function has_rating_aggregate(): bool {
        return $this->get_rating_aggregate() != RATING_AGGREGATE_NONE;
    }

    /**
     * Get the timestamp for when the assessment period begins.
     *
     * @return int
     */
    public function get_assess_time_start(): int {
        return $this->assesstimestart;
    }

    /**
     * Get the timestamp for when the assessment period ends.
     *
     * @return int
     */
    public function get_assess_time_finish(): int {
        return $this->assesstimefinish;
    }

    /**
     * Get the rating scale.
     *
     * @return int
     */
    public function get_scale(): int {
        return $this->scale;
    }

    /**
     * Get the grade for the peerforum when grading holistically.
     *
     * @return int
     */
    public function get_grade_for_peerforum(): int {
        return $this->gradepeerforum;
    }

    /**
     * Whether grading is enabled for this item.
     *
     * @return bool
     */
    public function is_grading_enabled(): bool {
        return $this->get_grade_for_peerforum() !== 0;
    }

    /**
     * Get the default for whether the students should be notified when grading holistically.
     *
     * @return bool
     */
    public function should_notify_students_default_when_grade_for_peerforum(): bool {
        return $this->gradepeerforumnotify;
    }

    /**
     * Get the maximum bytes.
     *
     * @return int
     */
    public function get_max_bytes(): int {
        return $this->maxbytes;
    }

    /**
     * Get the maximum number of attachments.
     *
     * @return int
     */
    public function get_max_attachments(): int {
        return $this->maxattachments;
    }

    /**
     * Get the subscription mode.
     *
     * @return int
     */
    public function get_subscription_mode(): int {
        return $this->forcesubscribe;
    }

    /**
     * Is the subscription mode set to optional.
     *
     * @return bool
     */
    public function is_subscription_optional(): bool {
        return $this->get_subscription_mode() === PEERFORUM_CHOOSESUBSCRIBE;
    }

    /**
     * Is the subscription mode set to forced.
     *
     * @return bool
     */
    public function is_subscription_forced(): bool {
        return $this->get_subscription_mode() === PEERFORUM_FORCESUBSCRIBE;
    }

    /**
     * Is the subscription mode set to automatic.
     *
     * @return bool
     */
    public function is_subscription_automatic(): bool {
        return $this->get_subscription_mode() === PEERFORUM_INITIALSUBSCRIBE;
    }

    /**
     * Is the subscription mode set to disabled.
     *
     * @return bool
     */
    public function is_subscription_disabled(): bool {
        return $this->get_subscription_mode() === PEERFORUM_DISALLOWSUBSCRIBE;
    }

    /**
     * Get the tracking type.
     *
     * @return int
     */
    public function get_tracking_type(): int {
        return $this->trackingtype;
    }

    /**
     * Get the RSS type.
     *
     * @return int
     */
    public function get_rss_type(): int {
        return $this->rsstype;
    }

    /**
     * Get the RSS articles.
     *
     * @return int
     */
    public function get_rss_articles(): int {
        return $this->rssarticles;
    }

    /**
     * Get the timestamp for when the peerforum was last modified.
     *
     * @return int
     */
    public function get_time_modified(): int {
        return $this->timemodified;
    }

    /**
     * Get warn after.
     *
     * @return int
     */
    public function get_warn_after(): int {
        return $this->warnafter;
    }

    /**
     * Get block after.
     *
     * @return int
     */
    public function get_block_after(): int {
        return $this->blockafter;
    }

    /**
     * Get the block period.
     *
     * @return int
     */
    public function get_block_period(): int {
        return $this->blockperiod;
    }

    /**
     * Does the peerforum have blocking enabled?
     *
     * @return bool
     */
    public function has_blocking_enabled(): bool {
        return !empty($this->get_block_after()) && !empty($this->get_block_period());
    }

    /**
     * Get the completion discussions.
     *
     * @return int
     */
    public function get_completion_discussions(): int {
        return $this->completiondiscussions;
    }

    /**
     * Get the completion replies.
     *
     * @return int
     */
    public function get_completion_replies(): int {
        return $this->completionreplies;
    }

    /**
     * Get the completion posts.
     *
     * @return int
     */
    public function get_completion_posts(): int {
        return $this->completionposts;
    }

    /**
     * Should the word counts be shown in the posts?
     *
     * @return bool
     */
    public function should_display_word_count(): bool {
        return $this->displaywordcount;
    }

    /**
     * Get the timestamp after which the discussion should be locked.
     *
     * @return int
     */
    public function get_lock_discussions_after(): int {
        return $this->lockdiscussionafter;
    }

    /**
     * Does the peerforum have a discussion locking timestamp?
     *
     * @return bool
     */
    public function has_lock_discussions_after(): bool {
        return !empty($this->get_lock_discussions_after());
    }

    /**
     * Check whether the discussion is locked based on peerforum's time based locking criteria
     *
     * @param discussion_entity $discussion
     * @return bool
     */
    public function is_discussion_time_locked(discussion_entity $discussion): bool {
        if (!$this->has_lock_discussions_after()) {
            return false;
        }

        if ($this->get_type() === 'single') {
            // It does not make sense to lock a single discussion peerforum.
            return false;
        }

        return (($discussion->get_time_modified() + $this->get_lock_discussions_after()) < time());
    }

    /**
     * Get the cutoff date.
     *
     * @return int
     */
    public function get_cutoff_date(): int {
        return $this->cutoffdate;
    }

    /**
     * Does the peerforum have a cutoff date?
     *
     * @return bool
     */
    public function has_cutoff_date(): bool {
        return !empty($this->get_cutoff_date());
    }

    /**
     * Is the cutoff date for the peerforum reached?
     *
     * @return bool
     */
    public function is_cutoff_date_reached(): bool {
        if ($this->has_cutoff_date() && ($this->get_cutoff_date() < time())) {
            return true;
        }

        return false;
    }

    /**
     * Get the due date.
     *
     * @return int
     */
    public function get_due_date(): int {
        return $this->duedate;
    }

    /**
     * Does the peerforum have a due date?
     *
     * @return bool
     */
    public function has_due_date(): bool {
        return !empty($this->get_due_date());
    }

    /**
     * Is the due date for the peerforum reached?
     *
     * @return bool
     */
    public function is_due_date_reached(): bool {
        if ($this->has_due_date() && ($this->get_due_date() < time())) {
            return true;
        }

        return false;
    }

    /**
     * Is the discussion locked? - Takes into account both discussion settings AND peerforum's criteria
     *
     * @param discussion_entity $discussion The discussion to check
     * @return bool
     */
    public function is_discussion_locked(discussion_entity $discussion): bool {
        if ($discussion->is_locked()) {
            return true;
        }

        return $this->is_discussion_time_locked($discussion);
    }

    /**
     * @return int
     */
    public function get_peergradescale(): int {
        return $this->peergradescale;
    }

    /**
     * @return int
     */
    public function get_peergrade_aggregate(): int {
        return $this->peergradeassessed;
    }
    /**
     *
     * Does the peerforum have a peergrade aggregate?
     *
     * @return int
     */
    public function has_peergrade_aggregate(): int {
        return $this->get_peergrade_aggregate() != PEERGRADE_AGGREGATE_NONE;
    }

    /**
     * @return int
     */
    public function get_peergradeassesstimestart(): int {
        return $this->peergradeassesstimestart;
    }

    /**
     * @return int
     */
    public function get_peergradeassesstimefinish(): int {
        return $this->peergradeassesstimefinish;
    }

    /**
     * @return string
     */
    public function get_peergradesvisibility(): string {
        return $this->peergradesvisibility;
    }

    /**
     * @return int
     */
    public function get_whenpeergrades(): int {
        return $this->whenpeergrades;
    }

    /**
     * @return string
     */
    public function get_feedbackvisibility(): string {
        return $this->feedbackvisibility;
    }

    /**
     * @return string
     */
    public function get_whenfeedback(): string {
        return $this->whenfeedback;
    }

    /**
     * @return int
     */
    public function is_enablefeedback(): int {
        return $this->enablefeedback;
    }

    /**
     * @return int
     */
    public function is_remainanonymous(): int {
        return $this->remainanonymous;
    }

    /**
     * @return int
     */
    public function get_selectpeergraders(): int {
        return $this->selectpeergraders;
    }

    /**
     * @return int
     */
    public function get_minpeergraders(): int {
        return $this->minpeergraders;
    }

    /**
     * @return int
     */
    public function get_finishpeergrade(): int {
        return $this->finishpeergrade;
    }

    /**
     * @return int
     */
    public function get_timetopeergrade(): int {
        return $this->timetopeergrade;
    }

    /**
     * @return int
     */
    public function get_finalgrademode(): int {
        return $this->finalgrademode;
    }

    /**
     * @return int
     */
    public function is_studentpercentage(): int {
        return $this->studentpercentage;
    }

    /**
     * @return int
     */
    public function is_professorpercentage(): int {
        return $this->professorpercentage;
    }

    /**
     * @return int
     */
    public function is_allowpeergrade(): int {
        return $this->allowpeergrade;
    }

    /**
     * @return int
     */
    public function is_expirepeergrade(): int {
        return $this->expirepeergrade;
    }

    /**
     * @return int
     */
    public function is_gradeprofessorpost(): int {
        return $this->gradeprofessorpost;
    }

    /**
     * @return int
     */
    public function is_showpeergrades(): int {
        return $this->showpeergrades;
    }

    /**
     * @return int
     */
    public function get_outdetectvalue(): int {
        return $this->outdetectvalue;
    }

    /**
     * @return int
     */
    public function is_blockoutliers(): int {
        return $this->blockoutliers;
    }

    /**
     * @return int
     */
    public function is_seeoutliers(): int {
        return $this->seeoutliers;
    }

    /**
     * @return string
     */
    public function get_outlierdetection(): string {
        return $this->outlierdetection;
    }

    /**
     * @return int
     */
    public function get_warningoutliers(): int {
        return $this->warningoutliers;
    }

    /**
     * @return int
     */
    public function is_showafterrating(): int {
        return $this->showafterrating;
    }

    /**
     * @return int
     */
    public function is_showratings(): int {
        return $this->showratings;
    }

    /**
     * @return int
     */
    public function is_showafterpeergrade(): int {
        return $this->showafterpeergrade;
    }

    /**
     * @return int
     */
    public function is_showpostid(): int {
        return $this->showpostid;
    }

    /**
     * @return int
     */
    public function is_showdetails(): int {
        return $this->showdetails;
    }

    /**
     * @return int
     */
    public function is_autoassignreplies(): int {
        return $this->autoassignreplies;
    }

    /**
     * @return int
     */
    public function is_hidereplies(): int {
        return $this->hidereplies;
    }

    /**
     * @return int
     */
    public function is_peernominations(): int {
        return $this->peernominations;
    }

    /**
     * @return int
     */
    public function is_peerrankings(): int {
        return $this->peerrankings;
    }

    /**
     * @return int
     */
    public function get_peernominationsfields(): int {
        return $this->peernominationsfields;
    }

    /**
     * @return int
     */
    public function is_peernominationsaddfields(): int {
        return $this->peernominationsaddfields;
    }

    /**
     * @return int
     */
    public function is_random_distribution(): int {
        return $this->randomdistribution;
    }

    /**
     * @return int
     */
    public function is_training(): int {
        return $this->training;
    }

    /**
     * @return int
     */
    public function is_threaded_grading(): int {
        return $this->threadedgrading;
    }

    /**
     * @return int
     */
    public function is_adv_peergrading(): int {
        return $this->advpeergrading;
    }

    /**
     * Generate an array of peergrade options for get_peergrades().
     * Intentionaly, no field item(s) or userid
     *
     * @return array
     */
    public function get_peergrade_options(): array {
        return array(
            'context' => $this->get_context(),
            'component' => 'mod_peerforum',
            'peergradearea' => 'post',
            'aggregate' => $this->get_peergrade_aggregate(),
            'peergradescaleid' => $this->get_peergradescale(),
            'assesstimestart' => $this->get_peergradeassesstimestart(),
            'assesstimefinish' => $this->get_peergradeassesstimefinish(),
            'timetoexpire' => $this->get_timetopeergrade(),
            'finishpeergrade' => $this->get_finishpeergrade(),
            'enablefeedback' => $this->is_enablefeedback(),
            'showpeergrades' => $this->is_showpeergrades(),
            'minpeergraders' => $this->get_minpeergraders(),
            'peergradevisibility' => $this->get_peergradesvisibility(),
            'expirepost' => $this->is_expirepeergrade(),
            'whenpeergradevisible' => $this->get_whenpeergrades(),
            'remainanonymous' => $this->is_remainanonymous(),
            'maxpeergraders' => $this->get_selectpeergraders(),
            'finalgrademode' => $this->get_finalgrademode(),
            'gradeprofessorpost' => $this->is_gradeprofessorpost(),
            'autoassignreplies' => $this->is_autoassignreplies(),
            'seeoutliers' => $this->is_seeoutliers(),
            'outlierdetection' => $this->get_outlierdetection(),
            'outdetectvalue' => $this->get_outdetectvalue(),
            'warningoutliers' => $this->get_warningoutliers(),
            'blockoutliers' => $this->is_blockoutliers(),
        );
    }
}
