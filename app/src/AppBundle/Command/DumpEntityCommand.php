<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Entity;

class DumpEntityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('samli:dumpEntity')
            ->setDescription('Dump an SP entity.')
            ->addArgument('entityid', InputArgument::REQUIRED, 'Add meg az entityId-t!');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $entities = $em->getRepository('AppBundle:Entity')->findByEntityid($input->getArgument('entityid'));
        foreach ($entities as $entity) {
            print_r(unserialize(stream_get_contents($entity->getEntitydata())));
        }
    }
}
