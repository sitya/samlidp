<?php
namespace AppBundle\DataFixtures\ORM;

use Hautelook\AliceBundle\Doctrine\DataFixtures\AbstractLoader;

class DataLoader extends AbstractLoader
{
    /**
     * {@inheritdoc}
     */
    public function getFixtures()
    {
        return [
            '@AppBundle/DataFixtures/ORM/domains_scopes.yml',
            '@AppBundle/DataFixtures/ORM/federations.yml',
            '@AppBundle/DataFixtures/ORM/idps.yml',
            '@AppBundle/DataFixtures/ORM/users.yml',
            '@AppBundle/DataFixtures/ORM/idp_internal_users.yml',
            '@AppBundle/DataFixtures/ORM/idp_audit.yml'
        ];
    }
}
