@mod @mod_peerforum
Feature: Posting to peerforums in a course with no groups behaves correctly

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity  | name               | intro                          | course | idnumber  | groupmode |
      | peerforum | Standard peerforum | Standard peerforum description | C1     | nogroups  | 0         |
      | peerforum | Visible peerforum  | Visible peerforum description  | C1     | visgroups | 2         |
      | peerforum | Separate peerforum | Separate peerforum description | C1     | sepgroups | 1         |

  Scenario: Teachers can post in standard peerforum
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Standard peerforum"
    When I click on "Add a new discussion topic" "link"
    Then I should not see "Post a copy to all groups"
    And I set the following fields to these values:
      | Subject | Teacher -> All participants |
      | Message | Teacher -> All participants |
    And I press "Post to peerforum"
    And I wait to be redirected
    And I should see "Teacher -> All participants"

  Scenario: Teachers can post in peerforum with separate groups
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Separate peerforum"
    When I click on "Add a new discussion topic" "link"
    Then I should not see "Post a copy to all groups"
    And I set the following fields to these values:
      | Subject | Teacher -> All participants |
      | Message | Teacher -> All participants |
    And I press "Post to peerforum"
    And I wait to be redirected
    And I should see "Teacher -> All participants"

  Scenario: Teachers can post in peerforum with visible groups
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Visible peerforum"
    When I click on "Add a new discussion topic" "link"
    Then I should not see "Post a copy to all groups"
    And I set the following fields to these values:
      | Subject | Teacher -> All participants |
      | Message | Teacher -> All participants |
    And I press "Post to peerforum"
    And I wait to be redirected
    And I should see "Teacher -> All participants"

  Scenario: Students can post in standard peerforum
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Standard peerforum"
    When I click on "Add a new discussion topic" "link"
    Then I should not see "Post a copy to all groups"
    And I set the following fields to these values:
      | Subject | Student -> All participants |
      | Message | Student -> All participants |
    And I press "Post to peerforum"
    And I wait to be redirected
    And I should see "Student -> All participants"

  Scenario: Students cannot post in peerforum with separate groups
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Separate peerforum"
    Then I should see "You are not able to create a discussion because you are not a member of any group."
    And I should not see "Add a new discussion topic"

  Scenario: Students cannot post in peerforum with visible groups
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Visible peerforum"
    Then I should see "You are not able to create a discussion because you are not a member of any group."
    And I should not see "Add a new discussion topic"
