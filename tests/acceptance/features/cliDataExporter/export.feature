@cli
Feature: An administrator wants to export the files of his user using
  the commandline

  Background:
    Given using new dav path
    And user "Alice" has been created with default attributes and small skeleton files


  Scenario: An uploaded file should be contained in an export.
    Given user "Alice" uploads file with content "hello" to "testfile.txt" using the WebDAV API
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "testfile.txt" with content "hello"


  Scenario: Deleted file should be contained in an export
    Given user "Alice" has created folder "testFolder"
    And user "Alice" uploads file with content "file in trash bin" to "/testFolder/trashbinFile.txt" using the WebDAV API
    And user "Alice" uploads file with content "hello" to "/testFolder/testfile.txt" using the WebDAV API
    And user "Alice" has deleted file "/testFolder/trashbinFile.txt"
    And as "Alice" file "trashbinFile.txt" should exist in the trashbin
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "/testFolder/testfile.txt" with content "hello"
    And the last export should contain file "trashbinFile.txt" with content "file in trash bin" in trashbin


  Scenario Outline: Multiple deleted file with almost similar name should be contained in an export
    Given user "Alice" has created folder "testFolder"
    And user "Alice" has uploaded file with content "hello" to "/testFolder/testfile.txt"
    And user "Alice" has uploaded file with content "first file in trash bin" to "/testFolder/<firstFileName>"
    And user "Alice" has uploaded file with content "second file in trash bin" to "/testFolder/<secondFileName>"
    And user "Alice" has deleted file "/testFolder/<firstFileName>"
    And user "Alice" has deleted file "/testFolder/<secondFileName>"
    And as "Alice" file "<firstFileName>" should exist in the trashbin
    And as "Alice" file "<secondFileName>" should exist in the trashbin
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "/testFolder/testfile.txt" with content "hello"
    And the last export should contain file "<firstFileName>" with content "first file in trash bin" in trashbin
    And the last export should contain file "<secondFileName>" with content "second file in trash bin" in trashbin
    Examples:
      | firstFileName     | secondFileName     |
      | trashbinFile.txt  | trashbinFil.txt    |
      | trashbinFile.txt  | trashbinFile.txt.a |
      | trashbinFile.txtt | trashbinFile.txt   |


  Scenario Outline: File uploaded by user with unusual username should be contained in an export
    Given user "<username>" has been created with default attributes and without skeleton files
    And user "<username>" has uploaded file with content "hello" to "testfile.txt"
    When user "<username>" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file "testfile.txt" with content "hello"
    Examples:
      | username |
      | user-1   |
      | null     |
      | nil      |
      | 123      |
      | 0.0      |


  Scenario Outline: File uploaded by user that has special characters in its name should be contained in an export
    Given user "Alice" has uploaded file with content "hello" to <foldername>
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export should contain file <foldername> with content "hello"
    Examples:
      | foldername              |
      | "testfile.txt"          |
      | '"quotes1"'             |
      | "'quotes2'"             |
      | "strängé नेपाली folder" |


  Scenario: An attempt to export an unknown user should fail
    When user "unknown" is exported to path "/tmp/fooSomething" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text "Could not extract user metadata for user"

  @issue-209
  Scenario: export a user after clearing out the items in their trash bin
    Given user "Alice" has uploaded file with content "hello" to "testfile1.txt"
    And user "Alice" has uploaded file with content "hello world" to "testfile2.txt"
    And user "Alice" has deleted file "/testfile1.txt"
    And user "Alice" has emptied the trashbin
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the command should have been successful
    And the last export should contain file "/testfile2.txt" with content "hello world"