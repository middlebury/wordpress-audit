<?php

namespace AppBundle\Controller;

use AppBundle\NoteType;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SitesController extends Controller
{
    /**
     * @Route("/sites", name="list_sites")
     * @Method("GET")
     */
    public function listAction()
    {
        // Get all the plugin data.
        $sites = $this->getDoctrine()
            ->getRepository('AppBundle:Site')
            ->findAll();

        return $this->render('site/sites.html.twig', [
            'title' => "WordPress Sites",
            'sites' => $sites,
        ]);
    }

    /**
     * @Route("/sites/{siteId}", name="show_site")
     */
    public function showAction($siteId, Request $request)
    {
        // Get data for a single plugin based on the internal id.
        $site = $this->getDoctrine()
            ->getRepository('AppBundle:Site')
            ->find($siteId);

        // Generate a new note object for the empty note form.
        $note = new Note();

        $form = $this->createForm(NoteType::class, $note);

        // Check to see if we have a postback with a new note.
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $site->addNote($note);

            $em = $this->getDoctrine()->getManager();
            $em->persist($site);
            $em->persist($note);
            $em->flush();

            // After saving the new note, return to the plugin page but without
            // any postback data to avoid triggering the note submission again.
            return $this->redirectToRoute('show_site', array('siteId' => $siteId));
        }

        return $this->render('site/site.html.twig', [
            'title' => "WordPress Sites: " . $site->getDomain() . $site->getPath(),
            'site' => $site,
            'form' => $form->createView(),
        ]);
    }
}
