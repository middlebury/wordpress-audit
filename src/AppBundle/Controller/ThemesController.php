<?php

namespace AppBundle\Controller;

use AppBundle\NoteType;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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

        return $this->render('theme/themes.html.twig',[
            'title' => "WordPress Themes",
            'themes' => $themes,
        ]);
    }

    /**
     * @Route("/themes/{themeName}", name="show_theme")
     */
    public function showAction($themeName, Request $request)
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
