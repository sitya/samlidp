@reset-schema
@alice(Federations)
@alice(Users)
@federation
Feature: Admin the fedetarions

  Background:
    Given I am on "/login"
    When I fill in "username" with "admin"
    And I fill in "password" with "admin"
    And I press "Sign in"
    Then I should be on "/idp/"
    And I should see "admin"

  Scenario: Show federation's list
    Given I am on "/federation/"
    Then I should see the following table
      | Name  | Slug  | Federation URL       | Last checked        | Metadata URL                               | Contact name  | Contact email | IdPs | SPs | Edit |
      | eduID | eduid | https://www.eduid.hu | 2019-05-05 05:05:05 | https://metadata.eduid.hu/current/href.xml | info@eduid.hu | info@eduid.hu | 0    |     |      |

  Scenario: Show federation details
    Given I am on "/federation/"
    When I follow "eduID"
    Then I should see "Federation" in the title
    And I should see the following table portion
      | Name | eduID |
      | Slug | eduid |

  Scenario: Add new federation
    Given I am on "/federation/"
    When I follow "New federation"
    Then I should see "Federation creation" in the title
    And I fill in the following:
      | Name           | test                         |
      | Slug           | test                         |
      | Federation url | http://test.org              |
      | Metadata url   | http://test.org/metadata.xml |
      | Contact name   | test                         |
      | Contact email  | test@test.org                |
      | Idps           |                              |
    And I press "Create"
    Then I should see "Federation list" in the title
    And I should see the following table portion
      | test | test | http://test.org | | http://test.org/metadata.xml | test | test@test.org | 0    |     |      |

  Scenario: Edit federation
    Given I am on "/federation/2/edit"
    Then I should see "Federation edit" in the title
    When I fill in the following:
      | Name           | edittest |
    And I press "Save changes"
    Then I should see "Federation updated successful."
    When I follow "Back to the federation's list"
    Then I should see "Federation list" in the title
    And I should see the following table portion
      | edittest | test | http://test.org | | http://test.org/metadata.xml | test | test@test.org | 0    |     |      |
