@filter @filter_studiolms
Feature: The participation bar of a forum
  In order to see how much of the class has taken part in a forum
  As a student or a teacher
  I need the [FORUMSTATS] marker in the description of a forum to show a bar, without exposing anyone

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
      | student3 | Student   | Three    |
      | student4 | Student   | Four     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the "studiolms" filter is "on"
    And the following "activities" exist:
      | activity | name   | course | intro               |
      | forum    | Debate | C1     | <p>[FORUMSTATS]</p> |

  Scenario: A student sees how much of the class has taken part
    Given the following config values are set as admin:
      | minclasssize | 3 | filter_studiolms |
    And the following "mod_forum > discussions" exist:
      | forum  | course | user     | name        | message |
      | Debate | C1     | student1 | First topic | Hello   |
    When I am on the "Debate" "forum activity" page logged in as "student2"
    Then I should see "Participation in this forum"
    And I should see "25%"
    And I should see "75%"
    And I should see "1 of 4"
    And I should not see "[FORUMSTATS]"

  Scenario: The bar follows the messages the class sends
    Given the following config values are set as admin:
      | minclasssize | 3 | filter_studiolms |
    And the following "mod_forum > discussions" exist:
      | forum  | course | user     | name         | message |
      | Debate | C1     | student1 | First topic  | Hello   |
      | Debate | C1     | student2 | Second topic | Hi      |
    When I am on the "Debate" "forum activity" page logged in as "student3"
    Then I should see "50%"
    And I should see "2 of 4"

  Scenario: A class smaller than the minimum is hidden from the students
    Given the following config values are set as admin:
      | minclasssize | 5 | filter_studiolms |
    When I am on the "Debate" "forum activity" page logged in as "student1"
    Then I should not see "Participation in this forum"
    And I should not see "[FORUMSTATS]"

  Scenario: The teacher is told why the students do not see the bar
    Given the following config values are set as admin:
      | minclasssize | 5 | filter_studiolms |
    When I am on the "Debate" "forum activity" page logged in as "teacher1"
    Then I should see "Hidden from students: the class has fewer than 5 students."

  Scenario: The marker in an activity that is not a forum shows nothing
    Given the following "activities" exist:
      | activity | name      | course | content            | contentformat |
      | page     | Not forum | C1     | <p>[FORUMSTATS]</p> | 1             |
    When I am on the "Not forum" "page activity" page logged in as "student1"
    Then I should not see "[FORUMSTATS]"
    And I should not see "Participation in this forum"

  @javascript
  Scenario: A teacher who types the marker in a new forum sees the bar
    Given the following config values are set as admin:
      | minclasssize | 3 | filter_studiolms |
    And I log in as "teacher1"
    And I add a "forum" activity to course "Course 1" section "1" and I fill the form with:
      | Forum name  | Typed forum    |
      | Description | [FORUMSTATS]   |
    When I am on the "Typed forum" "forum activity" page
    Then I should see "Participation in this forum"
    And I should not see "[FORUMSTATS]"
