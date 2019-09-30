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
            ->setDescription('Refresh metadatas for the registered federations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $federations = $em->getRepository('AppBundle:Federation')->findAll();

        foreach ($federations as $federation) {
            $modified = $notmodified = $failed = 0;
            if ($federation->getLastChecked()->diff(new \DateTime())->days > -100) {
                $federation->setLastChecked(new \DateTime());

                // will be populated again based on the metadata
                $federation->clearIdpsContained();

                $em->persist($federation);
                $em->flush($federation);
                $output->writeln("\n<info> * ".$federation->getName().' -- downloading metadata from '.$federation->getMetadataurl().'</info>');
                if (!empty($federation->getMetadataurl())) {
                    $ctx = stream_context_create(array('ssl' => ['verify_peer' => false], 'http' => array('ignore_errors' => true)));
                    try {
                        $xmlData = file_get_contents($federation->getMetadataurl(), false, $ctx);
                    } catch (Exception $e) {
                        // TODO: error handling
                    }

                    $output->writeln("\n\tMetadata URL: " .$federation->getMetadataurl());

                    $doc = \SAML2\DOMDocumentFactory::fromString($xmlData);
                    $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($doc->documentElement);

                    foreach ($entities as $key => $entity) {
                        $ifSp = $entity->getMetadata20SP();
                        if ($ifSp == 0) {
                            #unset($entities[$key]);
                        }
                    }

                    $progress = new ProgressBar($output, count($entities));
                    $progress->setFormatDefinition('custom', '%message%: %percent%% (%current%/%max%) %elapsed% %memory% ');
                    $progress->setFormat('custom');
                    $progress->setMessage('Parsing entities from downloaded EntitiesDescriptor');
                    $progress->setBarWidth(50);
                    $progress->start();

                    foreach ($entities as $entity) {
                        $output->writeln("\n\t\tEntityId: " . $entity->getEntityId());
                        $m = $entity->getMetadata20SP();
                        if ($m != 0) {
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

                        $m = $entity->getMetadata20IdP();
                        if ($m !== null && count($m) > 0) {
                            // check if IdP is inside this samlidp instance
                            //$sp = $em->getRepository('AppBundle:Entity')->findOneByEntityid($entity->getEntityId());
                            //
                            $pattern = '/'
                                    . preg_quote('https://', '/')
                                    . '([[:alnum:]\-]+)'
                                    . preg_quote('.')
                                    . '(.+)'
                                    . preg_quote('/saml2/idp/metadata.php', '/')
                                    . '/';

                            $output->writeln("\n\t\tpattern: " . $pattern);
                            $match_ok = preg_match(
                                $pattern,
                                $entity->getEntityId(),
                                $matches
                            );
                            if ($match_ok === 1) {
                                $output->writeln('server:' . $_SERVER['SAMLIDP_HOSTNAME']);
                                $output->writeln('matches:' . print_r($matches, TRUE));

                                $samlidp_hostname = $_SERVER['SAMLIDP_HOSTNAME'];

                                if ($matches[2] === $samlidp_hostname) {
                                    $output->writeln("\n\t\tMATCH, adding: ");
                                    $idp = $em->getRepository('AppBundle:IdP')->findOneByHostname($matches[1]);
                                    #$output->writeln('matches:' . print_r($idp, TRUE));

                                    $output->writeln('before:' . gettype($idp->getFederationsContaining()));
                                    $output->writeln('before:' . print_r($idp->getFederationsContaining(), TRUE));
                                    #$idp->addFederationContaining($federation);
                                    $federation->addIdpContained($idp);
                                    $output->writeln('before:' . gettype($idp->getFederationsContaining()));
                                    //$output->writeln('before:' . print_r($idp->getFederationsContaining(), TRUE));

                                    $em->persist($idp);
                                    $em->flush($idp);
                                    $em->persist($federation);
                                    $em->flush($federation);
                                    #$output->writeln('before:' . count($idp->getFederationsContaining()));
                                    #$output->writeln('after:' . count($idp->getFederationsContaining()));
                                    #$output->writeln('after:' . count($idp->getFederationsContaining()));
                                }
                            }
                        }
                    }
                    $em->flush();
                    $progress->finish();
                    $output->writeln("\n\t<comment>added or changed: ".$modified."</comment>\n\t<info>updated, but not changed: ".$notmodified."</info>");
                }
            }
        }
        $output->writeln("\n<info>Updating federation metadatas has been done.</info>");
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
