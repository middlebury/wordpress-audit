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
    public function listThemes()
    {
        $themes = $this->getDoctrine()
            ->getRepository('AppBundle:Theme')
            ->findAll();

        return $this->render('theme/themes.html.twig',[
            'title' => "WordPress Themes",
            'themes' => $themes,
        ]);
    }

    /**
     * @Route("/themes/{themeName}", name="show_theme")
     */
    public function showTheme($themeName)
    {
        $theme = $this->getDoctrine()
            ->getRepository('AppBundle:Theme')
            ->findOneByName($themeName);

        $note = new Note();

        $form = $this->createForm(NoteType::class, $note);
            
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {            
            $theme->addNote($note);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($theme);
            $em->persist($note);
            $em->flush();

            return $this->redirectToRoute('show_theme', array('themeName' => $themeName));
        }

        return $this->render('theme/theme.html.twig', [
            'title' => "WordPress Themes: " . $themeName,
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }
}
