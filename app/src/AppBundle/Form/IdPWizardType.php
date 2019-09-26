<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdPWizardType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'hostname',
                TextType::class,
                array(
                    'label' => 'idp.wizard.hostname.label',
                    'required' => true,
                    'attr' => array(
                        'class' => 'form-control col-xs-4',
                        'placeholder' => 'idp.wizard.hostname.placeholder',
                        'aria-describedby' => 'basic-addon3'
                    )
                )
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\IdP',
            'translation_domain' => 'idp',
        ));
    }
}
