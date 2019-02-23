<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Federation;
use AppBundle\Entity\Entity;
use Symfony\Component\Console\Helper\ProgressBar;

class ParseFederationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('samli:parseFederations')
            ->setDescription('Parsing federations from MET');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        // $jsonData = file_get_contents('https://met.refeds.org/?export=federations&format=json');
        // $addedFederation = 0;
        // foreach (json_decode($jsonData, true) as $key => $value) {
        //     $slug = $this->toAscii($key);
        //     $federation = $em->getRepository('AppBundle:Federation')->findOneBySlug($slug);
        //     if (!$federation) {
        //         $federation = new Federation();
        //         ++$addedFederation;
        //     }
        //     $federation->setSlug($slug);
        //     $federation->setLastChecked(new \DateTime());
        //     $federation->setSps($value['SPSSO']);
        //     $federation->setName($key);
        //     $em->persist($federation);
        //     $em->flush();
        // }
        // if ($addedFederation > 0) {
        //     $output->writeln("Hozzáadtunk $addedFederation föderációt.");
        // } else {
        //     $output->writeln('Nem került be új föderáció.');
        // }

        $federations = $em->getRepository('AppBundle:Federation')->findAll();
        //$federations = $em->getRepository('AppBundle:Federation')->findBySlug('eduidhu-federation');

        foreach ($federations as $federation) {
            $modified = $notmodified = $failed = 0;
            $mdSource = (empty($federation->getMetadataurl())) ? 'MET' : $federation->getMetadataurl();
            $output->writeln("\n<info> * ".$federation->getName().' -- download metadata from '.$mdSource.'</info>');
            if ($federation->getLastChecked()->diff(new \DateTime())->days > 1) {
                $federation->setLastChecked(new \DateTime());
                $em->persist($federation);
                $em->flush($federation);

                if (!empty($federation->getMetadataurl())) {
                    $progress = new ProgressBar($output, 50);
                    $ctx = stream_context_create(array('ssl' => ['verify_peer' => false], 'http' => array('ignore_errors' => true)), array('notification' => function ($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) use ($output, $progress) {
                        switch ($notification_code) {
                            case STREAM_NOTIFY_FILE_SIZE_IS:
                                $progress->start($bytes_max);
                                break;
                            case STREAM_NOTIFY_PROGRESS:
                                $progress->setProgress($bytes_transferred);
                                break;
                        }
                    }));
                    try {
                        $xmlData = file_get_contents($federation->getMetadataurl(), false, $ctx);
                    } catch (Exception $e) {
                    }

                    $progress->finish();

                    $doc = \SAML2_DOMDocumentFactory::fromString($xmlData);
                    $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($doc->documentElement);

                    foreach ($entities as $key => $entity) {
                        $ifSp = $entity->getMetadata20SP();
                        if ($ifSp == 0) {
                            unset($entities[$key]);
                        }
                    }

                    $progress = new ProgressBar($output, count($entities));
                    $progress->setFormatDefinition('custom', '%message%: %percent%% (%current%/%max%) %elapsed% %memory% ');
                    $progress->setFormat('custom');
                    $progress->setMessage('Parsing entities from downloaded EntitiesDescriptor');
                    $progress->setBarWidth(50);
                    $progress->start();

                    foreach ($entities as $entity) {
                        $m = $entity->getMetadata20SP();
                        if (count($m) > 0) {
                            $sp = $em->getRepository('AppBundle:Entity')->findOneByEntityid($entity->getEntityId());
                            unset($m['entityDescriptor']);
                            unset($m['expire']);
                            unset($m['metadata-set']);
                            $serializedData = serialize($m);
                            if (!$sp || $sp->getFederation() != $federation || ($sp->getSha1sum() != sha1($serializedData) && $sp->getLastModified() < new \DateTime('-60 minutes'))) {
                                if (!$sp || $sp->getFederation() != $federation) {
                                    $sp = new Entity();
                                    $sp->setEntityid($entity->getEntityId());
                                }
                                $sp->setSha1sum(sha1($serializedData));
                                $sp->setEntitydata($serializedData);
                                $sp->setFederation($federation);
                                $sp->setLastModified(new \DateTime());
                                $em->persist($sp);
                                $em->flush($sp);
                                ++$modified;
                            } else {
                                ++$notmodified;
                            }
                            $progress->advance();
                        }
                    }
                    $progress->finish();
                    $output->writeln('<comment> '.$modified.'</comment> <info>'.$notmodified.'</info> <error>'.$failed.'</error>');
                } else {
                    $rounds = ceil($federation->getSps() / 20);

                    $progress = new ProgressBar($output, $federation->getSps());
                    $progress->setFormatDefinition('custom', '%message%: %percent%% (%current%/%max%) %elapsed% %memory% ');
                    $progress->setFormat('custom');
                    $progress->setMessage('Fetching entities from MET one by one');
                    $progress->setBarWidth(50);
                    $progress->start();
                    for ($i = 1; $i <= $rounds; ++$i) {
                        $jsonData = file_get_contents('https://met.refeds.org/met/federation/'.$federation->getSlug().'/?format=json&entity_type=SPSSODescriptor&page='.$i);

                        foreach (json_decode($jsonData, true) as $entity) {
                            if ($entity['types'][0] == 'SP') {
                                $spXml = file_get_contents('https://met.refeds.org/met/entity/'.urlencode($entity['entityid']).'/?viewxml=true&federation='.$federation->getSlug());
                                $sp = $em->getRepository('AppBundle:Entity')->findOneByEntityid($entity['entityid']);

                                if (!$sp || $sp->getSha1sum() != sha1($spXml)) {
                                    try {
                                        $m = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsString($spXml);
                                        $m = $m[$entity['entityid']]->getMetadata20SP();
                                        unset($m['entityDescriptor']);
                                        unset($m['expire']);
                                        unset($m['metadata-set']);
                                    } catch (\Exception $e) {
                                        // XXX hekk, hogy érvénytelen xml esetén se álljon le a futás
                                        ++$failed;
                                        continue;
                                    }
                                    if (!$sp) {
                                        $sp = new Entity();
                                        $sp->setEntityid($entity['entityid']);
                                    }
                                    $sp->setSha1sum(sha1($spXml));
                                    $sp->setEntitydata(serialize($m));
                                    $sp->setFederation($federation);
                                    $sp->setLastModified(new \DateTime());
                                    $em->persist($sp);
                                    $em->flush($sp);
                                    ++$modified;
                                } else {
                                    ++$notmodified;
                                }
                            }
                            $progress->advance();
                        }
                    }
                    $progress->finish();
                    $output->writeln('<comment> '.$modified.'</comment> <info>'.$notmodified.'</info> <error>'.$failed.'</error>');
                }
            }
        }
    }

    protected function toAscii($str, $replace = array(), $delimiter = '-')
    {
        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }
}
