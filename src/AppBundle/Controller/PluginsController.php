<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \PDO;

class PluginsController extends Controller
{
    /**
     * @Route("/plugins", name="list_plugins")
     * @Method("GET")
     */
    public function listPlugins()
    {
        $wordpresses = $this->getParameter('wordpresses');
        $database_host = $wordpresses['sites.middlebury.edu']['database_host'];
        $database_name = $wordpresses['sites.middlebury.edu']['database_name'];
        $database_user = $wordpresses['sites.middlebury.edu']['database_user'];
        $database_password = $wordpresses['sites.middlebury.edu']['database_password'];
        $connection = new PDO("mysql:host=$database_host;dbname=$database_name", $database_user, $database_password);

        $statement = $connection->prepare("SELECT meta_value FROM wp_sitemeta WHERE meta_key='_site_transient_update_plugins'");
        $statement->execute();
        $row = $statement->fetch();
        $plugin_data = unserialize($row['meta_value']);

        $plugins = array();
        foreach ($plugin_data->checked as $plugin => $version) {
            $record = array();
            if (!empty($plugin_data->response[$plugin])) {
                $record = get_object_vars($plugin_data->response[$plugin]);
            } else if (!empty($plugin_data->no_update[$plugin])) {
                $record = get_object_vars($plugin_data->no_update[$plugin]);
            } else {
                $record['slug'] = substr($plugin, 0, strpos($plugin, '/'));
            }
            $record['version'] = $version;
            $plugins[] = $record;
        }

        return $this->render('plugins.html.twig', [
            'title' => "WordPress Plugins",
            'plugins' => $plugins,
            'data' => $plugins,
        ]);
    }

    /**
     * @Route("/plugins/{pluginName}", name="show_plugin")
     */
    public function showPlugin($pluginName)
    {
        return $this->render('plugin.html.twig', [
            'title' => "WordPress Plugins: " . $pluginName,
            'name' => $pluginName,
        ]);
    }
}
