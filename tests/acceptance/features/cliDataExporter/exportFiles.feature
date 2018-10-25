Feature: An administrator wants to export the files of his user using
         the commandline

  Background:
    Given using new dav path
    And user "user0" has been created

  @cli
  Scenario: An uploaded file should be contained in an export.
    Given user "user0" uploads file with content "hello" to "testfile.txt" using the WebDAV API
    When user "user0" is exported to path "/tmp/fooSomething" using the occ command
    Then the last export contains file "files/testfile.txt"

