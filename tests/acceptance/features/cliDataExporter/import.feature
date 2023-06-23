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
    When a user is imported from path "trashbinExport/usertrash" using the occ command
    And the administrator changes the password of user "usertrash" to "123456" using the provisioning API
    And user "usertrash" restores the file with original path "AFolder/DeletedFolder" using the trashbin API
    And user "usertrash" restores the file with original path "AFolder/DeletedFile.txt" using the trashbin API
    Then as "usertrash" file "AFolder/DeletedFolder/fileinfolder.txt" should exist
    And as "usertrash" file "AFolder/DeletedFile.txt" should exist
    And the content of file "AFolder/DeletedFolder/fileinfolder.txt" for user "usertrash" should be "text in file in deleted folder"
    And the content of file "AFolder/DeletedFile.txt" for user "usertrash" should be "text in deleted file"


  Scenario: Restore and rename files in trashbin after user import
    Given a user has been imported from path "trashbinExport/usertrash" using the occ command
    And the administrator has changed the password of user "usertrash" to "123456"
    When user "usertrash" restores the folder with original path "AFolder/DeletedFolder" using the trashbin API
    And user "usertrash" restores the file with original path "AFolder/DeletedFile.txt" using the trashbin API
    Then as "usertrash" file "AFolder/DeletedFolder/fileinfolder.txt" should exist
    And as "usertrash" file "AFolder/DeletedFile.txt" should exist
    When user "usertrash" moves file "AFolder/DeletedFile.txt" to "AFolder/RestoredDeletedFile.txt" using the WebDAV API
    And user "usertrash" moves folder "AFolder/DeletedFolder" to "AFolder/RestoredDeletedFolder" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "usertrash" folder "AFolder/RestoredDeletedFolder" should exist
    And as "usertrash" file "AFolder/RestoredDeletedFile.txt" should exist


  Scenario Outline: Share files after import
    Given a user has been imported from path "trashbinExport/usertrash" using the occ command
    And the administrator has changed the password of user "usertrash" to "123456"
    And a user has been imported from path "simpleExport/userfoo" using the occ command
    And the administrator has changed the password of user "userfoo" to "123456"
    And using OCS API version "<ocs_api_version>"
    When user "userfoo" shares file "AFolder/afile.txt" with user "usertrash" with permissions "read" using the sharing API
    Then the OCS status code should be "<ocs_status_code>"
    And the HTTP status code should be "200"
    And as "usertrash" file "/afile.txt" should exist
    And the content of file "afile.txt" for user "usertrash" should be "This is a File"
    Examples:
      | ocs_api_version | ocs_status_code |
      | 1               | 100             |
      | 2               | 200             |


  Scenario Outline: Import file with special characters in its name
    When a user is imported from path "simpleExport/testUser" using the occ command
    Then user "testUser" should exist
    And as "testUser" folder "newFolder" should exist
    And as "testUser" file "newFolder/<filename>" should exist
    Examples:
      | filename            |
      | 'quotes1'           |
      | strängé नेपाली file |


  Scenario: Import files from multi level sub-folder
    When a user is imported from path "simpleExport/testUser" using the occ command
    Then user "testUser" should exist
    And as "testUser" file "newFolder/testFolder/T1/T2/afile.txt" should exist
    And the content of file "newFolder/testFolder/T1/T2/afile.txt" for user "testUser" should be "This is a File"


  Scenario: Restore deleted file from multiple level of sub-folder
    Given a user has been imported from path "simpleExport/testUser" using the occ command
    When user "testUser" restores the file with original path "/newFolder/trashFolder/T1/T2/fileinfolder.txt" to "/testFile.txt" using the trashbin API
    Then the HTTP status code should be "201"
    And as "testUser" file "/testFile.txt" should exist
    And the content of file "/testFile.txt" for user "testUser" should be "text in file in deleted folder"


  Scenario: An attempt to import from a non-existent export should fail
    When a user is imported from path "simpleExport/unknown" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text "user.json not found"

  @issue-210
  Scenario: importing a user with empty trash bin
    When a user is imported from path "emptyTrashbinExport/usertrash" using the occ command
    Then the command should have been successful
    And user "usertrash" should exist
    And as "usertrash" folder "AFolder" should exist
    And as "usertrash" file "AFolder/fileInFolder.txt" should exist

  @issue-210
  Scenario: importing without trash bin
    When a user is imported from path "noTrashbinImport/testuser1" using the occ command
    Then the command should have been successful
    And user "testuser1" should exist
    And as "testuser1" file "welcome.txt" should exist
