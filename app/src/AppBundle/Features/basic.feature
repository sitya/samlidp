Feature: Get the first page

   Scenario: Displaying the splash screen
       Given I am on "/"
        Then I should see "samlidp.io"

   Scenario: Displaying the login page
       Given I am on "/"
        Then I should see "Sign in"
        When I follow "Sign in"
         And I should see a "form" element
         And I should see "Remember me"
