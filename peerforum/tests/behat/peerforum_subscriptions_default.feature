@mod @mod_peerforum
Feature: A user can control their default discussion subscription settings
  In order to automatically subscribe to discussions
  As a user
  I can choose my default subscription preference

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                   | autosubscribe |
      | student1 | Student   | One      | student.one@example.com | 1             |
      | student2 | Student   | Two      | student.one@example.com | 0             |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Creating a new discussion in an optional peerforum follows user preferences
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    When I click on "Add a new discussion topic" "link"
    And I click on "Advanced" "button"
    Then "input[name=discussionsubscribe][checked=checked]" "css_element" should exist
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I click on "Add a new discussion topic" "link"
    And I click on "Advanced" "button"
    And "input[name=discussionsubscribe]:not([checked=checked])" "css_element" should exist

  Scenario: Replying to an existing discussion in an optional peerforum follows user preferences
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I follow "Test post subject"
    When I follow "Reply"
    Then "input[name=discussionsubscribe][checked=checked]" "css_element" should exist
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I follow "Test post subject"
    And I follow "Reply"
    And "input[name=discussionsubscribe]:not([checked=checked])" "css_element" should exist

  Scenario: Creating a new discussion in an automatic peerforum follows peerforum subscription
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Auto subscription                  |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    When I click on "Add a new discussion topic" "link"
    And I click on "Advanced" "button"
    Then "input[name=discussionsubscribe][checked=checked]" "css_element" should exist
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I click on "Add a new discussion topic" "link"
    And I click on "Advanced" "button"
    And "input[name=discussionsubscribe][checked=checked]" "css_element" should exist

  Scenario: Replying to an existing discussion in an automatic peerforum follows peerforum subscription
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I follow "Test post subject"
    When I follow "Reply"
    Then "input[name=discussionsubscribe][checked=checked]" "css_element" should exist
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I follow "Test post subject"
    And I follow "Reply"
    And "input[name=discussionsubscribe]:not([checked=checked])" "css_element" should exist

  @javascript
  Scenario: Replying to an existing discussion in an automatic peerforum which has been unsubscribed from follows user preferences
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Auto subscription                  |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject |
      | Message | Test post message |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject" "table_row"
    And I follow "Test post subject"
    When I follow "Reply"
    And I click on "Advanced" "button"
    And "input[name=discussionsubscribe][checked]" "css_element" should exist
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject" "table_row"
    And I follow "Test post subject"
    And I follow "Reply"
    And I click on "Advanced" "button"
    And "input[name=discussionsubscribe]:not([checked])" "css_element" should exist
