<?php

namespace AppBundle;

use Symfony\Component\Process\ProcessBuilder;
use \PDO;

class WordPress
{
    protected $database_host;

    protected $database_name;

    protected $database_user;

    protected $database_password;

    protected $install_path;

    protected $plugins_path;

    protected $branches;

    public function __construct($settings)
    {
        foreach ($settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    private function getConnection()
    {
        return new PDO("mysql:host=$this->database_host;dbname=$this->database_name", $this->database_user, $this->database_password);
    }

    public function getPlugins()
    {
        $plugins = array();

        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT meta_value FROM wp_sitemeta WHERE meta_key='_site_transient_update_plugins'");
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['meta_value']);
        
        $connection = null;

        foreach ($data->checked as $plugin => $version) {
            $record = array();
            $slugs = preg_split('/\//', $plugin);
            if (!empty($data->response[$plugin])) {
                $record = get_object_vars($data->response[$plugin]);
            } else if (!empty($data->no_update[$plugin])) {
                $record = get_object_vars($data->no_update[$plugin]);
            } else {
                $record['slug'] = $slugs[0];
            }
            $record['version'] = $version;

            // Get the last updated time from git.
            $record['updated'] = $this->getPluginUpdatedTime($slugs[0]);

            // Get the plugin author from the file.
            $handle = @fopen($this->install_path . $this->plugins_path . $plugin, "r");
            if ($handle) {
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    $matches = array();
                    preg_match('/Author:\s([^\n]*)\n/', $buffer, $matches);
                    if (!empty($matches[1])) {
                        $record['author'] = trim($matches[1]);
                        break;
                    }
                }
                fclose($handle);
            }
            $plugins[$plugin] = $record;
        }

        return $plugins;
    }

    private function getPluginUpdatedTime($name)
    {
        $date = null;
        $i = 0;

        while (empty($date) && !empty($branches[$i])) {
            $date = $this->runPluginUpdatedTime($name, $branches[$i]);
            $i++;
        }

        if (!empty($date)) {
            return new \DateTime($date);
        }

        return null;
    }

    private function runPluginUpdatedTime($name, $branch)
    {
        $builder = new ProcessBuilder();
        $process = $builder->setPrefix('git')
            ->add('--git-dir=' . $this->install_path . '.git')
            ->add('log')
            ->add('-1')
            ->add('--format=%cd')
            ->add($branch)
            ->add('--')
            ->add($this->plugins_path . $name)
            ->getProcess();
        $process->run();

        return $process->getOutput();
    }

    public function getSites()
    {
        $sites = array();

        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT blog_id, domain, path FROM wp_blogs");
        $statement->execute();
        $rows = $statement->fetchAll();
        
        $connection = null;

        foreach ($rows as $row) {
            $row['plugins'] = $this->getSitePlugins($row['blog_id']);

            $sites[$row['domain'] . $row['path']] = $row;
        }

        return $sites;
    }
    
    public function getSitePlugins($site_id)
    {
        $plugins = array();
        
        if (!is_int($site_id)) {
            return $plugins;
        }
        
        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT option_value FROM wp_" . $site_id . "_options WHERE option_name='active_plugins'");
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['option_value']);
        
        $connection = null;
        
        return array_merge($plugins, $data);
    }
}
