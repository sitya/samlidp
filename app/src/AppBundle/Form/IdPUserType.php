<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdPUserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $idp = $options['data']->getIdP();
        $builder
            ->add(
                'givenName',
                TextType::class,
                array(
                    'label' => 'idp_user.givenname.label',
                    'attr' => array(
                        'class' => 'namePart'
                    )
                )
            )
            ->add(
                'surName',
                TextType::class,
                array(
                    'label' => 'idp_user.surname.label',
                    'attr' => array(
                        'class' => 'namePart'
                    )
                )
            )
            ->add(
                'username',
                TextType::class,
                array(
                    'label' => 'idp_user.username.label',
                    'required' => true
                )
            )
            ->add(
                'email',
                EmailType::class,
                array(
                    'label' => 'idp_user.email.label',
                    'required' => true
                )
            )
            ->add(
                'displayName',
                TextType::class,
                array(
                    'label' => 'idp_user.displayname.label',
                    'required' => true
                )
            )
            ->add(
                'affiliation',
                ChoiceType::class,
                array(
                    'choices' => array(
                        'idp_user.affiliation.choices.student' => 'student',
                        'idp_user.affiliation.choices.staff' => 'staff',
                        'idp_user.affiliation.choices.member' => 'member',
                        'idp_user.affiliation.choices.faculty' => 'faculty',
                        'idp_user.affiliation.choices.employee' => 'employee',
                        'idp_user.affiliation.choices.affiliate' => 'affiliate',
                        'idp_user.affiliation.choices.alum' => 'alum',
                        'idp_user.affiliation.choices.library-walk-in' => 'library-walk-in'
                    ),
                    'label' => 'idp_user.affiliation.label',
                    'placeholder' => 'idp_user.affiliation.placeholder',
                    'required' => true
                )
            )
            ->add(
                'scope',
                EntityType::class,
                array(
                    'class' => 'AppBundle:Scope',
                    'label' => 'idp_user.scope.label',
                    'preferred_choices' => array($idp->getDefaultScope()),
                    'query_builder' => function (EntityRepository $er) use ($idp) {
                        return $er->createQueryBuilder('s')
                        ->join('s.domain', 'd')
                        ->where('d.idp = :idp')
                        ->orWhere('s.domain = :samlidomainid AND s.value = :hostname')
                        ->orderBy('s.value', 'ASC')
                        ->setParameter('idp', $idp)
                        ->setParameter('hostname', $idp->getHostname())
                        ->setParameter('samlidomainid', 1);
                    },
                )
            );
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\IdPUser',
            'translation_domain' => 'idp_user',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_idpuser';
    }
}
