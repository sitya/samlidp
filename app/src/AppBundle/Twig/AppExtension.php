<?php


namespace AppBundle\Twig;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('sha1', [$this, 'getSha1']),
        ];
    }

    public function getSha1($string, $length = null)
    {
        $sha1 = sha1($string);
        if ($length) {
            $sha1 = substr($sha1,0,$length);
        }

        return $sha1;
    }
}