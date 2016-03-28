<?php

namespace AppBundle\WordPress;

use AppBundle\WordPress\WordPress;

/**
 * Defines a network of WordPress installations.
 *
 * @author Ian McBride <imcbride@middlebury.edu>
 */
class Network
{
    /**
     * A network of WordPress installations.
     *
     * @var WordPress[]
     */
    protected $wordpresses = array();

    /**
     * Creates the Network object.
     *
     * The $wordpresses parameter should be an array of WordPress configuration
     * options as defined in parameters.yml. See parameters.yml.dist for the
     * types of parameters and expected values in the "wordpresses" array. This
     * information can be passed into the construction in Symfony using:
     *
     * <code>
     * $network = new Network($this->getParameter('wordpresses'));
     * </code>
     *
     * @param array $wordpresses Configuration options for each WordPress
     *     installation.
     */
    public function __construct($wordpresses)
    {
        foreach ($wordpresses as $domain => $wordpress) {
            $this->wordpresses[] = new WordPress($wordpress);
        }
    }

    /**
     * Get an array of metadata about the plugins active on this network of
     * Wordpress installations.
     *
     * @return array Metadata about plugins active on this network.
     *
     * @see \AppBundle\WordPress\WordPress::getPlugins()
     */
    public function getPlugins()
    {
        $plugins = array();

        foreach ($this->wordpresses as $wordpress) {
            $plugins = array_merge($plugins, $wordpress->getPlugins());
        }

        return $plugins;
    }

    /**
     * Get an array of metadata about the sites active on this network of
     * WordPress installations.
     *
     * @return array Metadata about sites on this network.
     *
     * @see \AppBundle\WordPress\WordPress::getSites()
     */
    public function getSites()
    {
        $sites = array();

        foreach ($this->wordpresses as $wordpress) {
            $sites = array_merge($sites, $wordpress->getSites());
        }

        return $sites;
    }

    /**
     * Get an array of metadata about the themes active on this network of
     * WordPress installations.
     *
     * @return array Metadata about themes active on this network.
     *
     * @see \AppBundle\WordPress\WordPress::getThemes()
     */
    public function getThemes()
    {
        $themes = array();

        foreach ($this->wordpresses as $wordpress) {
            $themes = array_merge($themes, $wordpress->getThemes());
        }

        return $themes;
    }
}
