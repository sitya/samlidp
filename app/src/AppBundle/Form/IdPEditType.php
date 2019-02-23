<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdPEditType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'instituteName',
                TextType::class,
                array(
                    'mapped' => false,
                    'label' => 'Organization name',
                    'attr' => array(
                        'class' => 'input-sm'
                    ),
                    'required' => true
                )
            )
            ->add(
                'instituteUrl',
                UrlType::class,
                array(
                    'mapped' => false,
                    'label' => 'Organization URL',
                    'attr' => array(
                        'class' => 'input-sm'
                    ),
                    'required' => true
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
            'data_class' => 'AppBundle\Entity\IdP'
        ));
    }
}
