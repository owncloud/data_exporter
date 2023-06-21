@cli @issue-209
Feature: an administrator wants to export the files of the user using the command line


  Scenario: export a user before any resources are deleted
    Given using new dav path
    And user "Alice" has been created with default attributes and without skeleton files
    And user "Alice" has uploaded file with content "hello world" to "testfile1.txt"
    When user "Alice" is exported to path "/tmp/fooSomething" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text "/Alice/files_trashbin/files"
    But the last export should contain file "/testfile1.txt" with content "hello world"