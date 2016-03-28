<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitesController extends Controller
{
    /**
     * @Route("/sites", name="list_sites")
     * @Method("GET")
     */
    public function listAction()
    {
        $sites = $this->getDoctrine()
            ->getRepository('AppBundle:Site')
            ->findAll();

        return $this->render('sites.html.twig', [
            'title' => "WordPress Sites",
            'sites' => $sites,
        ]);
    }

    /**
     * @Route("/sites/{siteId}", name="show_site")
     */
    public function showAction($siteId)
    {
        $site = $this->getDoctrine()
            ->getRepository('AppBundle:Site')
            ->find($siteId);

        return $this->render('site.html.twig', [
            'title' => "WordPress Sites: " . $site->getDomain() . $site->getPath(),
            'site' => $site,
        ]);
    }
}
