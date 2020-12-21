@mod @mod_peerforum @peerforumreport @peerforumreport_summary
Feature: Post date columns data available
  In order to determine users' earliest and most recent peerforum posts
  As a teacher
  I need to view that data in the peerforum summary report

  Scenario: Add posts and view accurate summary report
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity  | name       | description         | course | idnumber   |
      | peerforum | peerforum1 | C1 first peerforum  | C1     | peerforum1 |
      | peerforum | peerforum2 | C1 second peerforum | C1     | peerforum2 |
      | peerforum | peerforum1 | C2 first peerforum  | C2     | peerforum1 |
    And the following peerforum discussions exist in course "Course 1":
      | user     | peerforum  | name        | message            | created                 |
      | teacher1 | peerforum1 | discussion1 | t1 earliest        | ##2018-01-02 09:00:00## |
      | teacher1 | peerforum1 | discussion2 | t1 between         | ##2018-03-27 10:00:00## |
      | teacher1 | peerforum2 | discussion3 | t1 other peerforum | ##2018-01-01 11:00:00## |
      | student1 | peerforum1 | discussion4 | s1 latest          | ##2019-03-27 13:00:00## |
      | student2 | peerforum2 | discussion5 | s2 other peerforum | ##2018-03-27 09:00:00## |
    And the following peerforum replies exist in course "Course 1":
      | user     | peerforum  | discussion  | message            | created                 |
      | teacher1 | peerforum1 | discussion1 | t1 between         | ##2018-01-02 10:30:00## |
      | teacher1 | peerforum1 | discussion2 | t1 latest          | ##2019-09-01 07:00:00## |
      | teacher1 | peerforum2 | discussion3 | t1 other peerforum | ##2019-09-12 08:00:00## |
      | student1 | peerforum1 | discussion1 | s1 earliest        | ##2019-03-27 04:00:00## |
      | student2 | peerforum2 | discussion3 | s2 other peerforum | ##2018-03-27 10:00:00## |
    And the following peerforum discussions exist in course "Course 2":
      | user     | peerforum  | name        | message         | created                 |
      | teacher1 | peerforum1 | discussion1 | t1 other course | ##2017-01-01 03:00:00## |
      | teacher1 | peerforum1 | discussion2 | t1 other course | ##2019-09-13 23:59:00## |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "peerforum1"
    And I navigate to "PeerForum summary report" in current page administration
    Then "Teacher 1" row "Earliest post" column of "peerforumreport_summary_table" table should contain "Tuesday, 2 January 2018, 9:00"
    Then "Teacher 1" row "Most recent post" column of "peerforumreport_summary_table" table should contain "Sunday, 1 September 2019, 7:00"
    Then "Student 1" row "Earliest post" column of "peerforumreport_summary_table" table should contain "Wednesday, 27 March 2019, 4:00"
    Then "Student 1" row "Most recent post" column of "peerforumreport_summary_table" table should contain "Wednesday, 27 March 2019, 1:00"
    Then "Student 2" row "Earliest post" column of "peerforumreport_summary_table" table should contain "-"
    Then "Student 2" row "Most recent post" column of "peerforumreport_summary_table" table should contain "-"
