@filter @filter_studiolms
Feature: The course map
  In order to see the sections of a course as a path
  As a student
  I need the [COURSEMAP] marker to draw the sections of the course

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the "studiolms" filter is "on"
    And the following "activities" exist:
      | activity | name      | course | section | content             | contentformat |
      | page     | Map page  | C1     | 1       | <p>[COURSEMAP]</p>  | 1             |
      | page     | Second    | C1     | 2       | <p>Second page</p>  | 1             |
      | page     | Third     | C1     | 3       | <p>Third page</p>   | 1             |

  Scenario: A student sees the sections of the course as a path
    When I am on the "Map page" "page activity" page logged in as "student1"
    Then I should see "Course map"
    And ".slw-map-step" "css_element" should exist
    And I should not see "[COURSEMAP]"

  Scenario: The map offers the options the author typed
    Given the following "activities" exist:
      | activity | name     | course | section | content                             | contentformat |
      | page     | Progress | C1     | 1       | <p>[COURSEMAP current=progress]</p> | 1             |
    When I am on the "Progress" "page activity" page logged in as "student1"
    Then I should see "Course map"
    And I should not see "[COURSEMAP current=progress]"

  Scenario: The filter turned off leaves no map
    Given the "studiolms" filter is "off"
    When I am on the "Map page" "page activity" page logged in as "student1"
    Then ".slw-map" "css_element" should not exist
