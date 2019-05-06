<?php

namespace AppBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FederationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('metadataUrl')->add('lastChecked' , DateTimeType::class, array('disabled'=> true, 'widget' => 'single_text', 'format' => 'yyyy-MM-dd  HH:mm'))->add('name')->add('slug')->add('federationUrl')->add('contactName')->add('contactEmail')->add('sps' ,
                IntegerType::class,
                array('disabled'=> true))->add('idps');
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Federation'
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
