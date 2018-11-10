@cli
Feature: An administrator wants to export the files of his user using
         the commandline

  Background:
    Given using new dav path
    And user "user0" has been created

  Scenario: An uploaded file should be contained in an export.
    Given user "user0" uploads file with content "hello" to "testfile.txt" using the WebDAV API
    When user "user0" is exported to path "/tmp/fooSomething" using the occ command
    Then the directory "/tmp/fooSomething/user0" should contain an export
    And the last export should contain file "files/testfile.txt" with content "hello"

