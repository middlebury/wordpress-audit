<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="site")
 */
class Site
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $blog_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $domain;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * @ORM\Column(type="date")
     */
    protected $registered;

    /**
     * @ORM\Column(type="date")
     */
    protected $last_updated;

    /**
     * @ORM\Column(type="integer", options={"default":1})
     */
    protected $visibility;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $archived;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $mature;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $spam;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $deactivated;

    /**
     * @ORM\ManyToOne(targetEntity="Theme")
     * @ORM\JoinColumn(name="theme_id", referencedColumnName="id")
     */
    protected $theme;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin", inversedBy="sites")
     * @ORM\JoinTable(name="plugins_sites",
     *      joinColumns={@ORM\JoinColumn(name="site_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="plugin_id", referencedColumnName="id")}
     *      )
     */
    protected $plugins;

    /**
     * @ORM\ManyToMany(targetEntity="Note")
     * @ORM\JoinTable(name="sites_notes",
     *      joinColumns={@ORM\JoinColumn(name="site_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="note_id", referencedColumnName="id", unique=true)}
     *      )
     */
    protected $notes;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set blogId
     *
     * @param integer $blogId
     *
     * @return Site
     */
    public function setBlogId($blogId)
    {
        $this->blog_id = $blogId;

        return $this;
    }

    /**
     * Get blogId
     *
     * @return integer
     */
    public function getBlogId()
    {
        return $this->blog_id;
    }

    /**
     * Set domain
     *
     * @param string $domain
     *
     * @return Site
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Site
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set theme
     *
     * @param \AppBundle\Entity\Theme $theme
     *
     * @return Site
     */
    public function setTheme(\AppBundle\Entity\Theme $theme = null)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return \AppBundle\Entity\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Add note
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return Site
     */
    public function addNote(\AppBundle\Entity\Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note
     *
     * @param \AppBundle\Entity\Note $note
     */
    public function removeNote(\AppBundle\Entity\Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add plugin
     *
     * @param \AppBundle\Entity\Plugin $plugin
     *
     * @return Site
     */
    public function addPlugin(\AppBundle\Entity\Plugin $plugin)
    {
        $this->plugins[] = $plugin;

        return $this;
    }

    /**
     * Remove plugin
     *
     * @param \AppBundle\Entity\Plugin $plugin
     */
    public function removePlugin(\AppBundle\Entity\Plugin $plugin)
    {
        $this->plugins->removeElement($plugin);
    }

    /**
     * Get plugins
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Set registered
     *
     * @param \DateTime $registered
     *
     * @return Site
     */
    public function setRegistered($registered)
    {
        $this->registered = $registered;

        return $this;
    }

    /**
     * Get registered
     *
     * @return \DateTime
     */
    public function getRegistered()
    {
        return $this->registered;
    }

    /**
     * Set lastUpdated
     *
     * @param \DateTime $lastUpdated
     *
     * @return Site
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->last_updated = $lastUpdated;

        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->last_updated;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     *
     * @return Site
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set archived
     *
     * @param boolean $archived
     *
     * @return Site
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived
     *
     * @return boolean
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set mature
     *
     * @param boolean $mature
     *
     * @return Site
     */
    public function setMature($mature)
    {
        $this->mature = $mature;

        return $this;
    }

    /**
     * Get mature
     *
     * @return boolean
     */
    public function getMature()
    {
        return $this->mature;
    }

    /**
     * Set spam
     *
     * @param boolean $spam
     *
     * @return Site
     */
    public function setSpam($spam)
    {
        $this->spam = $spam;

        return $this;
    }

    /**
     * Get spam
     *
     * @return boolean
     */
    public function getSpam()
    {
        return $this->spam;
    }

    /**
     * Set deactivated
     *
     * @param boolean $deactivated
     *
     * @return Site
     */
    public function setDeactivated($deactivated)
    {
        $this->deactivated = $deactivated;

        return $this;
    }

    /**
     * Get deactivated
     *
     * @return boolean
     */
    public function getDeactivated()
    {
        return $this->deactivated;
    }
}
