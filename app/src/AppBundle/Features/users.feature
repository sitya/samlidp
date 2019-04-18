@reset-schema
@alice(InternalUsers)
@idpuser
Feature: Admin the users belong to my institutional IdP

  Background:
    Given I am on "/login"
    When I fill in "username" with "user"
    And I fill in "password" with "user"
    And I press "Sign in"
    Then I should be on "/idp/"
    And I should see "user"
    And I should not see "EasyAdmin"

  Scenario: Show idp1 users
    Given I am on "/idpuser/idp/1"
    Then I should see the following table
      | Username    | Email              | Display name             | Last name   | First name   | Affiliation | Scope                 |
      | username_12 | email_12@gmail.com | firstname_12 lastname_12 | lastname_12 | firstname_12 | staff       | behatidp_1.samlidp.io |
      | username_1  | email_1@gmail.com  | firstname_1 lastname_1   | lastname_1  | firstname_1  | staff       | behatidp_1.samlidp.io |
      | username_10 | email_10@gmail.com | firstname_10 lastname_10 | lastname_10 | firstname_10 | staff       | behatidp_1.samlidp.io |
      | username_11 | email_11@gmail.com | firstname_11 lastname_11 | lastname_11 | firstname_11 | staff       | behatidp_1.samlidp.io |
      | username_2  | email_2@gmail.com  | firstname_2 lastname_2   | lastname_2  | firstname_2  | staff       | behatidp_1.samlidp.io |
      | username_3  | email_3@gmail.com  | firstname_3 lastname_3   | lastname_3  | firstname_3  | staff       | behatidp_1.samlidp.io |
      | username_4  | email_4@gmail.com  | firstname_4 lastname_4   | lastname_4  | firstname_4  | staff       | behatidp_1.samlidp.io |
      | username_5  | email_5@gmail.com  | firstname_5 lastname_5   | lastname_5  | firstname_5  | staff       | behatidp_1.samlidp.io |
      | username_6  | email_6@gmail.com  | firstname_6 lastname_6   | lastname_6  | firstname_6  | staff       | behatidp_1.samlidp.io |
      | username_7  | email_7@gmail.com  | firstname_7 lastname_7   | lastname_7  | firstname_7  | staff       | behatidp_1.samlidp.io |
      | username_8  | email_8@gmail.com  | firstname_8 lastname_8   | lastname_8  | firstname_8  | staff       | behatidp_1.samlidp.io |
      | username_9  | email_9@gmail.com  | firstname_9 lastname_9   | lastname_9  | firstname_9  | staff       | behatidp_1.samlidp.io |

  Scenario: Add new user
    Given I am on "/idpuser/idp/1"
    When I follow "New user"
    Then I should be on "/idpuser/new/1"
    And I fill in the following:
      | Username     | username_13              |
      | Email        | email_13@gmail.com       |
      | Display name | firstname_13 lastname_13 |
      | Last name    | lastname_13              |
      | First name   | firstname_13             |
      | Affiliation  | staff                    |
    And I press "Create"
    Then I should be on "/idpuser/idp/1"
    And I should see the following table portion
      | username_13 | email_13@gmail.com | firstname_13 lastname_13 | lastname_13 | firstname_13 | staff | behatidp_1.samlidp.io |  |

  Scenario: Edit user
    Given I am on "/idpuser/11/edit"
    Then I should see "Edit user"
    When I fill in the following:
      | Email       | edited_email_11@gmail.com |
      | Last name   | edited_lastname_11        |
      | First name  | edited_firstname_11       |
      | Affiliation | alum                      |
    And I press "Save changes"
    Then I should see "User updated successful."
    When I follow "Back to the list"
    Then I should be on "/idpuser/idp/1"
    And I should see the following table portion
      | username_11 | edited_email_11@gmail.com | firstname_11 lastname_11 | edited_lastname_11 | edited_firstname_11 | alum | behatidp_1.samlidp.io |  |

  Scenario: Inactivate a user then reactivate it
    Given I am on "/idpuser/7/edit"
    Then I should see "Inactivate user"
    When I follow "Inactivate user"
    Then I should see "User inactivated successful."
    And I should be on "/idpuser/idp/1"
    Then I go to "/idpuser/7/edit"
    Then I should not see "Inactivate user"
    When I follow "Send password reset mail and reactivate the user"
    Then I should see "Password reset mail sent successful."
    And I should be on "/idpuser/idp/1"

  Scenario: reset password confirm modal at inactivated user
    Given I am on "/idpuser/12/edit"
    Then I should not see "Inactivate user"
    Then I should see "Send password reset mail and reactivate the user"
    Then I follow "Send password reset mail and reactivate the user"
    Then I should be on "/idpuser/idp/1"
    Then I go to "/idpuser/12/edit"
    Then I should see "Send password reset mail and reactivate the user"
    When I press "Send password reset mail and reactivate the user"
    Then I should see the modal "Send password reset mail" in the "confirm-initpasswordreset"
    Then I follow "Send again"
    Then I should see "Password reset mail sent successful."
    Then I should be on "/idpuser/idp/1"

  Scenario: Reset a user's password
    Given I am on "/idpuser/10/edit"
    Then I should see "Send password reset mail"
    When I follow "Send password reset mail"
    Then I should see "Password reset mail sent successful"
    And I should be on "/idpuser/idp/1"

  @javascript
  Scenario: Delete user
    Given I am on "/idpuser/12/edit"
    Then I should see "Delete"
    When I follow "Delete user"
    Then I should see the modal "Confirm delete user" in the "confirm-delete"
    Then I follow "confirm-delete-button"
    Then I should see "User deleted successful"
    Then I should be on "/idpuser/idp/1"
    And I should not see "username_12"

  @javascript
  Scenario: reset password confirm modal at already password reseted user
    Given I am on "/idpuser/10/edit"
    Then I should see "Delete"
    When I follow "Delete user"
    Then I should see the modal "Confirm delete user" in the "confirm-delete"
    Then I follow "confirm-delete-button"
    Then I should see "User deleted successful"
    Then I should be on "/idpuser/idp/1"
    And I should not see "username_10"

