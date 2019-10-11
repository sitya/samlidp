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
            ->add('organizationNames',
                CollectionType::class, [
                    'label' => 'idp.edit.organizationNames.label',
                    'entry_type' => OrganizationNameType::class,
                    'entry_options' => ['label' => false],
//                    'entry_options' => ['label' => 'idp.edit.instituteName.label'],
                    'required' => true,
                    'mapped' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
            ->add('organizationInformationURLs',
                CollectionType::class, [
                    'label' => 'idp.edit.organizationInformationURLs.label',
                    'entry_type' => OrganizationInformationURLType::class,
                    'entry_options' => ['label' => false],
                    // 'entry_options' => ['label' => 'idp.edit.instituteUrl.label'],
                    'required' => true,
                    'mapped' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                ])
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
