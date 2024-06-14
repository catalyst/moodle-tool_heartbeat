@tool @tool_heartbeat @javascript
Feature: Testing nobehaterrors in tool_heartbeat
  Background:
    Given the following config values are set as admin:
    | config            | value            | plugin         |
    | cachecheckwebping | ## 1 week ago ## | tool_heartbeat |
  Scenario: I should see no errors
    Given I am on homepage
    Then I should see "Acceptance test site"