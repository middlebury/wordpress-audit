<?php

namespace AppBundle;

use AppBundle\WordPress;

class Network
{
    protected $sites;

    public function __construct($wordpresses)
    {
        $this->sites = array();

        foreach ($wordpresses as $domain => $wordpress) {
            $this->sites[] = new WordPress($wordpress);
        }
    }
    
    public function getThemes()
    {
        $themes = array();
        
        foreach ($this->sites as $site) {
            $themes = array_merge($themes, $site->getThemes());
        }
        
        return $themes;
    }

    public function getPlugins()
    {
        $plugins = array();

        foreach ($this->sites as $site) {
            $plugins = array_merge($plugins, $site->getPlugins());
        }

        return $plugins;
    }

    public function getSites()
    {
        $sites = array();

        foreach ($this->sites as $site) {
            $sites = array_merge($sites, $site->getSites());
        }

        return $sites;
    }
}
