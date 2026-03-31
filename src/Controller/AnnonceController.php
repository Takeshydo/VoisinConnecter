<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use App\Entity\Annonce;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Date;

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
    #[Route('/annonce/getAll', name: 'app_annonce_all', methods: ['GET'])]
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
            ], 200, [], ['groups' => ['annonce:info']]);
        }
    }

    #[Route('/api/annonce/{category}', name: 'app_annonce_category', methods: ['GET'])]
    public function getAnnonceByCategory(string $category): Response{
        $annonce = $this->annonceRepo->findAll();

        foreach($annonce as $annonces){
            if($annonces->getCategorie() === $category){
                return $this->json([
                    "status" => "success",
                    "message" => "liste des annonces par catégorie",
                    "results" => $annonce
                ]);
            }
        }

        return $this->json([
            "status" => "error",
            "message" => "Pas d'annonce dans cette categorie"
        ]);
    }

    #[Route('/api/annonce/get/{id}', name: 'app_annonce_show', methods: ['GET', 'OPTIONS'])]
    public function getAnnonce(int $id): Response {

        $annonce = $this->annonceRepo->find($id);

        if (!$annonce) {
            return $this->json([
                "status" => "error",
                "message" => "Annonce non trouvée"
            ], 404);
        }

        return $this->json([
            "status" => "ok",
            "message" => "Détails de l'annonce",
            "results" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
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
    #[Route('/api/annonce/add', name: 'app_annonce_add', methods: ['POST'])]
    public function addAnnonce(Request $request, EntityManagerInterface $em): Response{
        $data = json_decode($request->getContent(), true);
        if(!$data){
            return $this->json([
                "status" => "error",
                "message" => "donne vide"
            ]);
        }

        $newAnnonce = new Annonce();
        $newAnnonce->setTitle($data["title"]);
        $newAnnonce->setUsername($data["username"]);
        $newAnnonce->setCategorie($data["categorie"]);
        $newAnnonce->setDescription($data["description"]);
        $newAnnonce->setRemuneration($data["remuneration"]);
        $newAnnonce->setDateActive(new \DateTime($data["dateActive"])  );
        $newAnnonce->setCreationDate(new \DateTime);

        $em->persist($newAnnonce);
        $em->flush();

        return $this->json([
            "status" => "success",
            "message" => "Annonce créer",
            "results" => $newAnnonce
        ]);
    }
}
