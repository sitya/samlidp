<?php

namespace AppBundle\Controller;

use AppBundle\Utils\IdPUserHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
/**
 * @Route("/IdPUserSelfService")
 */
class IdPUserSelfServiceController extends Controller
{
    /**
     * @Route("/editProfile")
     * @Template()
     * @Security("has_role('ROLE_USER')")
     */
    /*
    public function editProfileAction(Request $request)
    {
        $idPUser = $this->getUser();

        $form = $this->createForm('AppBundle\Form\IdPUserType', $idPUser);
        unset($form['username']);
        unset($form['affiliation']);
        unset($form['scope']);

        $form->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password')
                )
        	);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($idPUser, $form->getData('password'));
            $idPUser->setPassword($encoded);

            $this->getDoctrine()->getManager()->persist($idPUser);
            $this->getDoctrine()->getManager()->flush();

            $this->get('session')->getFlashBag()->add('success', 'User updated successful.');
        }

        return array(
            "form" => $form->createView()
        	);
    }
    */

    /**
     * @Route("/login/{domain}", name="idpuser_login")
     * @Template()
     */
    /*
    public function loginAction($domain)
    {
        $csrf_token = "TODO csrftoken";
        $last_username = "";
        return array(
            'last_username' => $last_username,
            'csrf_token' => $csrf_token,
            'domain' => $domain
            );
    }
    */

    /**
     * @Route("/login", name="idpuser_login_check")
     * @Template()
     */
    /*
    public function loginCheckAction($value='')
    {
        # code...
        return array(
            );
    }
    */

    /**
     * @Route("/resetting/request/{domain}", name="idpuser_resetting_request")
     * @Template()
     */
    public function resetRequestAction(Request $request, $domain)
    {
        if ($request->get('username')) {
            $em = $this->getDoctrine()->getManager();
            $idp = $em->getRepository('AppBundle:IdP')->findOneByHostname($domain);
            if (! $idp) {
                return array(
                    "invalid_idp" => $domain,
                    "domain" => $domain
                    );
            }

            foreach ($idp->getIdPUsers() as $user) {
                if ($request->get('username') == $user->getUsername()
                    ||
                    $request->get('username') == $user->getEmail()) {

                    $token = $this->get('fos_user.util.token_generator')->generateToken();

                    IdPUserHelper::sendPasswordResetToken(
                        $user,
                        $token,
                        $this->get('router'),
                        $this->get('twig'),
                        $this->get('mailer'),
                        $this->getParameter('mailer_sender'),
                        'sendPasswordResetToken'
                    );
                    
                    $user->setConfirmationToken($token);
                    $user->setPasswordRequestedAt(new \DateTime());

                    $em->persist($user);
                    $em->flush();
                    return array(
                        'token' => $token,
                        "domain" => $domain
                        );            
                }
            }
            return array(
                "invalid_username" => $request->get('username'),
                "domain" => $domain
                
                );
        }
        return array(
            "domain" => $domain
            );
    }

    /**
     * @Route("/resetting/{token}", name="idpuser_reset_password")
     * @Template()
     */
    public function resetPasswordAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $idPUser = $em->getRepository('AppBundle:IdPUser')->findOneByConfirmationToken($token);
        if (! $idPUser) {
            throw $this->createNotFoundException('Token not found.');
        }

        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add(
                'password',
                RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'options' => array('attr' => array('class' => 'password-field')),
                    'required' => true,
                    'first_options' => array('label' => 'Password'),
                    'second_options' => array('label' => 'Repeat Password')
                )
            )
            ->add(
                'submit',
                SubmitType::class,
                array(
                    'attr' => array(
                        'class' => 'btn btn-primary btn-lg'
                    )
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            // $encoder = $this->container->get('security.password_encoder');
            // $encoded = $encoder->encodePassword($idPUser, $data['password'], $salt);
            $encoder = $this->container->get('appbundle.sha512salted_encoder');
            $salt = $idPUser->getRandomString();
            $encoded = $encoder->encodePassword($data['password'], $salt);
            
            $idPUser->setSalt($salt);
            $idPUser->setPassword($encoded);

            // Convert the password from UTF8 to UTF16 (little endian)
            $ntml_input=iconv('UTF-8','UTF-16LE',$data['password']);
            // Encrypt it with the MD4 hash
            $md4_hash=hash('md4',$ntml_input);
            // Make it uppercase, not necessary, but it's common to do so with NTLM hashes
            $ntml_hash=strtoupper($md4_hash);

            $idPUser->setPasswordNtml($ntml_hash);

            $idPUser->setEnabled(true);
            
            $idPUser->setConfirmationToken(false);

            $em->persist($idPUser);
            $em->flush();
            return array(
                );
        }

        return array(
            "form" => $form->createView()
            );
    }

}
