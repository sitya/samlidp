<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Domain;

class CreateDefaultDomainCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('samli:createDomainOne')
            ->setDescription('Run after install');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $ecObj = $em->getRepository('AppBundle:Domain')->findOneByDomain($this->getContainer()->getParameter('samlidp_hostname'));
        if (!$ecObj) {
            $ecObj = new Domain();
            $ecObj->setDomain($this->getContainer()->getParameter('samlidp_hostname'));
            $em->persist($ecObj);
            $em->flush();
            $output->writeln('Success. ' . $this->getContainer()->getParameter('samlidp_hostname') . ' is your main domain.');
            return;
        }
        
        $output->writeln('Main domain (' .$this->getContainer()->getParameter('samlidp_hostname'). ') has already been in the database.');

    }
}
