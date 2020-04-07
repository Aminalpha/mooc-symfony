<?php
// src/Controller/AdvertController.php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Skill;
use App\Entity\Advert;
use App\Entity\Category;
use App\Form\AdvertType;
use App\Entity\AdvertSkill;
use App\Entity\Application;
use App\Form\AdvertEditType;
use App\Repository\AdvertRepository;
use App\Repository\ApplicationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @Route("/advert")
 */

class AdvertController extends AbstractController
{
    public function home()
    {
        return $this->redirectToRoute("oc_advert_index");
    }
    /**
     * @Route("/{page}", name="oc_advert_index", requirements={"page" = "\d+"}, defaults={"page" = 1})
     */
    public function index($page)
    {
        if ($page < 1 ) {
            throw $this->createNotFoundException('Page "' .$page. '" inexistante.');
        }
        // Notre liste d'annonce en dur
        // $listAdverts = array(
        //     array(
        //     'title'   => 'Recherche développpeur Symfony',
        //     'id'      => 1,
        //     'author'  => 'Alexandre',
        //     'content' => 'Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…',
        //     'date'    => new \Datetime()),
        //     array(
        //     'title'   => 'Mission de webmaster',
        //     'id'      => 2,
        //     'author'  => 'Hugo',
        //     'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet. Blabla…',
        //     'date'    => new \Datetime()),
        //     array(
        //     'title'   => 'Offre de stage webdesigner',
        //     'id'      => 3,
        //     'author'  => 'Mathieu',
        //     'content' => 'Nous proposons un poste pour webdesigner. Blabla…',
        //     'date'    => new \Datetime())
        // );
        $nbPerPage = 1;
        $listAdverts = $this->getDoctrine()->getRepository(Advert::class)->getAdverts($page, $nbPerPage);

        $nbPages = ceil(count($listAdverts) / $nbPerPage);

        if ($page > $nbPages) {
            throw $this->createNotFoundException('Page "' .$page. '" inexistante.');
        }
        return $this->render("Advert/index.html.twig", ['listAdverts' => $listAdverts,
        'nbPages' => $nbPages,
        'page' => $page ]) ;
    }


    /**
     * @Route("/view/{advert_id}", name="oc_advert_view", requirements={ "advert_id" = "\d+"})
     * @ParamConverter("advert", options={"mapping": {"advert_id": "id"}})
     */

    public function view(Advert $advert)
    {
        // $advert = $this->getDoctrine()
        // ->getRepository(Advert::class)
        // ->find($id);

  
        // if (null === $advert) {
        //     throw new NotFoundHttpException("L'annonce d'id ". $id ." n'existe pas");
        // }

        // On récupère la liste des candidatures de cette annonce
        $listApplications = $this->getDoctrine()
        ->getRepository(Application::class)
        ->findBy(array('advert' => $advert));
        $advertSkills = $this->getDoctrine()->getRepository(AdvertSkill::class)->findBy(
            array('advert' => $advert),
        );
        $image = $this->getDoctrine()->getRepository(Image::class)->findOneBy(
            ["id" => $advert->getImage()->getId() ]
        );
        $advert->setImage($image);
        return $this->render("Advert/view.html.twig", [
            "advert" => $advert,
            "listApplications" => $listApplications,
            "advertSkills" => $advertSkills,
        ]);
    }

    /**
     * @Route("/add", name="oc_advert_add")
     * @Security("has_role('ROLE_AUTEUR')")
     */

    public function add(Request $request)
    {
        // if (!$this->get('security.authorization_checker')->isGranted("ROLE_AUTEUR")) {
        //     throw new AccessDeniedException('Accès limité aux auteurs.');
        // }
        $advert = new Advert();
        $form = $this->createForm(AdvertType::class, $advert);
      

        if ($request->isMethod('POST')) {
            //Link form and request
            $form->handleRequest($request);
            if ($form->isValid()) {
                //$advert->getImage()->upload();
                $em = $this->getDoctrine()->getManager();
                $em->persist($advert);
                $em->flush();
            }
            $this->addFlash('notice', 'Annonce bien enregistrée');
            return $this->redirectToRoute('oc_advert_view', [
                'advert_id' => $advert->getId()
            ]);
        }

        return $this->render("Advert/add.html.twig", 
        ['form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{id}", name="oc_advert_edit", requirements={"id" = "\d+"})
     */

    public function edit($id, Request $request)
    {
        $advert = $this->getDoctrine()->getManager()->getRepository(Advert::class)
        ->find($id);
        
        if ( null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas");
        }

        $form = $this->get("form.factory")->create(AdvertEditType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash("notice", "Annonce bien modifiée.");
            return $this->redirectToRoute("oc_advert_view", [
                'advert_id' => $advert->getId(),
            ]);
        }

        return $this->render('Advert/edit.html.twig', ['advert' => $advert,
        'form' => $form->createView(),
        ]);
    }

     /**
      * @Route("/delete/{id}", name="oc_advert_delete", requirements={"id" = "\d+"})
      */

    public function delete($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository(Advert::class)->find($id);
    
        if (null === $advert) {
          throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }
    
        //create empty form which content csrf field

        $form = $this->get('form.factory')->create();

        if ($request->isMethod("POST") && $form->handleRequest($request)->isValid()) {
            $em->remove($advert);
            $em->flush();

            $this->addFlash('info', "L'annonce a été bien supprimée");

            return $this->redirectToRoute("oc_advert_index");
        }
    
        return $this->render("Advert/delete.html.twig", array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }
      
    public function menu($limit)
    {
        // On fixe en dur une liste ici, bien entendu par la suite
        // on la récupérera depuis la BDD !
        // $listAdverts = array(
        // array('id' => 2, 'title' => 'Recherche développeur Symfony'),
        // array('id' => 5, 'title' => 'Mission de webmaster'),
        // array('id' => 9, 'title' => 'Offre de stage webdesigner')
        // );
        // $em = $this->getDoctrine()->getManager();
        $listAdverts = $this->getDoctrine()->getRepository(Advert::class)->findAll();

        return $this->render('Advert/menu.html.twig', array(
        // Tout l'intérêt est ici : le contrôleur passe
        // les variables nécessaires au template !
        'listAdverts' => $listAdverts
        ));
    }

    public function contact()
    {
        return $this->redirectToRoute("oc_advert_index");
    }

}