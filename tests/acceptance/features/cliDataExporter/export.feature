@cli
Feature: An administrator wants to export the files of his user using
         the commandline

  Background:
    Given using new dav path
    And user "user0" has been created with default attributes and skeleton files

  Scenario: An uploaded file should be contained in an export.
    Given user "user0" uploads file with content "hello" to "testfile.txt" using the WebDAV API
    When user "user0" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "testfile.txt" with content "hello"

  Scenario: Deleted file should be contained in an export
    Given user "user0" has created folder "testFolder"
    And user "user0" uploads file with content "file in trash bin" to "/testFolder/trashbinFile.txt" using the WebDAV API
    And user "user0" uploads file with content "hello" to "/testFolder/testfile.txt" using the WebDAV API
    And user "user0" has deleted file "/testFolder/trashbinFile.txt"
    And as "user0" file "trashbinFile.txt" should exist in the trashbin
    When user "user0" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "/testFolder/testfile.txt" with content "hello"
    And the last export should contain file "trashbinFile.txt" with content "file in trash bin" in trashbin