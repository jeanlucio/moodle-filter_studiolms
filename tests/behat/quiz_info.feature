@filter @filter_studiolms
Feature: The rules of a quiz
  In order to know the rules of a quiz before starting it
  As a student
  I need the [QUIZINFO] marker in the description of a quiz to show its rules as they are now

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the "studiolms" filter is "on"

  Scenario: A quiz with no limit of attempts says so
    Given the following "activities" exist:
      | activity | name | course | intro               |
      | quiz     | Quiz | C1     | <p>[QUIZINFO]</p>   |
    When I am on the "Quiz" "quiz activity" page logged in as "student1"
    Then I should see "Quiz rules"
    And I should see "Attempts"
    And I should see "Unlimited"
    And I should not see "[QUIZINFO]"

  Scenario: The attempts and the time of the quiz are shown
    Given the following "activities" exist:
      | activity | name | course | intro             | attempts | timelimit |
      | quiz     | Quiz | C1     | <p>[QUIZINFO]</p> | 2        | 1800      |
    When I am on the "Quiz" "quiz activity" page logged in as "student1"
    Then I should see "2" in the ".slw-quiz" "css_element"
    And I should see "30 mins" in the ".slw-quiz" "css_element"

  Scenario: The rules follow a change the teacher makes later
    Given the following "activities" exist:
      | activity | name | course | intro             | attempts |
      | quiz     | Quiz | C1     | <p>[QUIZINFO]</p> | 2        |
    And I am on the "Quiz" "quiz activity" page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Attempts allowed | 5 |
    And I press "Save and display"
    And I log out
    When I am on the "Quiz" "quiz activity" page logged in as "student1"
    Then I should see "5" in the ".slw-quiz" "css_element"

  Scenario: The marker in an activity that is not a quiz shows nothing
    Given the following "activities" exist:
      | activity | name     | course | content            | contentformat |
      | page     | Not quiz | C1     | <p>[QUIZINFO]</p>  | 1             |
    When I am on the "Not quiz" "page activity" page logged in as "student1"
    Then I should not see "[QUIZINFO]"
    And I should not see "Quiz rules"
