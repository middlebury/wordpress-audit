<?php

namespace AppBundle\Controller;

use AppBundle\Network;
use AppBundle\Entity\Plugin;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RefreshController extends Controller
{
    /**
     * @Route("/refresh/plugins", name="refresh_plugins")
     * @Method("GET")
     */
    public function refreshPlugins()
    {
        $results = array();

        // The plugins in our WordPress installation(s).
        $network = new Network($this->getParameter('wordpresses'));
        $wordpress_plugins = $network->getPlugins();

        // The plugins in our local application database.
        $plugins = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findAll();

        $update_command = 'git --git-dir=/home/imcbride/private_html/middwp/.git log -1 --format=%cd origin/plugins -- wp-content/plugins/';

        $em = $this->getDoctrine()->getManager();

        foreach ($plugins as $plugin) {
            $file = $plugin->getFile();
            if (in_array($file, array_keys($wordpress_plugins))) {
                $plugin->setInstalled(1);
                $plugin->setFile($file);
                $plugin->setName($wordpress_plugins[$file]['slug']);

                if (!empty($wordpress_plugins[$file]['author'])) {
                    $plugin->setAuthor($wordpress_plugins[$file]['author']);
                }

                if (!empty($wordpress_plugins[$file]['version'])) {
                    $plugin->setInstalledVersion($wordpress_plugins[$file]['version']);
                }

                if (!empty($wordpress_plugins[$file]['new_version'])) {
                    $plugin->setAvailableVersion($wordpress_plugins[$file]['new_version']);
                }

                if (!empty($wordpress_plugins[$file]['updated'])) {
                    $plugin->setUpdated($wordpress_plugins[$file]['updated']);
                }

                unset($wordpress_plugins[$file]);

                $results[] = 'Updated plugin record for ' . $plugin->getName();
            } else {
                $plugin->setInstalled(0);
                $results[] = 'Set plugin ' . $plugin->getName() . ' to uninstalled.';
            }
        }

        foreach ($wordpress_plugins as $file => $wordpress_plugin) {
            $plugin = new Plugin();
            $plugin->setInstalled(1);
            $plugin->setFile($file);
            $plugin->setName($wordpress_plugin['slug']);

            if (!empty($wordpress_plugin['author'])) {
                $plugin->setAuthor($wordpress_plugin['author']);
            }

            if (!empty($wordpress_plugin['version'])) {
                $plugin->setInstalledVersion($wordpress_plugin['version']);
            }

            if (!empty($wordpress_plugin['new_version'])) {
                $plugin->setAvailableVersion($wordpress_plugin['new_version']);
            }

            if (!empty($wordpress_plugin['updated'])) {
                $plugin->setUpdated($wordpress_plugin['updated']);
            }

            $em->persist($plugin);

            $results[] = 'Created plugin record for ' . $plugin->getName();
        }

        $em->flush();

        return $this->render('refresh.html.twig', [
            'title' => "WordPress Plugins Refreshed",
            'results' => $results,
            'wordpress_plugins' => $wordpress_plugins,
            'plugins' => $plugins,
        ]);
    }
}
