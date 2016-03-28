<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ThemesController extends Controller
{
    /**
     * @Route("/themes", name="list_themes")
     * @Method("GET")
     */
    public function listAction()
    {
        $themes = $this->getDoctrine()
            ->getRepository('AppBundle:Theme')
            ->findAll();

        return $this->render('themes.html.twig',[
            'title' => "WordPress Themes",
            'themes' => $themes,
        ]);
    }

    /**
     * @Route("/themes/{themeName}", name="show_theme")
     */
    public function showAction($themeName)
    {
        $theme = $this->getDoctrine()
            ->getRepository('AppBundle:Theme')
            ->findOneByName($themeName);

        return $this->render('theme.html.twig', [
            'title' => "WordPress Themes: " . $themeName,
            'theme' => $theme,
        ]);
    }
}
