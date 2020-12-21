@mod @mod_peerforum @block_recent_activity
Feature: Users can see the relevant recent peerforum posts from the recent activity block
  In order to quickly see the updates from peerforums in my course
  As a user
  I need to be able to see the recent peerforum posts in the recent activity block

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G2    |
      | teacher1 | G1    |
      | teacher1 | G2    |
    And the following "activities" exist:
      | activity  | name                      | intro                       | course | idnumber   | type    | groupmode | visible |
      | peerforum | Separate groups peerforum | Separate groups description | C1     | peerforum1 | general | 1         | 1       |
      | peerforum | Visible groups peerforum  | Visible groups description  | C1     | peerforum2 | general | 2         | 1       |
      | peerforum | Standard peerforum        | Standard description        | C1     | peerforum3 | general | 0         | 1       |
      | peerforum | Hidden peerforum          | Hidden description          | C1     | peerforum4 | general | 0         | 0       |
      | peerforum | Q&A peerforum             | Q&A description             | C1     | peerforum5 | qanda   | 0         | 1       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Recent activity" block

  Scenario: Recent peerforum activity with separate group discussion
    Given I add a new discussion to "Separate groups peerforum" peerforum with:
      | Subject | Group 1 separate discussion |
      | Message | Group 1 members only        |
      | Group   | Group 1                     |
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should see "Group 1 separate discussion" in the "Recent activity" "block"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should not see "Group 1 separate discussion" in the "Recent activity" "block"

  Scenario: Recent peerforum activity with visible groups discussion
    Given I add a new discussion to "Visible groups peerforum" peerforum with:
      | Subject | Group 1 visible discussion   |
      | Message | Not just for group 1 members |
      | Group   | Group 1                      |
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should see "Group 1 visible discussion" in the "Recent activity" "block"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "Group 1 visible discussion" in the "Recent activity" "block"

  Scenario: Recent peerforum activity with recent post as a private reply
    Given I add a new discussion to "Standard peerforum" peerforum with:
      | Subject | Standard peerforum discussion        |
      | Message | Discuss anything under the sun here! |
    And I reply "Standard peerforum discussion" post from "Standard peerforum" peerforum with:
      | Subject         | Teacher's private reply |
      | Message         | This is a private reply |
      | Reply privately | 1                       |
    And I am on "Course 1" course homepage
    And I should see "Standard peerforum discussion" in the "Recent activity" "block"
    And I should see "Teacher's private reply" in the "Recent activity" "block"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Standard peerforum discussion" in the "Recent activity" "block"
    But I should not see "Teacher's private reply" in the "Recent activity" "block"

  Scenario: Recent peerforum activity with recent post in a hidden peerforum
    Given I add a new discussion to "Hidden peerforum" peerforum with:
      | Subject | Hidden discussion |
      | Message | Should be hidden! |
    And I am on "Course 1" course homepage
    And I should see "Hidden discussion" in the "Recent activity" "block"
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should not see "Hidden discussion" in the "Recent activity" "block"

  Scenario: Recent peerforum activity with question and answer peerforum
    Given I add a new question to "Q&A peerforum" peerforum with:
      | Subject | The egg vs the chicken                    |
      | Message | Which came first? The egg or the chicken? |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I reply "The egg vs the chicken" post from "Q&A peerforum" peerforum with:
      | Subject | Student 1's answer |
      | Message | The egg!           |
    And I am on "Course 1" course homepage
    And I should see "The egg vs the chicken" in the "Recent activity" "block"
    And I should see "Student 1's answer" in the "Recent activity" "block"
    And I log out
    And I log in as "admin"
    And the following config values are set as admin:
      | maxeditingtime | 1 |
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "The egg vs the chicken" in the "Recent activity" "block"
    But I should not see "Student 1's answer" in the "Recent activity" "block"
    And I reply "The egg vs the chicken" post from "Q&A peerforum" peerforum with:
      | Subject | Student 2's answer |
      | Message | The chicken, duh!  |
    And I wait "2" seconds
    And I am on "Course 1" course homepage
    And I should see "Student 1's answer" in the "Recent activity" "block"
    And I should see "Student 2's answer" in the "Recent activity" "block"

  Scenario: Recent peerforum activity with timed discussion
    Given I add a new discussion to "Standard peerforum" peerforum with:
      | Subject          | Timed discussion                                  |
      | Message          | Discuss anything under the sun here... no more!!! |
      | timeend[enabled] | 1                                                 |
      | timeend[year]    | 2020                                              |
      | timeend[month]   | 1                                                 |
      | timeend[day]     | 1                                                 |
    And I am on "Course 1" course homepage
    And I should see "Timed discussion" in the "Recent activity" "block"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "Timed discussion" in the "Recent activity" "block"
