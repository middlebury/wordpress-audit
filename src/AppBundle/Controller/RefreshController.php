<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Plugin;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\ProcessBuilder;
use \PDO;

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
        $wordpress_plugins = $this->getPluginsFromWordPress();

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
                $plugin->setName($wordpress_plugins['slug']);

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

    private function getPluginUpdatedTime($name, $install, $branches)
    {
        $date = null;
        $i = 0;

        while (empty($date) && !empty($branches[$i])) {
            $date = $this->runPluginUpdatedTime($name, $install, $branches[$i]);
            $i++;
        }

        if (!empty($date)) {
            return new \DateTime($date);
        }

        return null;
    }

    private function runPluginUpdatedTime($name, $install, $branch) {
        $builder = new ProcessBuilder();
        $process = $builder->setPrefix('git')
            ->add('--git-dir=' . $install)
            ->add('log')
            ->add('-1')
            ->add('--format=%cd')
            ->add($branch)
            ->add('--')
            ->add('wp-content/plugins/' . $name)
            ->getProcess();
        $process->run();

        return $process->getOutput();
    }

    private function getPluginsFromWordPress()
    {
        $plugins = array();

        $wordpresses = $this->getParameter('wordpresses');

        foreach ($wordpresses as $wordpress) {
            $host = $wordpress['database_host'];
            $name = $wordpress['database_name'];
            $user = $wordpress['database_user'];
            $pass = $wordpress['database_password'];
            $connection = new PDO("mysql:host=$host;dbname=$name", $user, $pass);

            $statement = $connection->prepare("SELECT meta_value FROM wp_sitemeta WHERE meta_key='_site_transient_update_plugins'");
            $statement->execute();
            $row = $statement->fetch();
            $data = unserialize($row['meta_value']);

            foreach ($data->checked as $plugin => $version) {
                $record = array();
                if (!empty($data->response[$plugin])) {
                    $record = get_object_vars($data->response[$plugin]);
                } else if (!empty($data->no_update[$plugin])) {
                    $record = get_object_vars($data->no_update[$plugin]);
                } else {
                    $slugs = preg_split('/\//', $plugin);
                    $record['slug'] = $slugs[0];
                }
                $record['version'] = $version;

                $record['updated'] = $this->getPluginUpdatedTime($record['slug'], $wordpress['install_path'], $wordpress['branches']);

                if (empty($plugins[$plugin])) {
                    $plugins[$plugin] = array();
                }
                $plugins[$plugin] = array_merge($plugins[$plugin], $record);
            }
        }

        return $plugins;
    }
}
