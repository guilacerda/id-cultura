<?php
namespace PROCERGS\LoginCidadao\CoreBundle\Controller\Dev;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PROCERGS\LoginCidadao\CoreBundle\Form\Type\ContactFormType;
use PROCERGS\LoginCidadao\CoreBundle\Entity\SentEmail;
use PROCERGS\OAuthBundle\Entity\Client;
use PROCERGS\LoginCidadao\CoreBundle\Helper\GridHelper;

/**
 * @Route("/dev/client")
 */
class ClientController extends Controller
{

    /**
     * @Route("/new", name="lc_dev_client_new")
     * @Template()
     */
    public function newAction()
    {
        $client = new Client();
        $form = $this->container->get('form.factory')->create($this->container->get('procergs_logincidadao.client.base.form.type'), $client);
        
        $form->handleRequest($this->getRequest());
        $messages = '';
        if ($form->isValid()) {
            $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
            $client->setPerson($this->getUser());
            $clientManager->updateClient($client);
            return $this->redirect($this->generateUrl('lc_dev_client_edit', array(
                'id' => $client->getId()
            )));
        }
        return array(
            'form' => $form->createView(),
            'messages' => $messages
        );
    }

    /**
     * @Route("/", name="lc_dev_client")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        return $this->gridAction($request);
    }
    
    /**
     * @Route("/grid", name="lc_dev_client_grid")
     * @Template()
     */
    public function gridAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $sql = $em->getRepository('PROCERGSOAuthBundle:Client')->createQueryBuilder('c')
        ->where('c.person = :person')
        ->setParameter('person', $this->getUser())
        ->addOrderBy('c.id', 'desc');
        $grid = new GridHelper();
        $grid->setId('client-grid');
        $grid->setPerPage(5);
        $grid->setMaxResult(5);
        $grid->setQueryBuilder($sql);
        $grid->setInfinityGrid(true);
        $grid->setRoute('lc_dev_client_grid');
        return array('grid' => $grid->createView($request));
        
    }
    

    /**
     * @Route("/edit/{id}", name="lc_dev_client_edit")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $client = $em->getRepository('PROCERGSOAuthBundle:Client')->findOneBy(array('id' => $id, 'person' => $this->getUser()));
        if (!$client) {
            return $this->redirect($this->generateUrl('lc_dev_client_new'));
        }
        $form = $this->container->get('form.factory')->create($this->container->get('procergs_logincidadao.client.base.form.type'), $client);
        $form->handleRequest($this->getRequest());
        $messages = '';
        if ($form->isValid()) {
            $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
            $clientManager->updateClient($client);
            $messages = 'aeee';
        }
        return $this->render('PROCERGSLoginCidadaoCoreBundle:Dev\Client:new.html.twig',
                        array(
                    'form' => $form->createView(),
                    'client' => $client,
                    'messages' => $messages
        ));
    }
    
}
