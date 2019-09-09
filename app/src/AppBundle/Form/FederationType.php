<?php

namespace AppBundle\Form;

use AppBundle\Entity\IdP;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FederationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('metadataUrl', UrlType::class, array(
                'label' => 'federation.metadataurl.label',
            ))
//            ->add('lastChecked' , DateTimeType::class, array('disabled'=> true, 'widget' => 'single_text', 'format' => 'yyyy-MM-dd  HH:mm'))
            ->add('name', TextType::class, array(
                'label' => 'federation.name.label',
            ))
            ->add('slug', TextType::class, array(
                'label' => 'federation.slug.label',
            ))
            ->add('federationUrl', UrlType::class, array(
                'label' => 'federation.federationurl.label',
            ))
            ->add('contactName', TextType::class, array(
                'label' => 'federation.contactname.label',
            ))
            ->add('contactEmail', EmailType::class, array(
                'label' => 'federation.contactemail.label',
            ))
//            ->add('sps',
//                IntegerType::class,
//                array('disabled'=> true))
            ->add('idps', EntityType::class, array(
                'label' => 'federation.idps.label',
                'class' => IdP::class,
            ));
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Federation',
            'translation_domain' => 'federation',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_federation';
    }


}
