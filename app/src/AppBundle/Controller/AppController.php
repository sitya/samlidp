<?php
/**
 * Created by PhpStorm.
 * User: gyufi
 * Date: 2018. 08. 23.
 * Time: 16:24
 */

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AppController extends Controller
{
    public function getTemplateBundleName()
    {
        return $this->getParameter('templatebundle_name')?$this->getParameter('templatebundle_name'):'AppBundle';
    }
}