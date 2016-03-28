<?php

namespace AppBundle\WordPress;

use Symfony\Component\Process\ProcessBuilder;
use \PDO;

/**
 * Defines a WordPress installation and retrieves information about the plugins,
 * themes, and sites on that installation.
 *
 * @author Ian McBride <imcbride@middlebury.edu>
 */
class WordPress
{
    /**
     * Hostname of the database server for this WordPress installation.
     *
     * @var string
     */
    protected $database_host;

    /**
     * Name of the database for this WordPress installation.
     *
     * @var string
     */
    protected $database_name;

    /**
     * Usename of a database user with access to run SELECT queries on this
     * WordPress installation's database.
     *
     * @var string
     */
    protected $database_user;

    /**
     * Password of the database user specified in $database_user.
     *
     * @var string
     */
    protected $database_password;

    /**
     * Fully qualified filesystem path to an installation of WordPress which
     * contains a .git directory storing repository information. This path
     * should end in a /.
     *
     * @var string
     */
    protected $install_path;

    /**
     * Path relative to the $install_path, ending with a / but not beginning
     * with one, which contains the plugins. This is typically
     * wp-content/plugins/
     *
     * @var string
     */
    protected $plugins_path;

    /**
     * Path relative to the $install_path, ending with a / but not beginning
     * with one, which contains the themes. This is typically
     * wp-content/themes/
     *
     * @var string
     */
    protected $themes_path;

    /**
     * An array of branch names of the git repository to check in sequence for
     * the last updated time of plugins and themes. This is helpful if you
     * commit third party plugins/themes to a different branch than the ones you
     * develop internally. This will be used for checking both the plugins and
     * themes updated times, but will skip to the next branch in the list if it
     * can't find anything in the working directory.
     *
     * Example:
     *
     * <code>
     * array(
     *     'origin/plugins',
     *     'origin/themes',
     *     'origin/master',
     * );
     * </code>
     *
     * @var array
     */
    protected $branches;

    /**
     * Creates the WordPress object.
     *
     * The $setting parameter should be an array of WordPress configuration
     * options as defined in parameters.yml. See parameters.yml.dist for the
     * types of parameters and expected values in the "wordpresses" array.
     *
     * Only keys of the $settings parameter which have corresponding properties
     * in this class will be saved to the object.
     *
     * @param array $wordpresses Configuration options for this WordPress
     *     installation.
     */
    public function __construct($settings)
    {
        foreach ($settings as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Creates a new \PDO connection to the WordPress database and returns it.
     *
     * This is assumed to be a MySQL database.
     *
     * The functions in this class do not destroy this connection, so it can be
     * reused throughout the process of collecting plugin, theme, and site
     * information about the WordPress installation.
     *
     * @return \PDO A connection to the WordPress database.
     */
    private function getConnection()
    {
        return new PDO(
            "mysql:host=$this->database_host;dbname=$this->database_name",
            $this->database_user,
            $this->database_password
        );
    }

    /**
     * Get metadata about the plugins installed on this instance of WordPress.
     *
     * The returned array will have the following structure:
     *
     * <code>
     * array(
     *     "plugin_path" => array(
     *         'id'          => "12345"
     *         'slug'        => "plugin_slug",
     *         'plugin'      => "plugin_path",
     *         'url'         => "https://wordpress.org/plugins/plugin_slug/"
     *         'package'     =>
     *             "https://downloads.wordpress.org/plugin/plugin_slug.2.0.zip"
     *         'version'     => "1.0",
     *         'new_version' => "2.0",
     *         'updated'     => new DateTime('Jan 1, 2016 00:01:01'),
     *         'author'      => "Michael Middlebury",
     *     ),
     * );
     * </code>
     *
     * Plugin path will typically be something like:
     *     plugin_directory/plugin_file.php
     *
     * Plugin slug will typically be something like: plugin_name
     *
     * @return array Metadata about this WordPress install's plugins.
     */
    public function getPlugins()
    {
        $plugins = array();

        $connection = $this->getConnection();

        // _site_transient_update_plugins stores data from the last time the
        // network administration interface checked for updates from
        // wordpress.org. This may not exist.
        $statement = $connection->prepare(
            "SELECT meta_value
             FROM wp_sitemeta
             WHERE meta_key='_site_transient_update_plugins'"
        );
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['meta_value']);

        foreach ($data->checked as $plugin => $version) {
            $record = array();

            // Some plugins have directories, some don't. This allows us to get
            // either the plugin directory or filename and store it in $slugs[0]
            $slugs = preg_split('/\//', $plugin);

            // There is an update available for this plugin on wordpress.org
            if (!empty($data->response[$plugin])) {
                $record = get_object_vars($data->response[$plugin]);

            // There is no update available for this plugin on wordpress.org
            } else if (!empty($data->no_update[$plugin])) {
                $record = get_object_vars($data->no_update[$plugin]);

            // This plugin wasn't found on wordpress.org. It is probably one
            // that was created locally for this installation of WordPress.
            } else {
                $record['slug'] = $slugs[0];
            }

            $record['version'] = $version;

            // Get the last updated time from git.
            $record['updated'] = $this->getUpdatedTime($slugs[0],
                $this->plugins_path);

            // Get the plugin author from the file.
            $record['author'] = $this->getAuthor($this->plugins_path, $plugin);

            $plugins[$plugin] = $record;
        }

        return $plugins;
    }

    /**
     * Get metadata about the sites on this instance of WordPress.
     *
     * The returned array will have the following structure:
     *
     * <code>
     * array(
     *     "sub.domain.tld/site_path" => array(
     *         'blog_id' => "12345",
     *         'domain' => "sub.domain.tld",
     *         'path' => "/site_path",
     *         'plugins' => array(
     *             "plugin1_directory/plugin1_file.php",
     *             "plugin2_directory/plugin2_file.php",
     *         ),
     *         'theme' => "theme_name",
     *     ),
     * );
     * </code>
     *
     * @return array Metadata about this WordPress install's sites.
     *
     * @see \AppBundle\WordPress\WordPress::getSitePlugins()
     * @see \AppBundle\WordPress\WordPress::getSiteTheme()
     */
    public function getSites()
    {
        $sites = array();

        $connection = $this->getConnection();

        $statement = $connection->prepare(
            "SELECT blog_id, domain, path
             FROM wp_blogs"
        );
        $statement->execute();
        $rows = $statement->fetchAll();

        foreach ($rows as $row) {
            // Gets an array of plugin names active on this site.
            $row['plugins'] = $this->getSitePlugins($row['blog_id']);

            // Gets the name of the currently active theme.
            $row['theme'] = $this->getSiteTheme($row['blog_id']);

            $sites[$row['domain'] . $row['path']] = $row;
        }

        return $sites;
    }

    /**
     * Get metadata about the themes installed on this instance of WordPress.
     *
     * The returned array will have the following structure:
     *
     * <code>
     * array(
     *     "theme_name" => array(
     *         'theme'       => "theme_name",
     *         'version'     => "1.0",
     *         'new_version' => "2.0",
     *         'updated'     => new DateTime('Jan 1, 2016 00:01:01'),
     *         'author'      => "Michael Middlebury",
     *     ),
     * );
     * </code>
     *
     * @return array Metadata about this WordPress install's themes.
     */
    public function getThemes()
    {
        $themes = array();

        $connection = $this->getConnection();

        // _site_transient_update_themes stores data from the last time the
        // network administration interface checked for updates from
        // wordpress.org. This may not exist.
        $statement = $connection->prepare(
            "SELECT meta_value
             FROM wp_sitemeta
             WHERE meta_key='_site_transient_update_themes'"
        );
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
            $record['updated'] = $this->getUpdatedTime($theme,
                $this->themes_path);

            // Get the plugin author from the file.
            $record['author'] = $this->getAuthor($this->themes_path,
                $theme . '/style.css');

            $themes[$theme] = $record;
        }

        return $themes;
    }

    /**
     * Get the last time a change was committed to a plugin or theme.
     *
     * The $path is typically wp-content/plugins/ or wp-content/themes/
     * depending on what type of file we're checking. The $name is any
     * file or directory within those paths to check.
     *
     * @param string $name The name of the directory or file to check.
     * @param string $path A path to the directory or file to check.
     *
     * @return \DateTime? The last time the plugin or theme was updated in our
     *     code repository, or null if no such date can be found.
     *
     * @see \AppBundle\WordPress\WordPress::runUpdatedTime()
     */
    private function getUpdatedTime($name, $path)
    {
        $date = null;
        $i = 0;

        // Check each branch of the project, in order until we get a date.
        while (empty($date) && !empty($this->branches[$i])) {
            $date = $this->runUpdatedTime($name, $path, $this->branches[$i]);
            $i++;
        }

        // Format the date as a \DateTime, which the Doctrine ORM expects.
        if (!empty($date)) {
            return new \DateTime($date);
        }

        return null;
    }

    /**
     * Run a console command to get the last updated date of a directory or file
     *   in a particular branch of a git repository.
     *
     * This will use the Symfony ProcessBuilder service to execute the command:
     *     git --git-dir=/path/to/wordpress log -1 --format=%cd BRANCH_NAME --
     *         PATH_TO_FILE/FILE_OR_DIRECTORY_NAME
     *
     * @param string $name  The name of the directory or file to check.
     * @param string $path  A path to the directory or file to check.
     * @param string branch The name of a branch in the git repository.
     *
     * @return string The console output of the command, hopefully in the form
     *     Fri Sep 10 10:35:57 2015 -0400
     *
     * @see \Symfony\Component\Process\ProcessBuilder
     * @see \AppBundle\WordPress\WordPress::getUpdatedTime()
     */
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

    /**
     * Get the name(s) or authors of a plugin or theme.
     *
     * WordPress asks plugin and theme authors to list this in a docblock of
     * their main plugin file or theme style.css file in the form "Author:
     * names of authors". This function will parse a given file and extract the
     * authors from first line matching that pattern.
     *
     * @param string $path  A path to the directory or file to check.
     * @param string $name  The name of the directory or file to check.
     *
     * @return string The name(s) of the authors or a blank string.
     */
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

    /**
     * Given a site id, get a list of the active plugins.
     *
     * Each site gets its own table to store options in the WordPress database
     * so we need to loop through all of them to get the list of active plugins
     * on each. This is a helper function to do that.
     *
     * The returned array will have the following structure:
     *
     * <code>
     * array(
     *     "plugin1_directory/plugin1_file.php",
     *     "plugin2_directory/plugin2_file.php",
     * );
     * </code>
     *
     * @param int $site_id The blog_id of the site in this WordPress install.
     *
     * @return array An array of plugins active on the site.
     */
    private function getSitePlugins($site_id)
    {
        $plugins = array();

        // Ensure that we're given a value that could be a blog_id since we're
        // passing this in as the name of a table.
        if (!is_numeric($site_id)) {
            return $plugins;
        }

        $connection = $this->getConnection();

        $statement = $connection->prepare(
            "SELECT option_value
             FROM wp_" . $site_id . "_options
             WHERE option_name='active_plugins'"
        );
        $statement->execute();
        $row = $statement->fetch();
        $data = unserialize($row['option_value']);

        if (!empty($data)) {
            $plugins = array_merge($plugins, $data);
        }

        return $plugins;
    }

    /**
     * Given a site id, get name of the active theme.
     *
     * Each site gets its own table to store options in the WordPress database
     * so we need to loop through all of them to get the list of active themes
     * on each. This is a helper function to do that.
     *
     * Note: the WordPress wp_ID_options table has a option_name 'active_theme'
     * which stores the human-readable name of the active theme. Here we want
     * the name of the theme's directory instead, so we look for the 'template'
     * option_name.
     *
     * @param int $site_id The blog_id of the site in this WordPress install.
     *
     * @return string The name of the theme active on the site.
     */
    private function getSiteTheme($site_id)
    {
        $theme = '';

        // Ensure that we're given a value that could be a blog_id since we're
        // passing this in as the name of a table.
        if (!is_numeric($site_id)) {
            return $theme;
        }

        $connection = $this->getConnection();

        $statement = $connection->prepare(
            "SELECT option_value
             FROM wp_" . $site_id . "_options
             WHERE option_name='template'"
        );
        $statement->execute();
        $row = $statement->fetch();

        if (!empty($row['option_value'])) {
            $theme = $row['option_value'];
        }

        return $theme;
    }
}
