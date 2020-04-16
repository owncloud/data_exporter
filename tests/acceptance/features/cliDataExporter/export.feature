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

  Scenario Outline: Multiple deleted file with almost similar name should be contained in an export
    Given user "user0" has created folder "testFolder"
    And user "user0" uploads file with content "hello" to "/testFolder/testfile.txt" using the WebDAV API
    And user "user0" uploads file with content "first file in trash bin" to "/testFolder/<firstFileName>" using the WebDAV API
    And user "user0" uploads file with content "second file in trash bin" to "/testFolder/<secondFileName>" using the WebDAV API
    And user "user0" has deleted file "/testFolder/<firstFileName>"
    And user "user0" has deleted file "/testFolder/<secondFileName>"
    And as "user0" file "<firstFileName>" should exist in the trashbin
    And as "user0" file "<secondFileName>" should exist in the trashbin
    When user "user0" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "/testFolder/testfile.txt" with content "hello"
    And the last export should contain file "<firstFileName>" with content "first file in trash bin" in trashbin
    And the last export should contain file "<secondFileName>" with content "second file in trash bin" in trashbin
    Examples:
      | firstFileName     | secondFileName     |
      | trashbinFile.txt  | trashbinFil.txt    |
      | trashbinFile.txt  | trashbinFile.txt.a |
      | trashbinFile.txtt | trashbinFile.txt   |
