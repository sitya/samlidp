<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add(
            'givenName',
            TextType::class,
            array(
                'label' => 'form.givenname.label',
                'translation_domain' => 'FOSUserBundle',
            )
        )
        ->add(
            'sn',
            TextType::class,
            array(
                'label' => 'form.sn.label',
                'translation_domain' => 'FOSUserBundle'
            )
        );
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_profile';
    }

    // For Symfony 2.x
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
