@cli
Feature: An administrator wants to import the files of his user using the commandline

  @issue-210
  Scenario: importing without trash bin
    When a user is imported from path "noTrashbinImport/testuser1" using the occ command
    Then the command should have failed with exit code 1
    And the command output should contain the text "Import failed on %path% on line 2: Invalid Data." for path "noTrashbinImport/testuser1/trashbin.jsonl"
    And user "testuser1" should exist