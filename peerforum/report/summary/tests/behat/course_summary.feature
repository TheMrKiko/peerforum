@mod @mod_peerforum @peerforumreport @peerforumreport_summary
Feature: Course level peerforum summary report
  In order to gain an overview of students' peerforum activities across a course
  As a teacher
  I should be able to prepare a summary report of all peerforums in a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student2 | C2     | student        |
      | student3 | C2     | student        |
    And the following "activities" exist:
      | activity  | name       | description    | course | idnumber   |
      | peerforum | peerforum1 | C1 peerforum 1 | C1     | peerforum1 |
      | peerforum | peerforum2 | C1 peerforum 2 | C1     | peerforum2 |
      | peerforum | peerforum3 | C1 peerforum 3 | C1     | peerforum3 |
      | peerforum | peerforum4 | C2 peerforum 1 | C2     | peerforum4 |
    And the following peerforum discussions exist in course "Course 1":
      | user     | peerforum  | name        | message      | created                 |
      | teacher1 | peerforum1 | discussion1 | Discussion 1 | ##2018-01-14 09:00:00## |
      | teacher1 | peerforum2 | discussion2 | Discussion 2 | ##2019-03-27 12:10:00## |
      | teacher1 | peerforum3 | discussion3 | Discussion 3 | ##2019-12-25 15:20:00## |
      | teacher1 | peerforum3 | discussion4 | Discussion 4 | ##2019-12-26 09:30:00## |
      | student1 | peerforum2 | discussion5 | Discussion 5 | ##2019-06-06 18:40:00## |
      | student1 | peerforum3 | discussion6 | Discussion 6 | ##2020-01-25 11:50:00## |
    And the following peerforum replies exist in course "Course 1":
      | user     | peerforum  | discussion  | subject | message | created                 |
      | teacher1 | peerforum1 | discussion1 | Re d1   | Reply 1 | ##2018-02-15 11:10:00## |
      | teacher1 | peerforum2 | discussion5 | Re d5   | Reply 2 | ##2019-06-09 18:20:00## |
      | teacher1 | peerforum2 | discussion5 | Re d5   | Reply 3 | ##2019-07-10 09:30:00## |
      | student1 | peerforum1 | discussion1 | Re d1   | Reply 4 | ##2018-01-25 16:40:00## |
      | student1 | peerforum2 | discussion2 | Re d6   | Reply 5 | ##2019-03-28 11:50:00## |
      | student1 | peerforum3 | discussion4 | Re d4   | Reply 6 | ##2019-12-30 20:00:00## |
    And the following peerforum discussions exist in course "Course 2":
      | user     | peerforum  | name        | message      | created                 |
      | teacher1 | peerforum4 | discussion7 | Discussion 7 | ##2020-01-29 15:00:00## |
      | student2 | peerforum4 | discussion8 | Discussion 8 | ##2020-02-02 16:00:00## |
    And the following peerforum replies exist in course "Course 2":
      | user     | peerforum  | discussion  | subject | message | created                 |
      | teacher1 | peerforum4 | discussion8 | Re d8   | Reply 7 | ##2020-02-03 09:45:00## |
      | student2 | peerforum4 | discussion7 | Re d7   | Reply 8 | ##2020-02-04 13:50:00## |

  Scenario: Course peerforum summary report can be viewed by teacher and contains accurate data
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "peerforum2"
    And I navigate to "PeerForum summary report" in current page administration
    And I should see "Export posts"
    And the following should exist in the "peerforumreport_summary_table" table:
    # |                      | Discussions | Replies |                                 |                                |
      | First name / Surname | -3- | -4- | Earliest post                   | Most recent post              |
      | Student 1            | 1   | 1   | Thursday, 28 March 2019, 11:50  | Thursday, 6 June 2019, 6:40   |
      | Student 2            | 0   | 0   | -                               | -                             |
      | Teacher 1            | 1   | 2   | Wednesday, 27 March 2019, 12:10 | Wednesday, 10 July 2019, 9:30 |
    And the following should not exist in the "peerforumreport_summary_table" table:
      | First name / Surname |
      | Student 3            |
    And the "PeerForum selected" select box should contain "All peerforums in course"
    And the "PeerForum selected" select box should contain "peerforum1"
    And the "PeerForum selected" select box should contain "peerforum2"
    And the "PeerForum selected" select box should contain "peerforum3"
    And the "PeerForum selected" select box should not contain "peerforum4"
    Then I select "All peerforums in course" from the "PeerForum selected" singleselect
    And I should not see "Export posts"
    And the following should exist in the "peerforumreport_summary_table" table:
    # |                      | Discussions | Replies |                                 |                                  |
      | First name / Surname | -3- | -4- | Earliest post                   | Most recent post                 |
      | Student 1            | 2   | 3   | Thursday, 25 January 2018, 4:40 | Saturday, 25 January 2020, 11:50 |
      | Student 2            | 0   | 0   | -                               | -                                |
      | Teacher 1            | 4   | 3   | Sunday, 14 January 2018, 9:00   | Thursday, 26 December 2019, 9:30 |
    And the following should not exist in the "peerforumreport_summary_table" table:
      | First name / Surname |
      | Student 3            |

  Scenario: Students given the view capability can view their own course report data
    Given the following "permission overrides" exist:
      | capability                   | permission | role    | contextlevel | reference |
      | peerforumreport/summary:view | Allow      | student | Course       | C1        |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "peerforum1"
    And I navigate to "PeerForum summary report" in current page administration
    And the following should exist in the "peerforumreport_summary_table" table:
    # |                      | Discussions | Replies |                                 |                                 |
      | First name / Surname | -2- | -3- | Earliest post                   | Most recent post                |
      | Student 1            | 0   | 1   | Thursday, 25 January 2018, 4:40 | Thursday, 25 January 2018, 4:40 |
    And the following should not exist in the "peerforumreport_summary_table" table:
      | First name / Surname |
      | Student 2            |
      | Student 3            |
      | Teacher 1            |
    And the "PeerForum selected" select box should contain "All peerforums in course"
    And the "PeerForum selected" select box should contain "peerforum1"
    And the "PeerForum selected" select box should contain "peerforum2"
    And the "PeerForum selected" select box should contain "peerforum3"
    And the "PeerForum selected" select box should not contain "peerforum4"
    Then I select "All peerforums in course" from the "PeerForum selected" singleselect
    And the following should exist in the "peerforumreport_summary_table" table:
    # |                      | Discussions | Replies |                                 |                                  |
      | First name / Surname | -2- | -3- | Earliest post                   | Most recent post                 |
      | Student 1            | 2   | 3   | Thursday, 25 January 2018, 4:40 | Saturday, 25 January 2020, 11:50 |
    And the following should not exist in the "peerforumreport_summary_table" table:
      | First name / Surname |
      | Student 2            |
      | Student 3            |
      | Teacher 1            |
