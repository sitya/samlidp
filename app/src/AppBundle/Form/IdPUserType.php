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
                    'label' => 'First name',
                    'attr' => array(
                        'class' => 'namePart'
                    )
                )
            )
            ->add(
                'surName',
                TextType::class,
                array(
                    'label' => 'Last name',
                    'attr' => array(
                        'class' => 'namePart'
                    )
                )
            )
            ->add(
                'username',
                TextType::class,
                array(
                    'required' => true
                )
            )
            ->add(
                'email',
                EmailType::class,
                array(
                    'required' => true
                )
            )
            ->add(
                'displayName',
                TextType::class,
                array(
                    'required' => true
                )
            )
            ->add(
                'affiliation',
                ChoiceType::class,
                array(
                    'choices' => array(
                        'student' => 'student',
                        'staff' => 'staff',
                        'member' => 'member',
                        'faculty' => 'faculty',
                        'employee' => 'employee',
                        'affiliate' => 'affiliate',
                        'alum' => 'alum',
                        'library-walk-in' => 'library-walk-in'
                    ),
                    'label' => 'Affiliation',
                    'placeholder' => '-- Please choose --',
                    'required' => true
                )
            )
            ->add(
                'scope',
                EntityType::class,
                array(
                    'class' => 'AppBundle:Scope',
                    'label' => 'Scope',
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
            'data_class' => 'AppBundle\Entity\IdPUser'
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
