<?php
namespace AppBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Port\Csv\CsvReader;
use Port\Steps\StepAggregator as Workflow;

use Port\Steps\Step\ValidatorStep;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\RecursiveValidator as Validator;

use AppBundle\Utils\IdPUserWriter;
use JMS\Serializer\Serializer;

use Swift_Mailer as Mailer;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig_Environment as Twig;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class UploadListener
{
    /**
     * @var ObjectManager
     */
    private $om;
    private $serializer;
    private $validator;
    private $mailer;
    private $router;
    private $twig;
    private $doctrine;
    private $samlidp_hostname;

    public function __construct(ObjectManager $om, Serializer $serializer, ValidatorInterface $validator, Router $router, Twig $twig, Mailer $mailer, Doctrine $doctrine, $samlidp_hostname)
    {
        $this->om = $om;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
        $this->doctrine = $doctrine;
        $this->samlidp_hostname = $samlidp_hostname;
    }

    public function onUpload(PostPersistEvent $event)
    {
        switch ($event->getType()) {
            case 'idplogos':
                //TODO owner validation
                $idp = $this->om->getRepository('AppBundle:IdP')->find($event->getRequest()->get('idpId'));
                $idp->setLogo($event->getFile()->getPath());
                $this->om->persist($idp);
                $this->om->flush();
                break;

            case 'massimport':
                $idp = $this->om->getRepository('AppBundle:IdP')->find($event->getRequest()->get('idpId'));
                
                //TODO owner validation

                $fileObject = new \SplFileObject($event->getFile()->getRealPath());

                $reader = new CsvReader($fileObject);
                $reader->setHeaderRowNumber(0);

                $validatorStep = new ValidatorStep($this->validator);
                $validatorStep->throwExceptions();

                // TODO egységesíteni az IdPUser edit-ben használttal

                $validatorStep->add('username', new Assert\NotBlank());
                $validatorStep->add('surname', new Assert\NotBlank());
                $validatorStep->add('givenname', new Assert\NotBlank());
                $validatorStep->add('displayname', new Assert\NotBlank());
                $validatorStep->add('scope', new Assert\NotBlank());
                $validatorStep->add('enabled', new Assert\NotBlank());

                $validatorStep->add('email',
                    new Assert\Email(
                        array(
                            "message" => "The email is not a valid email.",
                            "checkMX" => true
                            )
                    )
                );
                $validatorStep->add('action',
                    new Assert\Choice(
                        array(
                            'choices' => array('add', 'update', 'delete'),
                            'message' => 'Invalid action.',
                            )
                    )
                );

                $validatorStep->add("affiliation",
                    new Assert\Choice(
                        array(
                            'choices' => array(
                                'student',
                                'staff',
                                'member',
                                'faculty',
                                'employee',
                                'affiliate',
                                'alum',
                                'library-walk-in'
                            ),
                            'message' => 'Invalid affiliation.',
                            )
                    )
                );

                $workflow = new Workflow($reader);
                $workflow->addWriter(new IdPUserWriter($idp, $this->om, $this->mailer, $this->router, $this->twig, $this->doctrine, $this->samlidp_hostname));
                $workflow->addStep($validatorStep);

                $result = $workflow
                    ->setSkipItemOnFailure(true)
                    ->process()
                ;
                
                $exceptionMessages = array();
                if ($result->getErrorCount() > 0) {
                    $exceptionMessages = $this->getExceptionMessages($result->getExceptions());
                }

                $jsonResult = $this->serializer->serialize(array('result' => $result, 'exceptions' => $exceptionMessages), 'json');
                $response = $event->getResponse();
                $response['result'] = $jsonResult;
                return $response;
                break;
            
            default:
                break;
        }
    }

    public function getExceptionMessages(\SplObjectStorage $exceptions)
    {
        $retarray = array();
        $exceptions->rewind();
        
        while ($exceptions->valid()) {
            $index  = $exceptions->key();
            $exception = $exceptions->current(); // similar to current($exceptions)
            $line   = $exceptions->getInfo();
            switch (get_class($exception)) {
                case 'Port\Exception\ValidationException':
                    foreach ($exception->getViolations() as $vkey => $violation) {
                        $retarray[] = array(
                                $line,
                                $violation->getMessage()
                                    . " "
                                    . $violation->getPropertyPath()
                                    . " "
                                    . $violation->getInvalidValue()
                            );
                    }
                    break;

                default:
                    $retarray[] = array($line, $exception->getMessage());
                    break;
            }
            $exceptions->next();
        }
        return $retarray;
    }
}
