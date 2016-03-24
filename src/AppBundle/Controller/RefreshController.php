<?php

namespace AppBundle\Controller;

use AppBundle\Network;
use AppBundle\Entity\Plugin;
use AppBundle\Entity\Site;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RefreshController extends Controller
{
    /**
     * @Route("/refresh/sites", name="refresh_sites")
     * @Method("GET")
     */
    public function refreshSites()
    {
        $results = array();

        // The sites in our WordPress installation(s).
        $network = new Network($this->getParameter('wordpresses'));
        $wordpress_sites = $network->getSites();

        // The sites in our local application database.
        $sites = $this->getDoctrine()
            ->getRepository('AppBundle:Site')
            ->findAll();

        $em = $this->getDoctrine()->getManager();

        foreach ($sites as $site) {
            $uri = $site->getDomain() . $site->getPath();
            if (in_array($uri, array_keys($wordpress_sites))) {
                $site->setBlogId($wordpress_sites[$uri]['blog_id']);
                $site->setDomain($wordpress_sites[$uri]['domain']);
                $site->setPath($wordpress_sites[$uri]['path']);
                
                $plugins = $site->getPlugins();
                foreach ($plugins as $plugin) {
                    if (!in_array($plugin->file, $wordpress_sites[$uri]['plugins'])) {
                        $site->removePlugin($plugin);
                    } else {
                        $wordpress_sites[$uri]['plugins'] = array_diff($wordpress_sites[$uri]['plugins'], array($plugin->file));
                    }
                }
                
                foreach ($wordpress_sites[$uri]['plugins'] as $file) {
                    $plugin = $this->getDoctrine()
                        ->getRepository('AppBundle:Plugin')
                        ->findOneBy(array('file' => $file));
                    $site->addPlugin($plugin);
                }

                unset($wordpress_sites[$uri]);

                $results[] = 'Updated site record for ' . $uri;
            } else {
                // Do something about sites no longer existing.
                // Only if we decide to keep notes on sites.
            }
        }

        foreach ($wordpress_sites as $uri => $wordpress_site) {
            $site = new Site();
            $site->setBlogId($wordpress_site['blog_id']);
            $site->setDomain($wordpress_site['domain']);
            $site->setPath($wordpress_site['path']);

            foreach ($wordpress_site['plugins'] as $file) {
                $plugin = $this->getDoctrine()
                    ->getRepository('AppBundle:Plugin')
                    ->findOneBy(array('file' => $file));
                $site->addPlugin($plugin);
            }

            $em->persist($site);

            $results[] = 'Created site record for ' . $uri;
        }

        $em->flush();

        return $this->render('refresh.html.twig', [
            'title' => "WordPress Sites Refreshed",
            'results' => $results,
        ]);
    }

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
        ]);
    }
}
