<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchAllLogosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('samli:fetchAllLogos')
            ->setDescription('Run after install');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();

        $output->writeln('Iterate over IdPs.');
        $haslogo = 0;
        $idps = $em->getRepository('AppBundle:IdP')->findAll();
        foreach ($idps as $idp) {
            if ($idp->getLogo()) {
                $logo_filename = $idp->getLogo();
                $logo_path = $container->get('kernel')->getRootDir() . '/../web/images/idp_logo/';
                if ($logo_filename && !is_file($logo_path . $logo_filename)) {
                    $filesystem = $container->get('oneup_flysystem.logos_filesystem');
                    $contents = $filesystem->read($logo_filename);
                    file_put_contents($logo_path . $logo_filename, $contents);
                }
                $output->writeln($idp->getHostname() . ': ' . $logo_filename);
                $haslogo++;
                continue;
            }
            $output->writeln($idp->getHostname() . ': no logo.');
        }
        $output->writeln('-------------');
        $output->writeln($haslogo . '/' . count($idps) . ' logo fetched.');
    }
}
