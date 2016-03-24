<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="plugin")
 */
class Plugin
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $file;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $installed;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $author;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $installed_version;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $available_version;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $updated;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $permissions;

    /**
     * @ORM\ManyToMany(targetEntity="Site", mappedBy="plugins")
     * @ORM\JoinTable(name="plugins_sites",
     *      joinColumns={@ORM\JoinColumn(name="plugin_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="site_id", referencedColumnName="id")}
     *      )
     */
    protected $sites;

    /**
     * @ORM\ManyToMany(targetEntity="Note")
     * @ORM\JoinTable(name="plugins_notes",
     *      joinColumns={@ORM\JoinColumn(name="plugin_id", referencedColumnName="id")},
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
     * Set name
     *
     * @param string $name
     *
     * @return Plugin
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set author
     *
     * @param string $author
     *
     * @return Plugin
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set installedVersion
     *
     * @param string $installedVersion
     *
     * @return Plugin
     */
    public function setInstalledVersion($installedVersion)
    {
        $this->installed_version = $installedVersion;

        return $this;
    }

    /**
     * Get installedVersion
     *
     * @return string
     */
    public function getInstalledVersion()
    {
        return $this->installed_version;
    }

    /**
     * Set availableVersion
     *
     * @param string $availableVersion
     *
     * @return Plugin
     */
    public function setAvailableVersion($availableVersion)
    {
        $this->available_version = $availableVersion;

        return $this;
    }

    /**
     * Get availableVersion
     *
     * @return string
     */
    public function getAvailableVersion()
    {
        return $this->available_version;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Plugin
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set permissions
     *
     * @param string $permissions
     *
     * @return Plugin
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Get permissions
     *
     * @return string
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add site
     *
     * @param \AppBundle\Entity\Site $site
     *
     * @return Plugin
     */
    public function addSite(\AppBundle\Entity\Site $site)
    {
        $this->sites[] = $site;

        return $this;
    }

    /**
     * Remove site
     *
     * @param \AppBundle\Entity\Site $site
     */
    public function removeSite(\AppBundle\Entity\Site $site)
    {
        $this->sites->removeElement($site);
    }

    /**
     * Get sites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * Add note
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return Plugin
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
     * Constructor
     */
    public function __construct()
    {
        $this->sites = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set installed
     *
     * @param boolean $installed
     *
     * @return Plugin
     */
    public function setInstalled($installed)
    {
        $this->installed = $installed;

        return $this;
    }

    /**
     * Get installed
     *
     * @return boolean
     */
    public function getInstalled()
    {
        return $this->installed;
    }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return Plugin
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }
}
