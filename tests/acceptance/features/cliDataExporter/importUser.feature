@cli
Feature: An administrator wants to import a user using the commandline
  Background:
    Given using new dav path

  Scenario: Import a user and his files only
    When a user is imported from path "simpleExport/userfoo" using the occ command
    And the administrator changes the password of user "userfoo" to "123456" using the provisioning API
    Then user "userfoo" should exist
    And as "userfoo" file "welcome.txt" should exist
    And as "userfoo" folder "AFolder" should exist
    And as "userfoo" file "AFolder/afile.txt" should exist
