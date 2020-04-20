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

  Scenario: Restore and rename files in trashbin after import
    Given a user has been imported from path "trashbinExport/usertrash" using the occ command
    And the administrator has changed the password of user "usertrash" to "123456"
    And user "usertrash" has restored the folder with original path "AFolder/DeletedFolder"
    And user "usertrash" has restored the file with original path "AFolder/DeletedFile.txt"
    And as "usertrash" file "AFolder/DeletedFolder/fileinfolder.txt" should exist
    And as "usertrash" file "AFolder/DeletedFile.txt" should exist
    When user "usertrash" moves file "AFolder/DeletedFile.txt" to "AFolder/RestoredDeletedFile.txt" using the WebDAV API
    And user "usertrash" moves folder "AFolder/DeletedFolder" to "AFolder/RestoredDeletedFolder" using the WebDAV API
    Then the HTTP status code should be "201"
    And as "usertrash" folder "AFolder/RestoredDeletedFolder" should exist
    And as "usertrash" file "AFolder/RestoredDeletedFile.txt" should exist
