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
    
    protected $themes_path;

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
    
    public function getThemes()
    {
        $themes = array();
        
        $connection = $this->getConnection();
        
        $statement = $connection->prepare("SELECT meta_value FROM wp_sitemeta WHERE meta_key='_site_transient_update_themes'");
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['meta_value']);
        
        foreach ($data->checked as $theme => $version) {
            $record = array();
            $record['theme'] = $theme;
            $record['version'] = $version;

            if (!empty($data->response[$theme])) {
                $record['new_version'] = $data->response[$theme]['new_version'];                
            }

            // Get the last updated time from git.
            $record['updated'] = $this->getUpdatedTime($theme, $this->themes_path);

            // Get the plugin author from the file.
            $record['author'] = $this->getAuthor($this->themes_path, $theme . '/style.css');
            
            $themes[$theme] = $record;
        }
        
        return $themes;
    }

    public function getPlugins()
    {
        $plugins = array();

        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT meta_value FROM wp_sitemeta WHERE meta_key='_site_transient_update_plugins'");
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['meta_value']);

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
            $record['updated'] = $this->getUpdatedTime($slugs[0], $this->plugins_path);

            // Get the plugin author from the file.
            $record['author'] = $this->getAuthor($this->plugins_path, $plugin);

            $plugins[$plugin] = $record;
        }

        return $plugins;
    }

    private function getUpdatedTime($name, $path)
    {
        $date = null;
        $i = 0;

        while (empty($date) && !empty($this->branches[$i])) {
            $date = $this->runUpdatedTime($name, $path, $this->branches[$i]);
            $i++;
        }

        if (!empty($date)) {
            return new \DateTime($date);
        }

        return null;
    }

    private function runUpdatedTime($name, $path, $branch)
    {
        $builder = new ProcessBuilder();
        $process = $builder->setPrefix('git')
            ->add('--git-dir=' . $this->install_path . '.git')
            ->add('log')
            ->add('-1')
            ->add('--format=%cd')
            ->add($branch)
            ->add('--')
            ->add($path . $name)
            ->getProcess();
        $process->run();

        return $process->getOutput();
    }
    
    private function getAuthor($path, $name)
    {
        $author = '';
        
        $handle = @fopen($this->install_path . $path . $name, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);
                $matches = array();
                preg_match('/Author:\s([^\n]*)\n/', $buffer, $matches);
                if (!empty($matches[1])) {
                    $author = trim($matches[1]);
                    break;
                }
            }
            fclose($handle);
        }
        
        return $author;
    }

    public function getSites()
    {
        $sites = array();

        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT blog_id, domain, path FROM wp_blogs");
        $statement->execute();
        $rows = $statement->fetchAll();

        foreach ($rows as $row) {
            $row['plugins'] = $this->getSitePlugins($row['blog_id']);
            $row['theme'] = $this->getSiteTheme($row['blog_id']);

            $sites[$row['domain'] . $row['path']] = $row;
        }

        return $sites;
    }
    
    private function getSitePlugins($site_id)
    {
        $plugins = array();
        
        if (!is_numeric($site_id)) {
            return $plugins;
        }
        
        $connection = $this->getConnection();

        $statement = $connection->prepare("SELECT option_value FROM wp_" . $site_id . "_options WHERE option_name='active_plugins'");
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['option_value']);
        
        if (!empty($data)) {
            $plugins = array_merge($plugins, $data);
        }
        
        return $plugins;
    }
    
    private function getSiteTheme($site_id)
    {
        $theme = '';
        
        if (!is_numeric($site_id)) {
            return $theme;
        }
        
        $connection = $this->getConnection();
        
        $statement = $connection->prepare("SELECT option_value FROM wp_" . $site_id . "_options WHERE option_name='template'");
        $statement->execute();
        $row = $statement->fetch();
        
        if (!empty($row['option_value'])) {
            $theme = $row['option_value'];
        }
        
        return $theme;
    }
}
