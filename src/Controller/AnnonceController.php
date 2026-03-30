<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use App\Entity\Annonce;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnnonceController extends AbstractController
{

    public function __construct(private AnnonceRepository $annonceRepo){

    }


    #[Route('/annonce', name: 'app_annonce')]
    public function index(): Response
    {
        return $this->render('annonce/index.html.twig', [
            'controller_name' => 'AnnonceController',
        ]);
    }



    //GET Toutes les Annonces
    #[Route('/api/annonce', name: 'app_annonce_all', methods: ['GET'])]
    public function getAnnonceAll(): Response{
        $annonce = $this->annonceRepo->findAll();
        if(empty($annonce)){
            return $this->json([
                "status" => "error",
                "message" => "pas d'annonce"
            ]);
        } else {
            return $this->json([
                "status" => "success",
                "message" => "liste des annonces",
                "results" => $annonce
            ]);
        }
    }

    //DELETE l'Annonce spécifique
    #[Route('/api/annonce/{id}', name: 'app_annonce_delete', methods: 'DELETE')]
    public function deleteAnnonce(int $id, EntityManagerInterface $em): Response{
        $annonce = $this->annonceRepo->find($id);

        if(!$annonce){
            return $this->json([
                "status" => "error",
                "message" => "pas d'annonce trouver"
            ]);
        }
        $em->remove($annonce);
        $em->flush();

        return $this->json([
            "status" => "success",
            "message" => "annonce supprimer"
        ]);
    }


    //POST Ajout d'Annonce
    #[Route('/api/annonce/', name: 'app_annonce_add', methods: ['POST'])]
    public function addAnnonce(Request $request, EntityManagerInterface $em): Response{
        $data = json_decode($request->getContent(), true);
        if(!$data){
            return $this->json([
                "status" => "error",
                "message" => "donne vide"
            ]);
        }

        $user = $this->userRepo->findOneBy(["email" => $data["email"]]);
        if(!$user){
            return $this->json(["status" => "error", "message" => "email invalide"]);
        }

        $newAnnonce = new Annonce();
        $newAnnonce->setTitle($data["title"]);
        $newAnnonce->setCategorie($data["categorie"]);
        $newAnnonce->setDescription($data["description"]);
        $newAnnonce->setRemuneration($data["remuneration"]);
        $newAnnonce->setDateActive(new \DateTime($data["dateActive"]));
        $newAnnonce->setCreationDate(new \DateTime($data["creationDate"]));

        $em->persist($newAnnonce);
        $em->flush();

        return $this->json([
            "status" => "success",
            "message" => "Annonce créer",
            "results" => $newAnnonce
        ]);
    }
}
