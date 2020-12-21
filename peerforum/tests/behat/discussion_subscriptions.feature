@mod @mod_peerforum
Feature: A user can control their own subscription preferences for a discussion
  In order to receive notifications for things I am interested in
  As a user
  I need to choose my discussion subscriptions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                   |
      | student1 | Student   | One      | student.one@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: An optional peerforum can have discussions subscribed to
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    Then I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I subscribe to this peerforum
    And I should see "Student One will be notified of new posts in 'Test peerforum name'"
    And I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I unsubscribe from this peerforum
    And I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"

  @javascript
  Scenario: An automatic subscription peerforum can have discussions unsubscribed from
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Auto subscription                  |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    Then I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can unsubscribe from this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can unsubscribe from this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I unsubscribe from this peerforum
    And I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I subscribe to this peerforum
    And I should see "Student One will be notified of new posts in 'Test peerforum name'"
    And I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"

  @javascript
  Scenario: A user does not lose their preferences when a peerforum is switch from optional to automatic
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Subscription mode | Auto subscription |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    When I unsubscribe from this peerforum
    Then I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"

  @javascript
  Scenario: A user does not lose their preferences when a peerforum is switch from optional to automatic
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I can subscribe to this peerforum
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I click on "input[id^=subscription-toggle]" "css_element" in the "Test post subject one" "table_row"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Subscription mode | Auto subscription |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I can unsubscribe from this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    When I unsubscribe from this peerforum
    And I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And I can subscribe to this peerforum
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"

  Scenario: An optional peerforum prompts a user to subscribe to a discussion when posting unless they have already chosen not to subscribe
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I should see "Subscribe to this peerforum"
    And I reply "Test post subject one" post from "Test peerforum name" peerforum with:
      | Subject                 | Reply 1 to discussion 1               |
      | Message                 | Discussion contents 1, second message |
      | Discussion subscription | 1                                     |
    And I reply "Test post subject two" post from "Test peerforum name" peerforum with:
      | Subject                 | Reply 1 to discussion 1               |
      | Message                 | Discussion contents 1, second message |
      | Discussion subscription | 0                                     |
    And I follow "Test peerforum name"
    Then "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I follow "Test post subject one"
    And I follow "Reply"
    And the field "Discussion subscription" matches value "Send me notifications of new posts in this discussion"
    And I follow "Test peerforum name"
    And I follow "Test post subject two"
    And I follow "Reply"
    And the field "Discussion subscription" matches value "I don't want to be notified of new posts in this discussion"

  Scenario: An automatic peerforum prompts a user to subscribe to a discussion when posting unless they have already chosen not to subscribe
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Auto subscription                  |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject two |
      | Message | Test post message two |
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test peerforum name"
    And I should see "Unsubscribe from this peerforum"
    And I reply "Test post subject one" post from "Test peerforum name" peerforum with:
      | Subject                 | Reply 1 to discussion 1               |
      | Message                 | Discussion contents 1, second message |
      | Discussion subscription | 1                                     |
    And I reply "Test post subject two" post from "Test peerforum name" peerforum with:
      | Subject                 | Reply 1 to discussion 1               |
      | Message                 | Discussion contents 1, second message |
      | Discussion subscription | 0                                     |
    And I follow "Test peerforum name"
    Then "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And "Subscribe to this discussion" "checkbox" should exist in the "Test post subject two" "table_row"
    And I follow "Test post subject one"
    And I follow "Reply"
    And the field "Discussion subscription" matches value "Send me notifications of new posts in this discussion"
    And I follow "Test peerforum name"
    And I follow "Test post subject two"
    And I follow "Reply"
    And the field "Discussion subscription" matches value "I don't want to be notified of new posts in this discussion"

  Scenario: A guest should not be able to subscribe to a discussion
    Given I am on site homepage
    And I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name | Test peerforum name                |
      | PeerForum type | Standard peerforum for general use |
      | Description    | Test peerforum description         |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I log out
    When I log in as "guest"
    And I follow "Test peerforum name"
    Then "Subscribe to this discussion" "checkbox" should not exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should not exist in the "Test post subject one" "table_row"
    And I follow "Test post subject one"
    And "Subscribe to this discussion" "checkbox" should not exist
    And "Unsubscribe from this discussion" "checkbox" should not exist

  Scenario: A user who is not logged in should not be able to subscribe to a discussion
    Given I am on site homepage
    And I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name | Test peerforum name                |
      | PeerForum type | Standard peerforum for general use |
      | Description    | Test peerforum description         |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I log out
    When I follow "Test peerforum name"
    Then "Subscribe to this discussion" "checkbox" should not exist in the "Test post subject one" "table_row"
    And "Unsubscribe from this discussion" "checkbox" should not exist in the "Test post subject one" "table_row"
    And I follow "Test post subject one"
    And "Subscribe to this discussion" "checkbox" should not exist
    And "Unsubscribe from this discussion" "checkbox" should not exist

  Scenario: A user can toggle their subscription preferences when viewing a discussion
    Given I add a "PeerForum" to section "1" and I fill the form with:
      | PeerForum name    | Test peerforum name                |
      | PeerForum type    | Standard peerforum for general use |
      | Description       | Test peerforum description         |
      | Subscription mode | Optional subscription              |
    And I add a new discussion to "Test peerforum name" peerforum with:
      | Subject | Test post subject one |
      | Message | Test post message one |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test peerforum name"
    Then "Subscribe to this peerforum" "link" should exist in current page administration
    And I follow "Test post subject one"
    And "You are not subscribed to this discussion. Click to subscribe" "link" should exist
    And I follow "Test peerforum name"
    And I navigate to "Subscribe to this peerforum" in current page administration
    And I should see "Student One will be notified of new posts in 'Test peerforum name'"
    And "Unsubscribe from this peerforum" "link" should exist in current page administration
    And I follow "Test post subject one"
    And "You are subscribed to this discussion. Click to unsubscribe" "link" should exist
    And I follow "You are subscribed to this discussion. Click to unsubscribe"
    And I should see "Student One will NOT be notified of new posts in 'Test post subject one' of 'Test peerforum name'"
    And I follow "Test post subject one"
    And "You are not subscribed to this discussion. Click to subscribe" "link" should exist
    And I follow "Test peerforum name"
    And I navigate to "Unsubscribe from this peerforum" in current page administration
    And I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And "Subscribe to this peerforum" "link" should exist in current page administration
    And I follow "Test post subject one"
    And "You are not subscribed to this discussion. Click to subscribe" "link" should exist
    And I follow "You are not subscribed to this discussion. Click to subscribe"
    And I should see "Student One will be notified of new posts in 'Test post subject one' of 'Test peerforum name'"
    And "Unsubscribe from this discussion" "checkbox" should exist in the "Test post subject one" "table_row"
    And I follow "Test peerforum name"
    And I navigate to "Subscribe to this peerforum" in current page administration
    And I should see "Student One will be notified of new posts in 'Test peerforum name'"
    And "Unsubscribe from this peerforum" "link" should exist in current page administration
    And I follow "Test post subject one"
    And "You are subscribed to this discussion. Click to unsubscribe" "link" should exist
    And I follow "Test peerforum name"
    And I navigate to "Unsubscribe from this peerforum" in current page administration
    And I should see "Student One will NOT be notified of new posts in 'Test peerforum name'"
    And "Subscribe to this peerforum" "link" should exist in current page administration
    And I follow "Test post subject one"
    And "You are not subscribed to this discussion. Click to subscribe" "link" should exist
