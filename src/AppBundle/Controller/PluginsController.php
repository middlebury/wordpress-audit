<?php

namespace AppBundle\Controller;

use AppBundle\NoteType;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PluginsController extends Controller
{
    /**
     * @Route("/plugins", name="list_plugins")
     * @Method("GET")
     */
    public function listAction()
    {
        $plugins = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findAll();

        return $this->render('plugin/plugins.html.twig', [
            'title' => "WordPress Plugins",
            'plugins' => $plugins,
        ]);
    }

    /**
     * @Route("/plugins/{pluginName}", name="show_plugin")
     */
    public function showPlugin($pluginName, Request $request)
    {
        $plugin = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findOneByName($pluginName);

        $note = new Note();

        $form = $this->createForm(NoteType::class, $note);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plugin->addNote($note);

            $em = $this->getDoctrine()->getManager();
            $em->persist($plugin);
            $em->persist($note);
            $em->flush();

            return $this->redirectToRoute('show_plugin', array('pluginName' => $pluginName));
        }

        return $this->render('plugin/plugin.html.twig', [
            'title' => "WordPress Plugins: " . $pluginName,
            'plugin' => $plugin,
            'form' => $form->createView(),
        ]);
    }
}
