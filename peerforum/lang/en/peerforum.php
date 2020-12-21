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
 * Strings for component 'peerforum', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_peerforum
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activityoverview'] = 'There are new peerforum posts';
$string['actionsforgraderinterface'] = 'Actions for the grader interface';
$string['actionsforpost'] = 'Actions for post';
$string['addanewdiscussion'] = 'Add a new discussion topic';
$string['addanewquestion'] = 'Add a new question';
$string['addanewtopic'] = 'Add a new topic';
$string['addtofavourites'] = 'Star this discussion';
$string['advancedsearch'] = 'Advanced search';
$string['alldiscussions'] = 'All discussions';
$string['allpeerforums'] = 'All peerforums';
$string['allowdiscussions'] = 'Can a {$a} post to this peerforum?';
$string['allowsallsubscribe'] = 'This peerforum allows everyone to choose whether to subscribe or not';
$string['allowsdiscussions'] = 'This peerforum allows each person to start one discussion topic.';
$string['allsubscribe'] = 'Subscribe to all peerforums';
$string['allunsubscribe'] = 'Unsubscribe from all peerforums';
$string['allusers'] = 'All users';
$string['alreadyfirstpost'] = 'This is already the first post in the discussion';
$string['anyfile'] = 'Any file';
$string['areaattachment'] = 'Attachments';
$string['areapost'] = 'Messages';
$string['attachment'] = 'Attachment';
$string['attachmentname'] = 'Attachment {$a}';
$string['attachmentnameandfilesize'] = '{$a->name} ({$a->size})';
$string['attachment_help'] =
        'You can optionally attach one or more files to a peerforum post. If you attach an image, it will be displayed after the message.';
$string['attachmentnopost'] = 'You cannot export attachments without a post id';
$string['attachments'] = 'Attachments';
$string['attachmentswordcount'] = 'Attachments and word count';
$string['authorreplyingprivatelytoauthor'] = '{$a->respondant} replying privately to {$a->author}';
$string['authorreplyingtoauthor'] = '{$a->respondant} replying to {$a->author}';
$string['availability'] = 'Availability';
$string['blockafter'] = 'Post threshold for blocking';
$string['blockafter_help'] =
        'This setting specifies the maximum number of posts which a user can post in the given time period. Users with the capability mod/peerforum:postwithoutthrottling are exempt from post limits.';
$string['blockperiod'] = 'Time period for blocking';
$string['blockperiod_help'] =
        'Students can be blocked from posting more than a given number of posts in a given time period. Users with the capability mod/peerforum:postwithoutthrottling are exempt from post limits.';
$string['blockperioddisabled'] = 'Don\'t block';
$string['blogpeerforum'] = 'Standard peerforum displayed in a blog-like format';
$string['bynameondate'] = 'by {$a->name} - {$a->date}';
$string['cachedef_peerforum_is_tracked'] = 'PeerForum tracking status for user';
$string['calendardue'] = '{$a} is due';
$string['cancelreply'] = 'Cancel reply';
$string['cannotadd'] = 'Could not add the discussion for this peerforum';
$string['cannotadddiscussion'] = 'Adding discussions to this peerforum requires group membership.';
$string['cannotadddiscussionall'] = 'You do not have permission to add a new discussion topic for all participants.';
$string['cannotadddiscussiongroup'] = 'You are not able to create a discussion because you are not a member of any group.';
$string['cannotaddsubscriber'] = 'Could not add subscriber with id {$a} to this peerforum!';
$string['cannotaddteacherpeerforumto'] = 'Could not add converted teacher peerforum instance to section 0 in the course';
$string['cannotcreatediscussion'] = 'Could not create new discussion';
$string['cannotcreateinstanceforteacher'] = 'Could not create new course module instance for the teacher peerforum';
$string['cannotdeletepost'] = 'You can\'t delete this post!';
$string['cannoteditposts'] = 'You can\'t edit other people\'s posts!';
$string['cannotexportpeerforum'] = 'You cannot export this peerforum';
$string['cannotfinddiscussion'] = 'Could not find the discussion in this peerforum';
$string['cannotfindfirstpost'] = 'Could not find the first post in this peerforum';
$string['cannotfindorcreatepeerforum'] = 'Could not find or create a main announcements peerforum for the site';
$string['cannotfindparentpost'] = 'Could not find top parent of post {$a}';
$string['cannotmovefromsinglepeerforum'] = 'Cannot move discussion from a simple single discussion peerforum';
$string['cannotmovenotvisible'] = 'PeerForum not visible';
$string['cannotmovetonotexist'] = 'You can\'t move to that peerforum - it doesn\'t exist!';
$string['cannotmovetonotfound'] = 'Target peerforum not found in this course.';
$string['cannotmovetosinglepeerforum'] = 'Cannot move discussion to a simple single discussion peerforum';
$string['cannotpindiscussions'] = 'Sorry, you do not have permission to pin discussions.';
$string['cannotpurgecachedrss'] =
        'Could not purge the cached RSS feeds for the source and/or destination peerforum(s) - check your file permissionspeerforums';
$string['cannotremovesubscriber'] = 'Could not remove subscriber with id {$a} from this peerforum!';
$string['cannotreply'] = 'You cannot reply to this post';
$string['cannotsplit'] = 'Discussions from this peerforum cannot be split';
$string['cannotsubscribe'] = 'Sorry, but you must be a group member to subscribe.';
$string['cannotfavourite'] = 'Sorry, but you do not have the permission to star discussions.';
$string['cannottrack'] = 'Could not stop tracking that peerforum';
$string['cannotunsubscribe'] = 'Could not unsubscribe you from that peerforum';
$string['cannotupdatepost'] = 'You can not update this post';
$string['cannotviewpostyet'] = 'You cannot read other students questions in this discussion yet because you haven\'t posted';
$string['cannotviewusersposts'] = 'There are no posts made by this user that you are able to view.';
$string['cleanreadtime'] = 'Mark old posts as read hour';
$string['clicktolockdiscussion'] = 'Click to lock this discussion';
$string['clicktounlockdiscussion'] = 'Click to unlock this discussion';
$string['clicktounsubscribe'] = 'You are subscribed to this discussion. Click to unsubscribe.';
$string['clicktosubscribe'] = 'You are not subscribed to this discussion. Click to subscribe.';
$string['clicktounfavourite'] = 'You have starred this discussion. Click to unstar.';
$string['clicktofavourite'] = 'You have not starred this discussion. Click to star.';
$string['close'] = 'Close';
$string['closegrader'] = 'Close grader';
$string['completiondiscussions'] = 'Student must create discussions:';
$string['completiondiscussionsdesc'] = 'Student must create at least {$a} discussion(s)';
$string['completiondiscussionsgroup'] = 'Require discussions';
$string['completiondiscussionshelp'] = 'requiring discussions to complete';
$string['completionposts'] = 'Student must post discussions or replies:';
$string['completionpostsdesc'] = 'Student must post at least {$a} discussion(s) or reply/replies';
$string['completionpostsgroup'] = 'Require posts';
$string['completionpostshelp'] = 'requiring discussions or replies to complete';
$string['completionreplies'] = 'Student must post replies:';
$string['completionrepliesdesc'] = 'Student must post at least {$a} reply/replies';
$string['completionrepliesgroup'] = 'Require replies';
$string['completionreplieshelp'] = 'requiring replies to complete';
$string['configcleanreadtime'] = 'The hour of the day to clean old posts from the \'read\' table.';
$string['configdigestmailtime'] =
        'People who choose to have emails sent to them in digest form will be emailed the digest daily. This setting controls which time of day the daily mail will be sent (the next cron that runs after this hour will send it).';
$string['configdisplaymode'] = 'The default display mode for discussions if one isn\'t set.';
$string['configenablerssfeeds'] =
        'This switch will enable the possibility of RSS feeds for all peerforums.  You will still need to turn feeds on manually in the settings for each peerforum.';
$string['configenabletimedposts'] =
        'Set to \'yes\' if you want to allow setting of display periods when posting a new peerforum discussion.';
$string['configlongpost'] =
        'Any post over this length (in characters not including HTML) is considered long. Posts displayed on the site front page, social format course pages, or user profiles are shortened to a natural break somewhere between the peerforum_shortpost and peerforum_longpost values.';
$string['configmanydiscussions'] = 'Maximum number of discussions shown in a peerforum per page';
$string['configmaxattachments'] = 'Default maximum number of attachments allowed per post.';
$string['configmaxbytes'] =
        'Default maximum size for all peerforum attachments on the site (subject to course limits and other local settings)';
$string['configoldpostdays'] = 'Number of days old any post is considered read.';
$string['configreplytouser'] =
        'When a peerforum post is mailed out, should it contain the user\'s email address so that recipients can reply personally rather than via the peerforum? Even if set to \'Yes\' users can choose in their profile to keep their email address secret.';
$string['configrsstypedefault'] = 'If RSS feeds are enabled, sets the default activity type.';
$string['configrssarticlesdefault'] =
        'If RSS feeds are enabled, sets the default number of articles (either discussions or posts).';
$string['configsubscriptiontype'] = 'Default setting for subscription mode.';
$string['configshortpost'] = 'Any post under this length (in characters not including HTML) is considered short (see below).';
$string['configtrackingtype'] = 'Default setting for read tracking.';
$string['configtrackreadposts'] = 'Set to \'yes\' if you want to track read/unread for each user.';
$string['configusermarksread'] =
        'If \'yes\', the user must manually mark a post as read. If \'no\', when the post is viewed it is marked as read.';
$string['confirmsubscribediscussion'] =
        'Do you really want to subscribe to discussion \'{$a->discussion}\' in peerforum \'{$a->peerforum}\'?';
$string['confirmunsubscribediscussion'] =
        'Do you really want to unsubscribe from discussion \'{$a->discussion}\' in peerforum \'{$a->peerforum}\'?';
$string['confirmsubscribe'] = 'Do you really want to subscribe to peerforum \'{$a}\'?';
$string['confirmunsubscribe'] = 'Do you really want to unsubscribe from peerforum \'{$a}\'?';
$string['couldnotadd'] = 'Could not add your post due to an unknown error';
$string['couldnotdeletereplies'] = 'Sorry, that cannot be deleted as people have already responded to it';
$string['couldnotupdate'] = 'Could not update your post due to an unknown error';
$string['created'] = 'Created';
$string['crontask'] = 'PeerForum mailings and maintenance jobs';
$string['cutoffdate'] = 'Cut-off date';
$string['cutoffdate_help'] = 'If set, the peerforum will not accept posts after this date.';
$string['cutoffdatereached'] = 'The cut-off date for posting to this peerforum is reached so you can no longer post to it.';
$string['cutoffdatevalidation'] = 'The cut-off date cannot be earlier than the due date.';
$string['delete'] = 'Delete';
$string['deleteddiscussion'] = 'The discussion topic has been deleted';
$string['deletedpost'] = 'The post has been deleted';
$string['deletedposts'] = 'Those posts have been deleted';
$string['deleteduser'] = 'Deleted user';
$string['deletesure'] = 'Are you sure you want to delete this post?';
$string['deletesureplural'] = 'Are you sure you want to delete this post and all replies? ({$a} posts)';
$string['digestmailheader'] =
        'This is your daily digest of new posts from the {$a->sitename} peerforums. To change your default peerforum email preferences, go to {$a->userprefs}.';
$string['digestmailpost'] = 'Change your peerforum digest preferences';
$string['digestmailpostlink'] = 'Change your peerforum digest preferences: {$a}';
$string['digestmailprefs'] = 'your user profile';
$string['digestmailsubject'] = '{$a}: peerforum digest';
$string['digestmailtime'] = 'Hour to send digest emails';
$string['digestsentusers'] = 'Email digests successfully sent to {$a} users.';
$string['disallowsubscribe'] = 'Subscriptions not allowed';
$string['disallowsubscription'] = 'Subscription';
$string['disallowsubscription_help'] = 'This peerforum has been configured so that you cannot subscribe to discussions.';
$string['disallowsubscribeteacher'] = 'Subscriptions not allowed (except for teachers)';
$string['discussion'] = 'Discussion';
$string['discussionlistsortbycreatedasc'] = 'Sort by creation date in ascending order';
$string['discussionlistsortbycreateddesc'] = 'Sort by creation date in descending order';
$string['discussionlistsortbydiscussionasc'] = 'Sort by discussion name in ascending order';
$string['discussionlistsortbydiscussiondesc'] = 'Sort by discussion name in descending order';
$string['discussionlistsortbygroupasc'] = 'Sort by group in ascending order';
$string['discussionlistsortbygroupdesc'] = 'Sort by group in descending order';
$string['discussionlistsortbylastpostdesc'] = 'Sort by last post creation date in descending order';
$string['discussionlistsortbylastpostasc'] = 'Sort by last post creation date in ascending order';
$string['discussionlistsortbyrepliesasc'] = 'Sort by number of replies in ascending order';
$string['discussionlistsortbyrepliesdesc'] = 'Sort by number of replies in descending order';
$string['discussionlistsortbystarterasc'] = 'Sort by discussion starter name in ascending order';
$string['discussionlistsortbystarterdesc'] = 'Sort by discussion starter name in descending order';
$string['discussionlocked'] = 'This discussion has been locked so you can no longer reply to it.';
$string['discussionlockingheader'] = 'Discussion locking';
$string['discussionlockingdisabled'] = 'Do not lock discussions';
$string['discussionmoved'] = 'This discussion has been moved to \'{$a}\'.';
$string['discussionmovedpost'] =
        'This discussion has been moved to <a href="{$a->discusshref}">here</a> in the peerforum <a href="{$a->peerforumhref}">{$a->peerforumname}</a>';
$string['discussionname'] = 'Discussion name';
$string['discussionnownotsubscribed'] =
        '{$a->name} will NOT be notified of new posts in \'{$a->discussion}\' of \'{$a->peerforum}\'';
$string['discussionnowsubscribed'] = '{$a->name} will be notified of new posts in \'{$a->discussion}\' of \'{$a->peerforum}\'';
$string['discussionpin'] = 'Pin';
$string['discussionpinned'] = 'Pinned';
$string['discussionpinned_help'] = 'Pinned discussions will appear at the top of a peerforum.';
$string['discussionsplit'] = 'Discussion has been split';
$string['discussionsubscribed'] = 'You are now subscribed to this discussion.';
$string['discussionsubscribestop'] = 'I don\'t want to be notified of new posts in this discussion';
$string['discussionsubscribestart'] = 'Send me notifications of new posts in this discussion';
$string['discussionsubscription'] = 'Discussion subscription';
$string['discussionsubscription_help'] =
        'Subscribing to a discussion means you will receive notifications of new posts to that discussion.';
$string['discussions'] = 'Discussions';
$string['discussionsstartedby'] = 'Discussions started by {$a}';
$string['discussionstartedby'] = 'Discussion started by {$a}';
$string['discussionsstartedbyrecent'] = 'Discussions recently started by {$a}';
$string['discussionsstartedbyuserincourse'] = 'Discussions started by {$a->fullname} in {$a->coursename}';
$string['discussionunpin'] = 'Unpin';
$string['discussionunsubscribed'] = 'You are now unsubscribed from this discussion.';
$string['discussthistopic'] = 'Discuss this topic';
$string['discusstopicname'] = 'Discuss the topic: {$a}';
$string['displayend'] = 'Display end';
$string['displayend_help'] =
        'This setting specifies whether a peerforum post should be hidden after a certain date. Note that administrators can always view peerforum posts.';
$string['displayenddate'] = 'Display end: {$a}.';
$string['displaymode'] = 'Display mode';
$string['displayperiod'] = 'Display period';
$string['displaystart'] = 'Display start';
$string['displaystart_help'] =
        'This setting specifies whether a peerforum post should be displayed from a certain date. Note that administrators can always view peerforum posts.';
$string['displaystartdate'] = 'Display start: {$a}.';
$string['displaywordcount'] = 'Display word count';
$string['displaywordcount_help'] = 'This setting specifies whether the word count of each post should be displayed or not.';
$string['duedate'] = 'Due date';
$string['duedate_help'] =
        'This is when posting in the peerforum is due. Although this date is displayed in the calendar as the due date for the peerforum, posting will still be allowed after this date. Set a peerforum cut-off date to prevent posting to the peerforum after a certain date.';
$string['duedatetodisplayincalendar'] = 'Due date to display in calendar';
$string['eachuserpeerforum'] = 'Each person posts one discussion';
$string['edit'] = 'Edit';
$string['editedby'] = 'Edited by {$a->name} - original submission {$a->date}';
$string['editedpostupdated'] = '{$a}\'s post was updated';
$string['editing'] = 'Editing';
$string['eventcoursesearched'] = 'Course searched';
$string['eventdiscussioncreated'] = 'Discussion created';
$string['eventdiscussionupdated'] = 'Discussion updated';
$string['eventdiscussiondeleted'] = 'Discussion deleted';
$string['eventdiscussionmoved'] = 'Discussion moved';
$string['eventdiscussionviewed'] = 'Discussion viewed';
$string['eventdiscussionsubscriptioncreated'] = 'Discussion subscription created';
$string['eventdiscussionsubscriptiondeleted'] = 'Discussion subscription deleted';
$string['eventdiscussionpinned'] = 'Discussion pinned';
$string['eventdiscussionunpinned'] = 'Discussion unpinned';
$string['eventuserreportviewed'] = 'User report viewed';
$string['eventpostcreated'] = 'Post created';
$string['eventpostdeleted'] = 'Post deleted';
$string['eventpostupdated'] = 'Post updated';
$string['eventreadtrackingdisabled'] = 'Read tracking disabled';
$string['eventreadtrackingenabled'] = 'Read tracking enabled';
$string['eventsubscribersviewed'] = 'Subscribers viewed';
$string['eventsubscriptioncreated'] = 'Subscription created';
$string['eventsubscriptiondeleted'] = 'Subscription deleted';
$string['emaildigestcompleteshort'] = 'Complete posts';
$string['emaildigestdefault'] = 'Default ({$a})';
$string['emaildigestoffshort'] = 'No digest';
$string['emaildigestsubjectsshort'] = 'Subjects only';
$string['emaildigesttype'] = 'Email digest options';
$string['emaildigesttype_help'] = 'The type of notification that you will receive for each peerforum.

* Default - follow the digest setting found in your user profile. If you update your profile, then that change will be reflected here too;
* No digest - you will receive one e-mail per peerforum post;
* Digest - complete posts - you will receive one digest e-mail per day containing the complete contents of each peerforum post;
* Digest - subjects only - you will receive one digest e-mail per day containing just the subject of each peerforum post.
';
$string['emptymessage'] =
        'Something was wrong with your post. Perhaps you left it blank, or the attachment was too big. Your changes have NOT been saved.';
$string['erroremptymessage'] = 'Post message cannot be empty';
$string['erroremptysubject'] = 'Post subject cannot be empty.';
$string['errorenrolmentrequired'] = 'You must be enrolled in this course to access this content';
$string['errorwhiledelete'] = 'An error occurred while deleting record.';
$string['errorcannotlock'] = 'You do not have the permission to lock discussions.';
$string['eventassessableuploaded'] = 'Some content has been posted.';
$string['everyonecanchoose'] = 'Everyone can choose to be subscribed';
$string['everyonecannowchoose'] = 'Everyone can now choose to be subscribed';
$string['everyoneisnowsubscribed'] = 'Everyone is now subscribed to this peerforum';
$string['everyoneissubscribed'] = 'Everyone is subscribed to this peerforum';
$string['existingsubscribers'] = 'Existing subscribers';
$string['export'] = 'Export';
$string['exportattachmentname'] = 'Export attachment {$a} to portfolio';
$string['exportdiscussion'] = 'Export whole discussion to portfolio';
$string['exportstriphtml'] = 'Remove HTML';
$string['exportstriphtml_help'] = 'Whether HTML tags such as p and br should be removed from the peerforum post message.';
$string['exportoptions'] = 'Export options';
$string['exporthumandates'] = 'Human-readable dates';
$string['exporthumandates_help'] =
        'Whether dates should be exported in a human-readable format or as a timestamp (sequence of numbers).';
$string['firstpost'] = 'First post';
$string['favourites'] = 'Starred';
$string['favouriteupdated'] = 'Your star option has been updated.';
$string['forcedreadtracking'] = 'Allow forced read tracking';
$string['forcedreadtracking_desc'] =
        'Allows peerforums to be set to forced read tracking. Will result in decreased performance for some users, particularly on courses with many peerforums and posts. When off, any peerforums previously set to Forced are treated as optional.';
$string['forcesubscribed_help'] = 'This peerforum has been configured so that you cannot unsubscribe from discussions.';
$string['forcesubscribed'] = 'This peerforum forces everyone to be subscribed';
$string['peerforum'] = 'PeerForum';
$string['peerforum:addinstance'] = 'Add a new peerforum';
$string['peerforum:addnews'] = 'Add announcements';
$string['peerforum:addquestion'] = 'Add question';
$string['peerforum:allowforcesubscribe'] = 'Allow force subscribe';
$string['peerforum:canoverridecutoff'] = 'Post to peerforums after their cut-off date';
$string['peerforum:canoverridediscussionlock'] = 'Reply to locked discussions';
$string['peerforum:cantogglefavourite'] = 'Star discussions';
$string['peerforum:grade'] = 'Grade peerforum';
$string['peerforumauthorhidden'] = 'Author (hidden)';
$string['peerforumblockingalmosttoomanyposts'] =
        'You are approaching the posting threshold. You have posted {$a->numposts} times in the last {$a->blockperiod} and the limit is {$a->blockafter} posts.';
$string['peerforumbodyhidden'] =
        'This post cannot be viewed by you, probably because you have not posted in the discussion, the maximum editing time hasn\'t passed yet, the discussion has not started or the discussion has expired.';
$string['peerforum:canposttomygroups'] = 'Post to all groups you have access to';
$string['peerforum:createattachment'] = 'Create attachments';
$string['peerforum:deleteanypost'] = 'Delete any posts (anytime)';
$string['peerforum:deleteownpost'] = 'Delete own posts (within deadline)';
$string['peerforum:editanypost'] = 'Edit any post';
$string['peerforum:exportdiscussion'] = 'Export whole discussion';
$string['peerforum:exportpeerforum'] = 'Export peerforum';
$string['peerforum:exportownpost'] = 'Export own post';
$string['peerforum:exportpost'] = 'Export post';
$string['peerforumgradingnavigation'] = 'PeerForum grading navigation';
$string['peerforumgradingpanel'] = 'PeerForum grading panel';
$string['peerforumintro'] = 'Description';
$string['peerforum:managesubscriptions'] = 'Manage subscribers';
$string['peerforum:movediscussions'] = 'Move discussions';
$string['peerforum:pindiscussions'] = 'Pin discussions';
$string['peerforum:postwithoutthrottling'] = 'Exempt from post threshold';
$string['peerforumname'] = 'PeerForum name';
$string['peerforumposts'] = 'PeerForum posts';
$string['peerforum:rate'] = 'Rate posts';
$string['peerforum:replynews'] = 'Reply to announcements';
$string['peerforum:replypost'] = 'Reply to posts';
$string['peerforum:postprivatereply'] = 'Reply privately to posts';
$string['peerforum:readprivatereplies'] = 'View private replies';
$string['peerforums'] = 'PeerForums';
$string['peerforum:splitdiscussions'] = 'Split discussions';
$string['peerforum:startdiscussion'] = 'Start new discussions';
$string['peerforumsubjecthidden'] = 'Subject (hidden)';
$string['peerforumtracked'] = 'Unread posts are being tracked';
$string['peerforumtrackednot'] = 'Unread posts are not being tracked';
$string['peerforumtype'] = 'PeerForum type';
$string['peerforumtype_help'] = 'There are 5 peerforum types:

* A single simple discussion - A single discussion topic which everyone can reply to (cannot be used with separate groups)
* Each person posts one discussion - Each student can post exactly one new discussion topic, which everyone can then reply to
* Q and A peerforum - Students must first post their perspectives before viewing other students\' posts
* Standard peerforum displayed in a blog-like format - An open peerforum where anyone can start a new discussion at any time, and in which discussion topics are displayed on one page with "Discuss this topic" links
* Standard peerforum for general use - An open peerforum where anyone can start a new discussion at any time';
$string['peerforum:viewallratings'] = 'View all raw ratings given by individuals';
$string['peerforum:viewanyrating'] = 'View total ratings that anyone received';
$string['peerforum:viewdiscussion'] = 'View discussions';
$string['peerforum:viewhiddentimedposts'] = 'View hidden timed posts';
$string['peerforum:viewqandawithoutposting'] = 'Always see Q and A posts';
$string['peerforum:viewrating'] = 'View the total rating you received';
$string['peerforum:viewsubscribers'] = 'View subscribers';
$string['generalpeerforum'] = 'Standard peerforum for general use';
$string['generalpeerforums'] = 'General peerforums';
$string['gradeitem:peerforum'] = 'PeerForum';
$string['hiddenpeerforumpost'] = 'Hidden peerforum post';
$string['hidegraderpanel'] = 'Hide grader panel';
$string['hidepreviousrepliescount'] = 'Hide previous replies ({$a})';
$string['hideusersearch'] = 'Hide user search';
$string['indicator:cognitivedepth'] = 'PeerForum cognitive';
$string['indicator:cognitivedepth_help'] =
        'This indicator is based on the cognitive depth reached by the student in a PeerForum activity.';
$string['indicator:cognitivedepthdef'] = 'PeerForum cognitive';
$string['indicator:cognitivedepthdef_help'] =
        'The participant has reached this percentage of the cognitive engagement offered by the PeerForum activities during this analysis interval (Levels = No view, View, Submit, View feedback, Comment on feedback, Resubmit after viewing feedback)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'PeerForum social';
$string['indicator:socialbreadth_help'] =
        'This indicator is based on the social breadth reached by the student in a PeerForum activity.';
$string['indicator:socialbreadthdef'] = 'PeerForum social';
$string['indicator:socialbreadthdef_help'] =
        'The participant has reached this percentage of the social engagement offered by the PeerForum activities during this analysis interval (Levels = No participation, Participant alone, Participant with others)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['starredonly'] = 'Search starred discussions only';
$string['indexoutoftotal'] = '{$a->index} out of {$a->total}';
$string['inpeerforum'] = 'in {$a}';
$string['inreplyto'] = 'In reply to {$a}';
$string['introblog'] =
        'The posts in this peerforum were copied here automatically from blogs of users in this course because those blog entries are no longer available';
$string['intronews'] = 'General news and announcements';
$string['introsocial'] = 'An open peerforum for chatting about anything you want to';
$string['introteacher'] = 'A peerforum for teacher-only notes and discussion';
$string['invalidaccess'] = 'This page was not accessed correctly';
$string['invaliddiscussionid'] = 'Discussion ID was incorrect or no longer exists';
$string['invaliddigestsetting'] = 'An invalid mail digest setting was provided';
$string['invalidforcesubscribe'] = 'Invalid force subscription mode';
$string['invalidpeerforumid'] = 'PeerForum ID was incorrect';
$string['invalidparentpostid'] = 'Parent post ID was incorrect';
$string['invalidpostid'] = 'Invalid post ID - {$a}';
$string['lastpost'] = 'Last post';
$string['learningpeerforums'] = 'Learning peerforums';
$string['lockdiscussionafter'] = 'Lock discussions after period of inactivity';
$string['lockdiscussionafter_help'] = 'Discussions may be automatically locked after a specified time has elapsed since the last reply.

Users with the capability to reply to locked discussions can unlock a discussion by replying to it.';
$string['longpost'] = 'Long post';
$string['locked'] = 'Locked';
$string['lockdiscussion'] = 'Lock this discussion';
$string['lockupdated'] = 'The lock option has been updated.';
$string['mailnow'] = 'Send peerforum post notifications with no editing-time delay';
$string['manydiscussions'] = 'Discussions per page';
$string['managesubscriptionsoff'] = 'Finish managing subscriptions';
$string['managesubscriptionson'] = 'Manage subscribers';
$string['markasread'] = 'Mark as read';
$string['markalldread'] = 'Mark all posts in this discussion read.';
$string['markallread'] = 'Mark all posts in this peerforum read.';
$string['markasreadonnotification'] = 'When sending peerforum post notifications';
$string['markasreadonnotificationno'] = 'Do not mark the post as read';
$string['markasreadonnotificationyes'] = 'Mark the post as read';
$string['markasreadonnotification_help'] =
        'When you are notified of a peerforum post, you can choose whether this should mark the post as read for the purpose of peerforum tracking.';
$string['markread'] = 'Mark read';
$string['markreadbutton'] = 'Mark<br />read';
$string['markunread'] = 'Mark unread';
$string['markunreadbutton'] = 'Mark<br />unread';
$string['maxattachments'] = 'Maximum number of attachments';
$string['maxattachments_help'] = 'This setting specifies the maximum number of files that can be attached to a peerforum post.';
$string['maxattachmentsize'] = 'Maximum attachment size';
$string['maxattachmentsize_help'] = 'This setting specifies the largest size of file that can be attached to a peerforum post.';
$string['maxtimehaspassed'] = 'Sorry, but the maximum time for editing this post ({$a}) has passed!';
$string['message'] = 'Message';
$string['messageinboundattachmentdisallowed'] =
        'Unable to post your reply, since it includes an attachment and the peerforum doesn\'t allow attachments.';
$string['messageinboundfilecountexceeded'] =
        'Unable to post your reply, since it includes more than the maximum number of attachments allowed for the peerforum ({$a->peerforum->maxattachments}).';
$string['messageinboundfilesizeexceeded'] =
        'Unable to post your reply, since the total attachment size ({$a->filesize}) is greater than the maximum size allowed for the peerforum ({$a->maxbytes}).';
$string['messageinboundpeerforumhidden'] = 'Unable to post your reply, since the peerforum is currently unavailable.';
$string['messageinboundnopostpeerforum'] =
        'Unable to post your reply, since you do not have permission to post in the {$a->peerforum->name} peerforum.';
$string['messageinboundthresholdhit'] =
        'Unable to post your reply.  You have exceeded the posting threshold set for this peerforum';
$string['messageprovider:digests'] = 'Subscribed peerforum digests';
$string['messageprovider:posts'] = 'Subscribed peerforum posts';
$string['missingsearchterms'] = 'The following search terms occur only in the HTML markup of this message:';
$string['modeflatnewestfirst'] = 'Display replies flat, with newest first';
$string['modeflatoldestfirst'] = 'Display replies flat, with oldest first';
$string['modenested'] = 'Display replies in nested form';
$string['modenestedv2'] = 'Display replies in experimental nested form';
$string['modethreaded'] = 'Display replies in threaded form';
$string['modulename'] = 'PeerForum';
$string['modulename_help'] = 'The peerforum activity module enables participants to have asynchronous discussions i.e. discussions that take place over an extended period of time.

There are several peerforum types to choose from, such as a standard peerforum where anyone can start a new discussion at any time; a peerforum where each student can post exactly one discussion; or a question and answer peerforum where students must first post before being able to view other students\' posts. A teacher can allow files to be attached to peerforum posts. Attached images are displayed in the peerforum post.

Participants can subscribe to a peerforum to receive notifications of new peerforum posts. A teacher can set the subscription mode to optional, forced or auto, or prevent subscription completely. If required, students can be blocked from posting more than a given number of posts in a given time period; this can prevent individuals from dominating discussions.

PeerForum posts can be rated by teachers or students (peer evaluation). Ratings can be aggregated to form a final grade which is recorded in the gradebook.

PeerForums have many uses, such as

* A social space for students to get to know each other
* For course announcements (using a news peerforum with forced subscription)
* For discussing course content or reading materials
* For continuing online an issue raised previously in a face-to-face session
* For teacher-only discussions (using a hidden peerforum)
* A help centre where tutors and students can give advice
* A one-on-one support area for private student-teacher communications (using a peerforum with separate groups and with one student per group)
* For extension activities, for example ‘brain teasers’ for students to ponder and suggest solutions to';
$string['modulename_link'] = 'mod/peerforum/view';
$string['modulenameplural'] = 'PeerForums';
$string['more'] = 'more';
$string['movedmarker'] = '(Moved)';
$string['movethisdiscussionlabel'] = 'Move the current discussion to the specified peerforum';
$string['movethisdiscussionto'] = 'Move this discussion to ...';
$string['mustprovidediscussionorpost'] = 'You must provide either a discussion ID or post ID to export.';
$string['myprofileownpost'] = 'My peerforum posts';
$string['myprofileowndis'] = 'My peerforum discussions';
$string['myprofileotherdis'] = 'PeerForum discussions';
$string['namenews'] = 'Announcements';
$string['namenews_help'] =
        'The course announcements peerforum is a special peerforum for announcements and is automatically created when a course is created. A course can have only one announcements peerforum. Only teachers and administrators can post announcements. The "Latest announcements" block will display recent announcements.';
$string['namesocial'] = 'Social peerforum';
$string['nameteacher'] = 'Teacher peerforum';
$string['nextdiscussiona'] = 'Next discussion: {$a}';
$string['nextuser'] = 'Save changes and proceed to the next user';
$string['newpeerforumposts'] = 'New peerforum posts';
$string['noattachments'] = 'There are no attachments to this post';
$string['nodiscussions'] = 'There are no discussion topics yet in this peerforum';
$string['nodiscussionsstartedby'] = '{$a} has not started any discussions';
$string['nodiscussionsstartedbyyou'] = 'You haven\'t started any discussions yet';
$string['noguestpost'] = 'Sorry, guests are not allowed to post.';
$string['noguestsubscribe'] = 'Sorry, guests are not allowed to subscribe.';
$string['noguesttracking'] = 'Sorry, guests are not allowed to set tracking options.';
$string['nomorepostscontaining'] = 'No more posts containing \'{$a}\' were found';
$string['nonews'] = 'No announcements have been posted yet.';
$string['noonecansubscribenow'] = 'Subscriptions are now disallowed';
$string['nopermissiontosubscribe'] = 'You do not have the permission to view peerforum subscribers';
$string['nopermissiontoview'] = 'You do not have permissions to view this post';
$string['nopostpeerforum'] = 'Sorry, you are not allowed to post to this peerforum';
$string['noposts'] = 'No posts';
$string['nopostsmadebyuser'] = '{$a} has made no posts';
$string['nopostsmadebyyou'] = 'You haven\'t made any posts';
$string['noquestions'] = 'There are no questions yet in this peerforum';
$string['nosubscribers'] = 'There are no subscribers yet for this peerforum';
$string['notsubscribed'] = 'Subscribe';
$string['notexists'] = 'Discussion no longer exists';
$string['nothingnew'] = 'Nothing new for {$a}';
$string['notingroup'] = 'Sorry, but you need to be part of a group to see this peerforum.';
$string['notinstalled'] = 'The peerforum module is not installed';
$string['notlocked'] = 'Lock';
$string['notpartofdiscussion'] = 'This post is not part of a discussion!';
$string['notrackpeerforum'] = 'Don\'t track unread posts';
$string['noviewdiscussionspermission'] = 'You do not have the permission to view discussions in this peerforum';
$string['nowallsubscribed'] = 'You are now subscribed to all peerforums in {$a}.';
$string['nowallunsubscribed'] = 'You are now unsubscribed from all peerforums in {$a}.';
$string['nownotsubscribed'] = '{$a->name} will NOT be notified of new posts in \'{$a->peerforum}\'';
$string['nownottracking'] = '{$a->name} is no longer tracking \'{$a->peerforum}\'.';
$string['nowsubscribed'] = '{$a->name} will be notified of new posts in \'{$a->peerforum}\'';
$string['nowtracking'] = '{$a->name} is now tracking \'{$a->peerforum}\'.';
$string['numposts'] = '{$a} posts';
$string['numberofreplies'] = 'Number of replies: {$a}';
$string['olderdiscussions'] = 'Older discussions';
$string['oldertopics'] = 'Older topics';
$string['oldpostdays'] = 'Read after days';
$string['page-mod-peerforum-x'] = 'Any peerforum module page';
$string['page-mod-peerforum-view'] = 'PeerForum module main page';
$string['page-mod-peerforum-discuss'] = 'PeerForum module discussion thread page';
$string['parent'] = 'Show parent';
$string['parentofthispost'] = 'Parent of this post';
$string['permalink'] = 'Permalink';
$string['permanentlinktopost'] = 'Permanent link to this post';
$string['permanentlinktoparentpost'] = 'Permanent link to the parent of this post';
$string['postisprivatereply'] = 'This is a private reply. It is not visible to other participants.';
$string['pindiscussion'] = 'Pin this discussion';
$string['pinupdated'] = 'The pin option has been updated.';
$string['posttomygroups'] = 'Post a copy to all groups';
$string['posttomygroups_help'] =
        'Posts a copy of this message to all groups you have access to. Participants in groups you do not have access to will not see this post';
$string['prevdiscussiona'] = 'Previous discussion: {$a}';
$string['pluginadministration'] = 'PeerForum administration';
$string['pluginname'] = 'PeerForum';
$string['postadded'] = '<p>Your post was successfully added.</p> <p>You have {$a} to edit it if you want to make any changes.</p>';
$string['postaddedsuccess'] = 'Your post was successfully added.';
$string['postaddedtimeleft'] = 'You have {$a} to edit it if you want to make any changes.';
$string['postbymailsuccess'] = 'Your reply "{$a->subject}" was successfully posted: {$a->discussionurl}';
$string['postbymailsuccess_html'] = 'Your reply <a href="{$a->discussionurl}">{$a->subject}</a> was successfully posted.';
$string['postbyuser'] = '{$a->post} by {$a->user}';
$string['postincontext'] = 'See this post in context';
$string['postmailinfolink'] = 'This is a copy of a message posted in {$a->coursename}.

To reply click on this link: {$a->replylink}';
$string['postmailnow'] = '<p>This post will be mailed out immediately to all peerforum subscribers.</p>';
$string['postmailsubject'] = '{$a->courseshortname}: {$a->subject}';
$string['postrating1'] = 'Mostly separate knowing';
$string['postrating2'] = 'Separate and connected';
$string['postrating3'] = 'Mostly connected knowing';
$string['posts'] = 'Posts';
$string['postsfrom'] = 'Posts from';
$string['postsmadebyuser'] = 'Posts made by {$a}';
$string['postsmadebyuserincourse'] = 'Posts made by {$a->fullname} in {$a->coursename}';
$string['poststo'] = 'Posts to';
$string['posttopeerforum'] = 'Post to peerforum';
$string['postupdated'] = 'Your post was updated';
$string['potentialsubscribers'] = 'Potential subscribers';
$string['previoususer'] = 'Save changes and proceed to the previous user';
$string['privacy:digesttypenone'] = 'We do not hold any data relating to a preferred peerforum digest type for this peerforum.';
$string['privacy:digesttypepreference'] = 'You have chosen to receive the following peerforum digest type: "{$a->type}".';
$string['privacy:discussionsubscriptionpreference'] =
        'You have chosen the following discussion subscription preference for this peerforum: "{$a->preference}"';
$string['privacy:metadata:core_tag'] = 'The peerforum makes use of the tag subsystem to support tagging of posts.';
$string['privacy:metadata:core_rating'] = 'The peerforum makes use of the rating subsystem to support the rating of posts.';
$string['privacy:metadata:peerforum_digests'] = 'Information about the digest preferences for each peerforum.';
$string['privacy:metadata:peerforum_digests:peerforum'] = 'The peerforum subscribed to.';
$string['privacy:metadata:peerforum_digests:maildigest'] = 'The digest preference.';
$string['privacy:metadata:peerforum_digests:userid'] = 'The ID of the user with the digest preference.';
$string['privacy:metadata:peerforum_discussion_subs'] = 'Information about the subscriptions to individual peerforum discussions';
$string['privacy:metadata:peerforum_discussion_subs:discussionid'] = 'The ID of the discussion that was subscribed to.';
$string['privacy:metadata:peerforum_discussion_subs:preference'] = 'The start time of the subscription.';
$string['privacy:metadata:peerforum_discussion_subs:userid'] = 'The ID of the user with the discussion subscription.';
$string['privacy:metadata:peerforum_discussions'] =
        'Information about the individual peerforum discussions that a user has created';
$string['privacy:metadata:peerforum_discussions:assessed'] = 'TODOD - what does this field store';
$string['privacy:metadata:peerforum_discussions:name'] = 'The name of the discussion, as chosen by the author.';
$string['privacy:metadata:peerforum_discussions:timemodified'] = 'The time that the discussion was last modified.';
$string['privacy:metadata:peerforum_discussions:userid'] = 'The ID of the user who created the discussion';
$string['privacy:metadata:peerforum_discussions:usermodified'] = 'The ID of the user who last modified the discussion in some way.';
$string['privacy:metadata:peerforum_grades'] = 'Grade data for the peerforum';
$string['privacy:metadata:peerforum_grades:peerforum'] = 'The peerforum that was graded';
$string['privacy:metadata:peerforum_grades:grade'] = 'The grade awarded';
$string['privacy:metadata:peerforum_grades:userid'] = 'The user who was graded';
$string['privacy:metadata:peerforum_posts'] = 'Information about the digest preferences for each peerforum.';
$string['privacy:metadata:peerforum_posts:created'] = 'The time that the post was created.';
$string['privacy:metadata:peerforum_posts:discussion'] = 'The discussion that the post is in.';
$string['privacy:metadata:peerforum_posts:message'] = 'The message of the peerforum post.';
$string['privacy:metadata:peerforum_posts:modified'] = 'The time that the post was last modified.';
$string['privacy:metadata:peerforum_posts:parent'] = 'The parent post that was replied to.';
$string['privacy:metadata:peerforum_posts:subject'] = 'The subject of the peerforum post.';
$string['privacy:metadata:peerforum_posts:totalscore'] = 'The message of the peerforum post.';
$string['privacy:metadata:peerforum_posts:userid'] = 'The ID of the user who authored the peerforum post.';
$string['privacy:metadata:peerforum_posts:privatereplyto'] = 'The ID of the user this reply was sent to.';
$string['privacy:metadata:peerforum_queue'] = 'Temporary log of posts that will be mailed in digest form';
$string['privacy:metadata:peerforum_queue:discussionid'] = 'PeerForum discussion ID';
$string['privacy:metadata:peerforum_queue:postid'] = 'PeerForum post ID';
$string['privacy:metadata:peerforum_queue:timemodified'] = 'The modified time of the original post';
$string['privacy:metadata:peerforum_queue:userid'] = 'User who needs to be notified of the post';
$string['privacy:metadata:peerforum_read'] = 'Information about which posts have been read by the user.';
$string['privacy:metadata:peerforum_read:discussionid'] = 'The discussion that the post is in.';
$string['privacy:metadata:peerforum_read:firstread'] = 'The first time that the post was read.';
$string['privacy:metadata:peerforum_read:lastread'] = 'The most recent time that the post was read.';
$string['privacy:metadata:peerforum_read:postid'] = 'The post that was read.';
$string['privacy:metadata:peerforum_read:userid'] = 'The ID of the user that this record relates to.';
$string['privacy:metadata:peerforum_subscriptions'] = 'Information about which peerforums the user has subscribed to.';
$string['privacy:metadata:peerforum_subscriptions:peerforum'] = 'The peerforum that was subscribed to.';
$string['privacy:metadata:peerforum_subscriptions:userid'] = 'The ID of the user that this peerforum subscription relates to.';
$string['privacy:metadata:peerforum_track_prefs'] =
        'Information about which peerforums the user has chosen to track post reads for.';
$string['privacy:metadata:peerforum_track_prefs:peerforumid'] = 'The peerforum that has read tracking enabled.';
$string['privacy:metadata:peerforum_track_prefs:userid'] = 'The ID of the user that this peerforum tracking preference relates to.';
$string['privacy:metadata:preference:autosubscribe'] = 'Whether to subscribe to discussions when replying to posts within them.';
$string['privacy:metadata:preference:peerforum_discussionlistsortorder'] = 'The preferred sorting order of the discussions list';
$string['privacy:metadata:preference:maildigest'] = 'The site-wide mail digest preference';
$string['privacy:metadata:preference:markasreadonnotification'] =
        'Whether to mark peerforum posts as read when receiving them as messages.';
$string['privacy:metadata:preference:trackpeerforums'] = 'Whether to enable read tracking.';
$string['privacy:postwasread'] = 'This post was first read on {$a->firstread} and most recently read on {$a->lastread}';
$string['privacy:readtrackingdisabled'] = 'You have chosen to not track posts you have read within this peerforum.';
$string['privacy:request:delete:discussion:name'] = 'Delete at the request of the author';
$string['privacy:request:delete:post:message'] = 'The content of this post has been deleted at the request of its author.';
$string['privacy:request:delete:post:subject'] = 'Delete at the request of the author';
$string['privacy:subscribedtopeerforum'] = 'You are subscribed to this peerforum.';
$string['privatereply'] = 'Reply privately';
$string['privatereply_help'] =
        'A private reply can only be viewed by the author of the post being replied to, and any users with the capability to view private replies.';
$string['processingdigest'] = 'Processing email digest for user {$a}';
$string['processingpost'] = 'Processing post {$a}';
$string['prune'] = 'Split';
$string['prunedpost'] = 'A new discussion has been created from that post';
$string['pruneheading'] = 'Split the discussion and move this post to a new discussion';
$string['qandapeerforum'] = 'Q and A peerforum';
$string['qandanotify'] =
        'This is a question and answer peerforum. In order to see other responses to these questions, you must first post your answer';
$string['re'] = 'Re:';
$string['readtherest'] = 'Read the rest of this topic';
$string['removeallpeerforumtags'] = 'Remove all peerforum tags';
$string['removefromfavourites'] = 'Unstar this discussion';
$string['replies'] = 'Replies';
$string['repliesmany'] = '{$a} replies so far';
$string['repliesone'] = '{$a} reply so far';
$string['reply'] = 'Reply';
$string['replyauthorself'] = '{$a} (you)';
$string['replyingtoauthor'] = 'Replying to {$a}...';
$string['replyplaceholder'] = 'Write your reply...';
$string['replypeerforum'] = 'Reply to peerforum';
$string['replytopostbyemail'] = 'You can reply to this via email.';
$string['replytouser'] = 'Use email address in reply';
$string['reply_handler'] = 'Reply to peerforum posts via email';
$string['reply_handler_name'] = 'Reply to peerforum posts';
$string['resetpeerforums'] = 'Delete posts from';
$string['resetpeerforumsall'] = 'Delete all posts';
$string['resetdigests'] = 'Delete all per-user peerforum digest preferences';
$string['resetsubscriptions'] = 'Delete all peerforum subscriptions';
$string['resettrackprefs'] = 'Delete all peerforum tracking preferences';
$string['rsssubscriberssdiscussions'] = 'RSS feed of discussions';
$string['rsssubscriberssposts'] = 'RSS feed of posts';
$string['rssarticles'] = 'Number of RSS recent articles';
$string['rssarticles_help'] =
        'This setting specifies the number of articles (either discussions or posts) to include in the RSS feed. Between 5 and 20 generally acceptable.';
$string['rsstype'] = 'RSS feed for this activity';
$string['rsstype_help'] =
        'To enable the RSS feed for this activity, select either discussions or posts to be included in the feed.';
$string['rsstypedefault'] = 'RSS feed type';
$string['search'] = 'Search';
$string['search:post'] = 'PeerForum - posts';
$string['search:activity'] = 'PeerForum - activity information';
$string['searchdatefrom'] = 'Posts must be newer than this';
$string['searchdateto'] = 'Posts must be older than this';
$string['searchpeerforumintro'] = 'Please enter search terms into one or more of the following fields:';
$string['searchpeerforums'] = 'Search peerforums';
$string['searchfullwords'] = 'These words should appear as whole words';
$string['searchnotwords'] = 'These words should NOT be included';
$string['searcholderposts'] = 'Search older posts...';
$string['searchphrase'] = 'This exact phrase must appear in the post';
$string['searchresults'] = 'Search results';
$string['searchsubject'] = 'These words should be in the subject';
$string['searchtags'] = 'Is tagged with';
$string['searchuser'] = 'This name should match the author';
$string['searchusers'] = 'Search users';
$string['searchuserid'] = 'The Moodle ID of the author';
$string['searchwhichpeerforums'] = 'Choose which peerforums to search';
$string['searchwords'] = 'These words can appear anywhere in the post';
$string['seeallposts'] = 'See all posts made by this user';
$string['sendstudentnotificationsno'] = 'No';
$string['sendstudentnotificationsyes'] = 'Yes, send notification to student';
$string['sendstudentnotificationsdefault'] = 'Default setting for "Notify students"';
$string['sendstudentnotificationsdefault_help'] = 'Set the default value for the "Notify students" checkbox on the grading form.';
$string['settings'] = 'Settings';
$string['shortpost'] = 'Short post';
$string['showingcountoftotaldiscussions'] = 'List of discussions. Showing {$a->count} of {$a->total} discussions';
$string['showgraderpanel'] = 'Show grader panel';
$string['showpreviousrepliescount'] = 'Show previous replies ({$a})';
$string['showsubscribers'] = 'Show/edit current subscribers';
$string['showusersearch'] = 'Show user search';
$string['singlepeerforum'] = 'A single simple discussion';
$string['smallmessage'] = '{$a->user} posted in {$a->peerforumname}';
$string['smallmessagedigest'] = 'PeerForum digest containing {$a} messages';
$string['startedby'] = 'Started by';
$string['subject'] = 'Subject';
$string['subscribe'] = 'Subscribe to this peerforum';
$string['subscribediscussion'] = 'Subscribe to this discussion';
$string['subscribeall'] = 'Subscribe everyone to this peerforum';
$string['subscribeenrolledonly'] = 'Sorry, only enrolled users are allowed to subscribe to peerforum post notifications.';
$string['subscribed'] = 'Subscribed';
$string['subscribenone'] = 'Unsubscribe everyone from this peerforum';
$string['subscribers'] = 'Subscribers';
$string['subscriberstowithcount'] = 'Subscribers to "{$a->name}" ({$a->count})';
$string['subscribestart'] = 'Send me notifications of new posts in this peerforum';
$string['subscribestop'] = 'I don\'t want to be notified of new posts in this peerforum';
$string['subscription'] = 'Subscription';
$string['subscription_help'] =
        'If you are subscribed to a peerforum it means you will receive notification of new peerforum posts. Usually you can choose whether you wish to be subscribed, though sometimes subscription is forced so that everyone receives notifications.';
$string['subscriptionandtracking'] = 'Subscription and tracking';
$string['subscriptionmode'] = 'Subscription mode';
$string['subscriptionmode_help'] = 'When a participant is subscribed to a peerforum it means they will receive peerforum post notifications. There are 4 subscription mode options:

* Optional subscription - Participants can choose whether to be subscribed
* Forced subscription - Everyone is subscribed and cannot unsubscribe
* Auto subscription - Everyone is subscribed initially but can choose to unsubscribe at any time
* Subscription disabled - Subscriptions are not allowed';
$string['subscriptionoptional'] = 'Optional subscription';
$string['subscriptionforced'] = 'Forced subscription';
$string['subscriptionauto'] = 'Auto subscription';
$string['subscriptiondisabled'] = 'Subscription disabled';
$string['subscriptions'] = 'Subscriptions';
$string['tagarea_peerforum_posts'] = 'PeerForum posts';
$string['tagsdeleted'] = 'PeerForum tags have been deleted';
$string['thispeerforumisthrottled'] =
        'This peerforum has a limit to the number of peerforum postings you can make in a given time period - this is currently set at {$a->blockafter} posting(s) in {$a->blockperiod}';
$string['thispeerforumisdue'] = 'The due date for posting to this peerforum was {$a}.';
$string['thispeerforumhasduedate'] = 'The due date for posting to this peerforum is {$a}.';
$string['timed'] = 'Timed';
$string['timeddiscussion'] = 'Timed discussion';
$string['timedhidden'] = 'Timed status: Hidden from students';
$string['timedposts'] = 'Timed posts';
$string['timedvisible'] = 'Timed status: Visible to all users';
$string['timestartenderror'] = 'Display end date cannot be earlier than the start date';
$string['togglediscussionmenu'] = 'Toggle the discussion menu';
$string['togglefullscreen'] = 'Toggle full screen';
$string['togglesettingsdrawer'] = 'Toggle settings drawer';
$string['trackpeerforum'] = 'Track unread posts';
$string['trackreadposts_header'] = 'PeerForum tracking';
$string['tracking'] = 'Track';
$string['trackingoff'] = 'Off';
$string['trackingon'] = 'Forced';
$string['trackingoptional'] = 'Optional';
$string['trackingtype'] = 'Read tracking';
$string['trackingtype_help'] = 'Read tracking enables participants to easily check which posts they have not yet seen by highlighting any new posts.

If set to optional, participants can choose whether to turn tracking on or off via a link in the actions menu or administration block, depending on the theme. (Users must also enable peerforum tracking in their peerforum preferences.)

If \'Allow forced read tracking\' is enabled in the site administration, then a further option is available - forced. This means that tracking is always on, regardless of users\' peerforum preferences.';
$string['unlockdiscussion'] = 'Unlock this discussion';
$string['unread'] = 'Unread';
$string['unreadpost'] = 'Unread post';
$string['unreadposts'] = 'Unread posts';
$string['unreadpostsnumber'] = '{$a} unread posts';
$string['unreadpostsone'] = '1 unread post';
$string['unsubscribe'] = 'Unsubscribe from this peerforum';
$string['unsubscribelink'] = 'Unsubscribe from this peerforum: {$a}';
$string['unsubscribediscussion'] = 'Unsubscribe from this discussion';
$string['unsubscribediscussionlink'] = 'Unsubscribe from this discussion: {$a}';
$string['unsubscribeall'] = 'Unsubscribe from all peerforums';
$string['unsubscribeallconfirm'] =
        'You are currently subscribed to {$a->peerforums} peerforums, and {$a->discussions} discussions. Do you really want to unsubscribe from all peerforums and discussions, and disable discussion auto-subscription?';
$string['unsubscribeallconfirmpeerforums'] =
        'You are currently subscribed to {$a->peerforums} peerforums. Do you really want to unsubscribe from all peerforums and disable discussion auto-subscription?';
$string['unsubscribeallconfirmdiscussions'] =
        'You are currently subscribed to {$a->discussions} discussions. Do you really want to unsubscribe from all discussions and disable discussion auto-subscription?';
$string['unsubscribealldone'] =
        'All optional peerforum subscriptions were removed. You will still receive notifications from peerforums with forced subscription. To manage peerforum notifications go to Messaging in My Profile Settings.';
$string['unsubscribeallempty'] =
        'You are not subscribed to any peerforums. To disable all notifications from this server go to Messaging in My Profile Settings.';
$string['unsubscribed'] = 'Unsubscribed';
$string['unsubscribeshort'] = 'Unsubscribe';
$string['useexperimentalui'] = 'Use experimental nested discussion view';
$string['usermarksread'] = 'Manual message read marking';
$string['usernavigation'] = 'User navigation';
$string['userspeerforumposts'] = 'User\'s peerforum posts';
$string['unpindiscussion'] = 'Unpin this discussion';
$string['viewalldiscussions'] = 'View all discussions';
$string['viewparentpost'] = 'View parent post';
$string['viewthediscussion'] = 'View the discussion';
$string['warnafter'] = 'Post threshold for warning';
$string['warnafter_help'] =
        'Students can be warned as they approach the maximum number of posts allowed in a given period. This setting specifies after how many posts they are warned. Users with the capability mod/peerforum:postwithoutthrottling are exempt from post limits.';
$string['warnformorepost'] = 'Warning! There is more than one discussion in this peerforum - using the most recent';
$string['yournewquestion'] = 'Your new question';
$string['yournewtopic'] = 'Your new discussion topic';
$string['yourreply'] = 'Your reply';
$string['peerforumsubjectdeleted'] = 'This peerforum post has been removed';
$string['peerforumbodydeleted'] = 'The content of this peerforum post has been removed and can no longer be accessed.';
$string['peerforumgrader'] = 'PeerForum grader';
$string['grade_peerforum_header'] = 'Whole peerforum grading';
$string['grade_peerforum_name'] = 'Whole peerforum';
$string['grade_peerforum_title'] = 'Grade';
$string['grade_rating_name'] = 'Rating';
$string['gradeusers'] = 'Grade users';
$string['graded'] = 'Graded';
$string['gradedby'] = 'Graded by';
$string['notgraded'] = 'Not graded';
$string['nowgradinguser'] = 'Now grading {$a}';
$string['gradeforrating'] = 'Grade for rating: {$a->str_long_grade}';
$string['gradeforratinghidden'] = 'Grade for rating hidden';
$string['gradeforwholepeerforum'] = 'Grade for peerforum: {$a->str_long_grade}';
$string['grading'] = 'Grading';
$string['gradingstatus'] = 'Grade status:';
$string['gradeforwholepeerforumhidden'] = 'Grade for peerforum hidden';
$string['gradeitemnameforwholepeerforum'] = 'Whole peerforum grade for {$a->name}';
$string['gradeitemnameforrating'] = 'Rating grade for {$a->name}';
$string['grades:gradesavedfor'] = 'Grade saved for {$a->fullname}';
$string['grades:gradesavefailed'] = 'Unable to save grade for {$a->fullname}: {$a->error}';
$string['notgraded'] = 'Not graded';
$string['nousersmatch'] = 'No user(s) found for given criteria';
$string['showmoreusers'] = 'Show more users';
$string['viewconversation'] = 'View discussion';
$string['viewgrades'] = 'View grades';

// Deprecated since Moodle 3.8.
$string['cannotdeletediscussioninsinglediscussion'] = 'You cannot delete the first post in a single discussion';
$string['inpagereplysubject'] = 'Re: {$a}';
$string['overviewnumpostssince'] = '{$a} posts since last login';
$string['overviewnumunread'] = '{$a} total unread';
