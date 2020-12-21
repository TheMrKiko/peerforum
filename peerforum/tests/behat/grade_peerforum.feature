@mod @mod_peerforum @core_grades
Feature: I can grade a students interaction across a peerforum
  In order to assess a student's contributions
  As a teacher
  I can assign grades to a student based on their contributions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Course 1 | C1        | weeks  | 5           |
    And the following "grade categories" exist:
      | fullname | course |
      | Tutor    | C1     |
      | Peers    | C1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "scales" exist:
      | name         | scale                                     |
      | Test Scale 1 | Disappointing, Good, Very good, Excellent |
    And I log in as "teacher1"
    And I change window size to "large"
    And I am on "Course 1" course homepage
    And I turn editing mode on

  @javascript
  Scenario: Ensure that peerforum grade settings do not leak to Ratings
    Given I add a "PeerForum" to section "1"
    And I expand all fieldsets
    And I set the following fields to these values:
      | PeerForum name | Test PeerForum 1 |
      | Description    | Test             |

    # Fields should be hidden when grading is not set.
    When I set the field "Whole peerforum grading > Type" to "None"
    Then "Whole peerforum grading > Grade to pass" "field" should not be visible
    And "Whole peerforum grading > Grade category" "field" should not be visible
    And "Whole peerforum grading > Maximum grade" "field" should not be visible
    And "Ratings > Grade to pass" "field" should not be visible
    And "Ratings > Grade category" "field" should not be visible
    And "Ratings > Maximum grade" "field" should not be visible

    # Only Whole peerforum grading fields should be visible.
    When I set the field "Whole peerforum grading > Type" to "Point"
    Then "Whole peerforum grading > Grade to pass" "field" should be visible
    And "Whole peerforum grading > Grade category" "field" should be visible
    And "Whole peerforum grading > Maximum grade" "field" should be visible
    But "Ratings > Grade to pass" "field" should not be visible
    And "Ratings > Grade category" "field" should not be visible
    And "Ratings > Maximum grade" "field" should not be visible

    # Save some values.
    Given I set the field "Whole peerforum grading > Maximum grade" to "10"
    And I set the field "Whole peerforum grading > Grade category" to "Tutor"
    And I set the field "Whole peerforum grading > Grade to pass" to "4"
    When I press "Save and return to course"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on

    # There shouldn't be any Ratings grade item.
    Then I should see "Whole peerforum grade"
    But I should not see "Rating grade"

    # The values saved should be reflected here.
    Given I click on "Edit  peerforum Whole peerforum grade for Test PeerForum 1" "link"
    When I expand all fieldsets
    Then the field "Maximum grade" matches value "10"
    Then the field "Grade to pass" matches value "4"
    And I should see "Tutor" in the "Parent category" "fieldset"

  @javascript
  Scenario: Ensure that Ratings settings do not leak to PeerForum grading
    Given I add a "PeerForum" to section "1"
    And I expand all fieldsets
    And I set the following fields to these values:
      | PeerForum name | Test PeerForum 1 |
      | Description    | Test             |

    # Fields should be hidden when grading is not set.
    When I set the field "Ratings > Aggregate type" to "No ratings"
    Then "Ratings > Type" "field" should not be visible
    And "Ratings > Grade to pass" "field" should not be visible
    And "Ratings > Grade category" "field" should not be visible
    And "Ratings > Maximum grade" "field" should not be visible
    And "Whole peerforum grading > Grade to pass" "field" should not be visible
    And "Whole peerforum grading > Grade category" "field" should not be visible
    And "Whole peerforum grading > Maximum grade" "field" should not be visible

    # Set to "Count of ratings"
    When I set the field "Ratings > Aggregate type" to "Count of ratings"
    Then "Ratings > Type" "field" should be visible
    When I set the field "Ratings > Type" to "None"
    Then "Ratings > Grade to pass" "field" should not be visible
    And "Ratings > Grade category" "field" should not be visible
    And "Ratings > Maximum grade" "field" should not be visible
    And "Whole peerforum grading > Grade to pass" "field" should not be visible
    And "Whole peerforum grading > Grade category" "field" should not be visible
    And "Whole peerforum grading > Maximum grade" "field" should not be visible

    # Use point grading
    When I set the field "Ratings > Type" to "Point"
    Then "Ratings > Grade to pass" "field" should be visible
    And "Ratings > Grade category" "field" should be visible
    And "Ratings > Maximum grade" "field" should be visible
    And "Whole peerforum grading > Grade to pass" "field" should not be visible
    And "Whole peerforum grading > Grade category" "field" should not be visible
    And "Whole peerforum grading > Maximum grade" "field" should not be visible

    # Save some values.
    Given I set the field "Ratings > Maximum grade" to "10"
    And I set the field "Ratings > Grade category" to "Tutor"
    And I set the field "Ratings > Grade to pass" to "4"
    When I press "Save and return to course"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on

    # There shouldn't be any Whole peerforum grade gradeitem.
    Then I should see "Rating grade"
    But I should not see "Whole peerforum grade"

    # The values saved should be reflected here.
    Given I click on "Edit  peerforum Rating grade for Test PeerForum 1" "link"
    When I expand all fieldsets
    Then the field "Maximum grade" matches value "10"
    Then the field "Grade to pass" matches value "4"
    And I should see "Tutor" in the "Parent category" "fieldset"

  Scenario: Setting both a rating and a whole peerforum grade does not bleed
    Given I add a "PeerForum" to section "1"
    And I expand all fieldsets
    And I set the following fields to these values:
      | PeerForum name | Test PeerForum 1 |
      | Description    | Test             |

    When I set the field "Ratings > Aggregate type" to "Count of ratings"
    And I set the field "Ratings > Type" to "Point"
    And I set the field "Ratings > Maximum grade" to "100"
    And I set the field "Ratings > Grade category" to "Peers"
    And I set the field "Ratings > Grade to pass" to "40"
    And I set the field "Whole peerforum grading > Type" to "Point"
    And I set the field "Whole peerforum grading > Maximum grade" to "10"
    And I set the field "Whole peerforum grading > Grade category" to "Tutor"
    And I set the field "Whole peerforum grading > Grade to pass" to "4"
    And I press "Save and return to course"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on

    # There shouldn't be any Whole peerforum grade gradeitem.
    Then I should see "Rating grade"
    And I should see "Whole peerforum grade"

    # The values saved should be reflected here.
    Given I click on "Edit  peerforum Rating grade for Test PeerForum 1" "link"
    When I expand all fieldsets
    Then the field "Maximum grade" matches value "100"
    Then the field "Grade to pass" matches value "40"
    And I should see "Peers" in the "Parent category" "fieldset"
    And I press "cancel"

    Given I click on "Edit  peerforum Whole peerforum grade for Test PeerForum 1" "link"
    When I expand all fieldsets
    Then the field "Maximum grade" matches value "10"
    Then the field "Grade to pass" matches value "4"
    And I should see "Tutor" in the "Parent category" "fieldset"
