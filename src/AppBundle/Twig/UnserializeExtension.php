<?php

namespace AppBundle\Twig;

/**
 * A twig extension that implements PHP's unserialize function.
 *
 * Plugin and theme permissions are returned as a serialized string, which the
 * twig template can send to an iterator by using this code:
 *
 * <code>
 * {% for domain, permission in plugin.permissions|unserialize %}
 *     <li>{{ domain }}: {{ permission }}</li>
 * {% endfor %}
 * </code>
 */
class UnserializeExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('unserialize', array($this, 'unserializeFilter')),
        );
    }

    /**
     * Passes a string to PHP's unserialize() function.
     *
     * @param string The string to unserialize.
     *
     * @return array An array of unserialized data.
     */
    public function unserializeFilter($string)
    {
        return unserialize($string);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'unserialize_extension';
    }
}
