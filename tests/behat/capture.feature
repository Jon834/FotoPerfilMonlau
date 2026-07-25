@local @local_profilephoto
Feature: Capture profile photos for students
  In order to keep student profile photos up to date quickly
  As a photographer
  I need to find a student, capture a photo, and save it to their real Moodle profile

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | laura    | Laura     | Garcia   | laura@example.com    | S001     |
      | marc     | Marc      | Puig     | marc@example.com     | S002     |
      | photog   | Foto      | Grafo    | photog@example.com   | OP001    |
      | outsider | Out       | Sider    | outsider@example.com | OP002    |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | laura | C1     | student |
      | marc  | C1     | student |
    And the following "roles" exist:
      | name        | shortname   |
      | Photographer | photographer |
    And the following "permission overrides" exist:
      | capability                        | permission | role         | contextlevel | reference |
      | local/profilephoto:view           | Allow      | photographer | System       |           |
      | local/profilephoto:searchusers    | Allow      | photographer | System       |           |
      | local/profilephoto:capture        | Allow      | photographer | System       |           |
      | local/profilephoto:updatepicture  | Allow      | photographer | System       |           |
      | local/profilephoto:replaceexisting| Allow      | photographer | System       |           |
      | local/profilephoto:viewallusers   | Allow      | photographer | System       |           |
      | local/profilephoto:exportsession  | Allow      | photographer | System       |           |
    And the following "role assigns" exist:
      | user   | role         | contextlevel | reference |
      | photog | photographer | System       |           |

  Scenario: A photographer finds a student, captures a photo and saves it to the real profile
    Given I log in as "photog"
    And I am on "local/profilephoto/index.php" page
    When I set the field "Nombre, correo, usuario o idnumber…" to "Garcia"
    # The live search is debounced client-side; Behat's Mink driver still
    # sees the AJAX-rendered result once it appears.
    And I click on "Laura Garcia" "text"
    And I attach the file "tests/fixtures/sample_face.jpg" to "Fotografía"
    And I press "Guardar y siguiente"
    Then I should see "Fotografía guardada correctamente para Laura Garcia"
    And I am on the "laura" "user > profile" page
    And I should see "Laura Garcia"

  Scenario: A user without local/profilephoto:view cannot open the capture screen
    Given I log in as "outsider"
    When I am on "local/profilephoto/index.php" page
    Then I should see "Sorry, but you do not currently have permissions to do that"

  Scenario: A photographer cannot update a student outside their scope
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | farstudent | Far     | Away     | far@example.com   |
    And the following "roles" exist:
      | name             | shortname       |
      | Scoped photographer | scopedphotographer |
    And the following "permission overrides" exist:
      | capability                       | permission | role                | contextlevel | reference |
      | local/profilephoto:view          | Allow      | scopedphotographer  | System       |           |
      | local/profilephoto:searchusers   | Allow      | scopedphotographer  | System       |           |
      | local/profilephoto:capture       | Allow      | scopedphotographer  | Course       | C1        |
      | local/profilephoto:updatepicture | Allow      | scopedphotographer  | System       |           |
    And the following "users" exist:
      | username | firstname | lastname |
      | scoped   | Scoped    | Operator |
    And the following "role assigns" exist:
      | user   | role               | contextlevel | reference |
      | scoped | scopedphotographer | System       |           |
    When I log in as "scoped"
    And I am on "local/profilephoto/index.php" page
    And I set the field "Nombre, correo, usuario o idnumber…" to "Away"
    Then I should not see "Far Away"

  Scenario: Repeating a photo before saving does not touch the profile
    Given I log in as "photog"
    And I am on "local/profilephoto/index.php" page
    When I set the field "Nombre, correo, usuario o idnumber…" to "Marc"
    And I click on "Marc Puig" "text"
    And I attach the file "tests/fixtures/sample_face.jpg" to "Fotografía"
    And I press "Repetir"
    Then I should see "Marc Puig"
    But I should not see "Fotografía guardada correctamente"
