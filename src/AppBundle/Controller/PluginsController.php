<?php

namespace AppBundle\Controller;

use AppBundle\NoteType;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to get information about plugins on the WordPress network.
 */
class PluginsController extends Controller
{
    /**
     * @Route("/plugins", name="list_plugins")
     * @Method("GET")
     */
    public function listAction()
    {
        // Get all the plugin data.
        $plugins = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findAll();

        // Get a count of the number of sites on each domain in the network.
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT s.domain AS domain, count(s) AS sites
            FROM AppBundle:Site s
            GROUP BY s.domain'
        );

        // Store the count of the number of sites on each domain in the network.
        $result = $query->getResult();
        $domains = array();
        foreach ($result as $row) {
            $domains[$row['domain']] = $row['sites'];
        }

        foreach ($plugins as &$plugin) {
            $sites = $plugin->getSites();
            $permissions = unserialize($plugin->getPermissions());

            // Store the number of sites where the plugin is manually activated.
            $plugin->num_sites = count($sites);

            // If the plugin is activated on the entire network, we need to
            // account for the total number of sites in the network.
            foreach ($permissions as $domain => $permission) {
                if ($permission == 'Network Activate') {
                    // Add the number of sites on the domain where the plugin
                    // is network activated to the number of sites where it is
                    // manually activated.
                    $plugin->num_sites += $domains[$domain];

                    // Run through any sites on the domain where it is network
                    // activated and subtract any that were added to the count
                    // twice because they'd also been manually activated.
                    foreach ($sites as $site) {
                        if ($site->getDomain() == $domain) {
                            $plugin->num_sites--;
                        }
                    }
                }
            }
        }

        return $this->render('plugin/plugins.html.twig', [
            'title' => "WordPress Plugins",
            'plugins' => $plugins,
            'domains' => $domains,
        ]);
    }

    /**
     * @Route("/plugins/{pluginName}", name="show_plugin")
     */
    public function showPlugin($pluginName, Request $request)
    {
        // Get data for a single plugin based on the name.
        $plugin = $this->getDoctrine()
            ->getRepository('AppBundle:Plugin')
            ->findOneByName($pluginName);

        // Generate a new note object for the empty note form.
        $note = new Note();

        $form = $this->createForm(NoteType::class, $note);

        // Check to see if we have a postback with a new note.
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plugin->addNote($note);

            $em = $this->getDoctrine()->getManager();
            $em->persist($plugin);
            $em->persist($note);
            $em->flush();

            // After saving the new note, return to the plugin page but without
            // any postback data to avoid triggering the note submission again.
            return $this->redirectToRoute('show_plugin',
                array('pluginName' => $pluginName)
            );
        }

        return $this->render('plugin/plugin.html.twig', [
            'title' => "WordPress Plugins: " . $pluginName,
            'plugin' => $plugin,
            'form' => $form->createView(),
        ]);
    }
}
