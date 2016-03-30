<?php

namespace AppBundle\Twig;

class UnserializeExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('unserialize', array($this, 'unserializeFilter')),
        );
    }

    public function unserializeFilter($string)
    {
        return unserialize($string);
    }

    public function getName()
    {
        return 'unserialize_extension';
    }
}