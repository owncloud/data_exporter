@cli @files_trashbin-app-required
Feature: An administrator wants to import a user using the commandline
  Background:
    Given using new dav path
    And the administrator has enabled DAV tech_preview

  Scenario: Import files only
    When a user is imported from path "simpleExport/userfoo" using the occ command
    And the administrator changes the password of user "userfoo" to "123456" using the provisioning API
    Then user "userfoo" should exist
    And as "userfoo" file "welcome.txt" should exist
    And as "userfoo" folder "AFolder" should exist
    And as "userfoo" file "AFolder/afile.txt" should exist

  Scenario: Import with trash-bin
    When a user is imported from path "trashbinExport/userbar" using the occ command
    And the administrator changes the password of user "userbar" to "123456" using the provisioning API
    When user "userbar" restores the file with original path "AFolder/L1/L2/DeletedFolder" using the trashbin API
    And user "userbar" restores the file with original path "AFolder/L1/DeletedFile" using the trashbin API
    Then as "userbar" file "AFolder/L1/L2/DeletedFolder/fileinfolder.txt" should exist
    And as "userbar" file "AFolder/L1/DeletedFile" should exist


