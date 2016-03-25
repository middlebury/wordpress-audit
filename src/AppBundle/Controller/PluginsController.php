<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PluginsController extends Controller
{
    /**
     * @Route("/plugins", name="list_plugins")
     * @Method("GET")
     */
    public function listPlugins()
    {
        $plugins = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findAll();

        return $this->render('plugins.html.twig', [
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

        $form = $this->createFormBuilder($note)
            ->add('author', TextType::class)
            ->add('date', DateType::class)
            ->add('note', TextareaType::class)
            ->add('save', SubmitType::class, array('label' => 'Add Note'))
            ->getForm();
            
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {            
            $plugin->addNote($note);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($plugin);
            $em->persist($note);
            $em->flush();

            return $this->redirectToRoute('show_plugin', array('pluginName' => $pluginName));
        }

        return $this->render('plugin.html.twig', [
            'title' => "WordPress Plugins: " . $pluginName,
            'plugin' => $plugin,
            'form' => $form->createView(),
        ]);
    }
}
