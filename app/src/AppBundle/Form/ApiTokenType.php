<?php

namespace AppBundle\Form;

use AppBundle\Entity\IdP;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiTokenType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'edit.apitoken-name-label'
            ])
            ->add('clientId', TextType::class, [
                'label' => 'edit.apitoken_clientid_label',
                'attr' => [
                    'class' => 'regenerate-random',
                    'data-random-length' => '6',
                ],
            ])
            ->add('secret', TextType::class, [
                'label' => 'edit.apitoken_secret_label',
                'attr' => [
                    'class' => 'regenerate-random toggle-password',
                ],
            ])
            ->add('idp', EntityType::class, [
                'class' => IdP::class,
                'label' => false,
                'attr' => [
                    'class' => 'hidden',
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'edit.apitoken_submit_label',
                'attr' => [
                    'class' => 'btn btn-primary btn-xs pull-right',
                ]
            ])
            ;
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ApiToken',
            'translation_domain' => 'idp',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_apitoken';
    }


}
