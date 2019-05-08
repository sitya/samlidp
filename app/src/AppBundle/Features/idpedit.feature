@reset-schema
@javascript
@alice(Users)
Feature: IdP admin functions
   Login as an institutional administrator
   Create an Identity Provider
   Add own scope
   # Put in and out into a federation
   # Add own SP
   # Modify this custom SP
   # Delete this SP
   Modify IdP's settings
   Delete the IdP

   Background:
      Given I am on "/login"
       When I fill in "username" with "user"
        And I fill in "password" with "user"
        And I press "Sign in"
       Then I should be on "/idp/"
        And I should see "user"
        And I should not see "EasyAdmin"

   Scenario: Navigation to idp creation
       Given I am on "/idp"
        When I follow "Register New Identity Provider"
        Then I should be on "/idp/add"
         And I should see "Short identifier"

   Scenario: Create and configure a new Identity Provider then delete it
     Given I am on "/idp/add"
      When I fill in "id_p_wizard_hostname" with "test22"
       And I press "Next step"
      Then I should be on "/idp/edit/1"
       And I should see "Organization name"
       And I should see "Organization URL"
      Then I fill in "id_p_edit[instituteName]" with "Test 22 Institute"
       And I fill in "id_p_edit[instituteUrl]" with "http://test22.example.com"
       And I press "Finish"
      Then I should be on "/idp/edit/1#domaindiv"
       And I should see "@test22.samlidp.io"
       And I should see "idp-verification=8e59a08ba401da8aedd958b3a65c2d8e70dc8da2"
       And I should see "https://test22.samlidp.io/saml2/idp/metadata.php"
      Then I fill in "domain-to-verify" with "keszi-favagas.hu"
       And I press "Verify domain"
      Then I should wait 4000
      Then I should be on "/idp/edit/1"
       And I should see ".keszi-favagas.hu"
      Then I follow "Delete Identity Provider"
      Then I should see the modal "Confirm delete Identity Provider" in the "confirm-idp-delete"
      Then I press "Delete"
      Then I should be on "/idp/"
       And I should see "Identity Provider deleted."
