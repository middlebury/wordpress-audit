<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PluginsController extends Controller
{
    /**
     * @Route("/plugins", name="list_plugins")
     * @Method("GET")
     */
    public function listPlugins()
    {
        $plugins = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findAll();

        return $this->render('plugins.html.twig', [
            'title' => "WordPress Plugins",
            'plugins' => $plugins,
        ]);
    }

    /**
     * @Route("/plugins/{pluginName}", name="show_plugin")
     */
    public function showPlugin($pluginName)
    {
        $plugin = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findOneByName($pluginName);

        return $this->render('plugin.html.twig', [
            'title' => "WordPress Plugins: " . $pluginName,
            'plugin' => $plugin,
        ]);
    }
}
