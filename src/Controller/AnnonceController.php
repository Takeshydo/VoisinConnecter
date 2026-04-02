<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Annonce;
use App\Repository\AnnonceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnnonceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AnnonceRepository $annonceRepo,
        private UserRepository $userRepo
    ) {}

    private function getAuthenticatedUser(Request $request): ?User
    {
        $tokenHeader = $request->headers->get('Authorization');
        if (!$tokenHeader) return null;

        $token = str_replace('Bearer ', '', $tokenHeader);
        return $this->userRepo->findOneBy(['token' => $token]);
    }

    #[Route('/annonce/getAll', name: 'app_annonce_all', methods: ['GET', 'OPTIONS'])]
    public function getAnnonceAll(): Response
    {
        $annonces = $this->annonceRepo->findAll();

        if (empty($annonces)) {
            return $this->json(["status" => "error", "message" => "Pas d'annonce"]);
        }

        return $this->json([
            "status" => "success",
            "message" => "Liste des annonces",
            "result" => $annonces
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/annonce/user/{id}', name: 'app_annonce_user_3', methods: ['GET', 'OPTIONS'])]
    public function getAnnonceUser(int $id): Response
    {
        $actualUser = $this->userRepo->find($id);

        if(empty($actualUser)){
            return $this->json(["status" => "error", "message" => "Utilisateur inexistant"]);
        }

        $annonces = $this->annonceRepo->findBy(["user_annonce" => $actualUser]);

        if (empty($annonces)) {
            return $this->json(["status" => "error", "message" => "Annonce inexistante"]);
        }

        return $this->json(["status" => "success",
                            "message" => "les Annoces de l'utlilisateur",
                            "result" => $annonces
                            ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/annonce/category/{category}', name: 'app_annonce_category', methods: ['GET', 'OPTIONS'])]
    public function getAnnonceByCategory(string $category): Response
    {
        $annonces = $this->annonceRepo->findBy(['categorie' => $category]);

        if (empty($annonces)) {
            return $this->json(["status" => "error", "message" => "Pas d'annonce dans cette categorie"]);
        }

        return $this->json([
            "status" => "success",
            "message" => "Liste des annonces par catégorie",
            "result" => $annonces
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/annonce/get/{id}', name: 'app_annonce_show', methods: ['GET', 'OPTIONS'])]
    public function getAnnonce(int $id): Response
    {
        $annonce = $this->annonceRepo->find($id);

        if (!$annonce) {
            return $this->json(["status" => "error", "message" => "Annonce non trouvée"], 404);
        }

        return $this->json([
            "status" => "ok",
            "message" => "Détails de l'annonce",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/auth/annonce', name: 'app_auth_create_annonce', methods: ['POST', 'OPTIONS'])]
    public function createAnnonce(Request $request): Response
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->json(["status" => "error", "message" => "Non autorisé."]);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données invalides."]);
        }

        $annonce = new Annonce();
        $annonce->setTitle($data['title']);
        $annonce->setDescription($data['description']);
        $annonce->setRemuneration($data['remuneration']);
        $annonce->setCategorie($data['categorie']);
        $annonce->setDateActive(new \DateTime($data['dateActive']));
        $annonce->setCreationDate(new \DateTime());
        $annonce->setUserAnnonce($user);

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Annonce créée.",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/annonce/update/{id}', name: 'app_auth_update_annonce', methods: ['PUT', 'OPTIONS'])]
    public function updateAnnonce(Request $request, int $id): Response
    {
        $user = $this->getAuthenticatedUser($request);
        $annonce = $this->annonceRepo->find($id);

        if (!$user || !$annonce || $annonce->getUserAnnonce() !== $user) {
            return $this->json(["status" => "error", "message" => "Action non autorisée."]);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) $annonce->setTitle($data['title']);
        if (isset($data['description'])) $annonce->setDescription($data['description']);
        if (isset($data['remuneration'])) $annonce->setRemuneration($data['remuneration']);
        if (isset($data['categorie'])) $annonce->setCategorie($data['categorie']);
        if (isset($data['dateActive']) || isset($data['date_active'])) {
            $annonce->setDateActive(new \DateTime($data['dateActive'] ?? $data['date_active']));
        }

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Annonce modifiée.",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/user/annonce/delete/{id}', name: 'app_auth_delete_annonce', methods: ['DELETE', 'OPTIONS'])]
    public function deleteAnnonce(Request $request, int $id): Response
    {
        $user = $this->getAuthenticatedUser($request);
        $annonce = $this->annonceRepo->find($id);

        if (!$user || !$annonce || $annonce->getUserAnnonce() !== $user) {
            return $this->json(["status" => "error", "message" => "Action non autorisée."]);
        }

        $this->em->remove($annonce);
        $this->em->flush();

        return $this->json(["status" => "ok", "message" => "Annonce supprimée."]);
    }

    #[Route('/admin/annonce/update/{id}', name: 'app_admin_edit_annonce', methods: ['PUT', 'OPTIONS'])]
    public function adminEditAnnonce(Request $request, int $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé."]);
        }

        $annonce = $this->annonceRepo->find($id);
        if (!$annonce) {
            return $this->json(["status" => "error", "message" => "Annonce introuvable."]);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données invalides."]);
        }

        if (isset($data['title'])) $annonce->setTitle($data['title']);
        if (isset($data['description'])) $annonce->setDescription($data['description']);
        if (isset($data['remuneration'])) $annonce->setRemuneration($data['remuneration']);
        if (isset($data['categorie'])) $annonce->setCategorie($data['categorie']);
        if (isset($data['dateActive']) || isset($data['date_active'])) {
            $annonce->setDateActive(new \DateTime($data['dateActive'] ?? $data['date_active']));
        }

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Annonce modifiée par l'administrateur.",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/admin/annonce/delete/{id}', name: 'app_admin_delete_annonce', methods: ['DELETE', 'OPTIONS'])]
    public function adminDeleteAnnonce(Request $request, int $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé."]);
        }

        $annonce = $this->annonceRepo->find($id);
        if (!$annonce) {
            return $this->json(["status" => "error", "message" => "Annonce introuvable."]);
        }

        $this->em->remove($annonce);
        $this->em->flush();

        return $this->json(["status" => "ok", "message" => "Annonce supprimée."]);
    }
}
