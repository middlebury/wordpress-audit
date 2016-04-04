<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Plugin;
use AppBundle\Entity\Site;
use AppBundle\Entity\Theme;
use AppBundle\WordPress\Network;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RefreshController extends Controller
{
    /**
     * @Route("/refresh", name="refresh_all")
     * @Method("GET")
     */
    public function refreshAction()
    {
        $results = array();

        if ($this->forward('AppBundle:Refresh:plugins')->isSuccessful()) {
            $results[] = "WordPress Plugins Refreshed.";
        }

        if ($this->forward('AppBundle:Refresh:themes')->isSuccessful()) {
            $results[] = "WordPress Themes Refreshed.";
        }

        if ($this->forward('AppBundle:Refresh:sites')->isSuccessful()) {
            $results[] = "WordPress Sites Refreshed.";
        }

        return $this->render('refresh.html.twig', [
            'title' => "WordPress Refreshed",
            'results' => $results,
        ]);
    }

    /**
     * @Route("/refresh/sites", name="refresh_sites")
     * @Method("GET")
     */
    public function sitesAction()
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
                $site->setRegistered($wordpress_sites[$uri]['registered']);
                $site->setLastUpdated($wordpress_sites[$uri]['last_updated']);
                $site->setVisibility($wordpress_sites[$uri]['public']);
                $site->setArchived($wordpress_sites[$uri]['archived']);
                $site->setMature($wordpress_sites[$uri]['mature']);
                $site->setSpam($wordpress_sites[$uri]['spam']);
                $site->setDeactivated($wordpress_sites[$uri]['deleted']);

                $plugins = $site->getPlugins();
                foreach ($plugins as $plugin) {
                    $file = $plugin->getFile();
                    if (!in_array($file, $wordpress_sites[$uri]['plugins'])) {
                        $site->removePlugin($plugin);
                    } else {
                        $wordpress_sites[$uri]['plugins'] = array_diff($wordpress_sites[$uri]['plugins'], array($file));
                    }
                }

                foreach ($wordpress_sites[$uri]['plugins'] as $file) {
                    $plugin = $this->getDoctrine()
                        ->getRepository('AppBundle:Plugin')
                        ->findOneBy(array('file' => $file));

                    if (!empty($plugin)) {
                        $site->addPlugin($plugin);
                    }
                }

                $theme = $this->getDoctrine()
                    ->getRepository('AppBundle:Theme')
                    ->findOneByName($wordpress_sites[$uri]['theme']);
                if (!empty($theme)) {
                    $site->setTheme($theme);
                    $theme->addSite($site);

                    $em->persist($theme);
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
            $site->setRegistered($wordpress_site['registerd']);
            $site->setLastUpdated($wordpress_site['last_updated']);
            $site->setVisibility($wordpress_site['visibility']);
            $site->setArchived($wordpress_site['archived']);
            $site->setMature($wordpress_site['mature']);
            $site->setSpam($wordpress_site['spam']);
            $site->setDeactivated($wordpress_site['deleted']);

            foreach ($wordpress_site['plugins'] as $file) {
                $plugin = $this->getDoctrine()
                    ->getRepository('AppBundle:Plugin')
                    ->findOneBy(array('file' => $file));

                if (!empty($plugin)) {
                    $site->addPlugin($plugin);
                }
            }

            $theme = $this->getDoctrine()
                ->getRepository('AppBundle:Theme')
                ->findOneByName($wordpress_site['theme']);
            if (!empty($theme)) {
                $site->setTheme($theme);
                $theme->addSite($site);

                $em->persist($theme);
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
     * @Route("/refresh/themes", name="refresh_themes")
     * @Method("GET")
     */
    public function themesAction()
    {
        $results = array();

        // The themes in our WordPress installation(s).
        $network = new Network($this->getParameter('wordpresses'));
        $wordpress_themes = $network->getThemes();

        // The themes in our local application database.
        $themes = $this->getDoctrine()
            ->getRepository('AppBundle:Theme')
            ->findAll();

        $em = $this->getDoctrine()->getManager();

        foreach ($themes as $theme) {
            $name = $theme->getName();
            if (in_array($name, array_keys($wordpress_themes))) {
                $theme->setInstalled(1);
                $theme->setName($name);

                if (!empty($wordpress_themes[$name]['version'])) {
                    $theme->setInstalledVersion($wordpress_themes[$name]['version']);
                }

                if (!empty($wordpress_themes[$name]['new_version'])) {
                    $theme->setAvailableVersion($wordpress_themes[$name]['new_version']);
                }

                if (!empty($wordpress_themes[$name]['updated'])) {
                    $theme->setUpdated($wordpress_themes[$name]['updated']);
                }

                if (!empty($wordpress_themes[$name]['author'])) {
                    $theme->setAuthor($wordpress_themes[$name]['author']);
                }

                if (!empty($wordpress_themes[$name]['permissions'])) {
                    $theme->setPermissions(serialize($wordpress_themes[$name]['permissions']));
                }

                unset($wordpress_themes[$name]);

                $results[] = 'Updated theme record for ' . $theme->getName();
            } else {
                $theme->setInstalled(0);
                $results[] = 'Set theme ' . $theme->getName() . ' to uninstalled.';
            }

            // Clear all the sites from this theme. The site refresh process will add them back in.
            foreach ($theme->getSites() as $site) {
                $theme->removeSite($site);
            }
        }

        foreach ($wordpress_themes as $name => $wordpress_theme) {
            $theme = new Theme();
            $theme->setInstalled(1);
            $theme->setName($name);

            if (!empty($wordpress_theme['version'])) {
                $theme->setInstalledVersion($wordpress_theme['version']);
            }

            if (!empty($wordpress_theme['new_version'])) {
                $theme->setAvailableVersion($wordpress_theme['new_version']);
            }

            if (!empty($wordpress_theme['updated'])) {
                $theme->setUpdated($wordpress_theme['updated']);
            }

            if (!empty($wordpress_theme['author'])) {
                $theme->setAuthor($wordpress_theme['author']);
            }

            if (!empty($wordpress_theme['permissions'])) {
                $theme->setPermissions(serialize($wordpress_theme['permissions']));
            }

            $em->persist($theme);

            $results[] = 'Created theme record for ' . $theme->getName();
        }

        $em->flush();

        return $this->render('refresh.html.twig', [
            'title' => "WordPress Themes Refreshed",
            'results' => $results,
        ]);
    }

    /**
     * @Route("/refresh/plugins", name="refresh_plugins")
     * @Method("GET")
     */
    public function pluginsAction()
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

                if (!empty($wordpress_plugins[$file]['permissions'])) {
                    $plugin->setPermissions(serialize($wordpress_plugins[$file]['permissions']));
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

            if (!empty($wordpress_plugin['permissions'])) {
                $plugin->setPermissions(serialize($wordpress_plugin['permissions']));
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
