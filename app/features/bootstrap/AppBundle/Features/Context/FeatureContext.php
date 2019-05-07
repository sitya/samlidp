<?php

namespace AppBundle\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Symfony\Component\HttpKernel\KernelInterface;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context, SnippetAcceptingContext, KernelAwareContext
{
    protected $kernel;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    public function setKernel(KernelInterface $kernelInterface)
    {
        $this->kernel = $kernelInterface;
    }

    /**
     * @Given there are following users:
     */
    public function thereAreFollowingUsers(TableNode $table)
    {
        $em = $this->kernel->getContainer()->get('doctrine')->getManager();

        $hash = $table->getHash();
        foreach ($hash as $row) {
            if ($em->getRepository('AppBundle:User')->findByUsername($row['username'])) {
                continue;
            }

            // $row['name'], $row['email'], $row['phone']
            $user = new \AppBundle\Entity\User();// inherits form FOSUserBundle\Entity\User()
            $user->setUsername($row['username']);
            $user->setUsernameCanonical($row['username']);
            $user->setPlainPassword($row['plain_password']);
            $user->setEmail($row['email']);// required by FOSUserBundle
            $user->setEnabled(true); // add it manually otherwise you'll get "Account is disabled" error
            $user->setRoles(array($row['role']));
            $user->setDisplayName($row['displayname']);
            $em->persist($user);
        }
        $em->flush();
    }

    /**
     * @Then /^show me the HTML page$/
     */
    public function showMeTheHtmlPageInTheBrowser()
    {

        $html_data = $this->getSession()->getDriver()->getContent();
        $file_and_path = '/tmp/behat_page.html';
        file_put_contents($file_and_path, $html_data);

        if (PHP_OS === "Darwin" && PHP_SAPI === "cli") {
            exec('open -a "Safari.app" ' . $file_and_path);
        };
    }

    /**
     * @Then I should wait :duration
     */
    public function iShouldWait($duration)
    {
        $this->getSession()->wait($duration);
    }

    protected function jqueryWait($duration)
    {
        $this->getSession()->wait($duration);
    }

    /**
     * @Then /^I should see the modal "([^"]*)" in the "(?P<element>[^"]*)"$/
     */
    public function iShouldSeeTheModal($title, $element)
    {
        $this->jqueryWait(2000);
        $this->assertElementContainsText('#' . $element . ' .modal-header', $title);

        return $this->getSession()->getPage()->find('css', '#' . $element . ' .modal-header')->isVisible();
    }

    public function assertElementContainsText($element, $text)
    {
        $this->assertSession()->elementTextContains('css', $element, $this->fixStepArgument($text));
    }

    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }


    /**
     * Checks, that ibox-title h1 contains specified text
     * Example: Then I should see "Batman" in the title
     * Example: And I should see "Batman" in the title
     *
     * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" in the title$/
     */
    public function assertElementContainsTextInTitle($text)
    {
        $this->assertSession()->elementTextContains('css', "ibox-title>h1", $this->fixStepArgument($text));
    }
}
