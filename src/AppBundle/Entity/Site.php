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
     * @ORM\OneToOne(targetEntity="Theme")
     * @ORM\JoinColumn(name="theme_id", referencedColumnName="id")
     */
    protected $theme;

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
}
