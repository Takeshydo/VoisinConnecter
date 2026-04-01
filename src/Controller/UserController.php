<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Annonce;
use App\Repository\UserRepository;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private AnnonceRepository $annonceRepo,
        private EntityManagerInterface $em
    ) {}

    private function getAuthenticatedUser(Request $request): ?User
    {
        $tokenHeader = $request->headers->get('Authorization');
        if (!$tokenHeader) return null;

        $token = str_replace('Bearer ', '', $tokenHeader);
        return $this->userRepo->findOneBy(['token' => $token]);
    }

    #[Route('/auth/profil', name: 'app_auth_update_profile', methods: ['PUT', 'OPTIONS'])]
    public function updateProfile(Request $request): Response
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return $this->json(["status" => "error", "message" => "Utilisateur introuvable ou session expirée."]);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données invalides."]);
        }

        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);
        if (isset($data['photoProfil'])) $user->setPhotoProfil($data['photoProfil']);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Profil mis à jour avec succès.",
            "result" => $user
        ], 200, [], ['groups' => ['user:info']]);
    }

    #[Route('/auth/annonce', name: 'app_auth_create_annonce', methods: ['POST', 'OPTIONS'])]
    public function createAnnonce(Request $request): Response
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return $this->json(["status" => "error", "message" => "Non autorisé."]);

        $data = json_decode($request->getContent(), true);

        $annonce = new Annonce();
        $annonce->setTitle($data['title']);
        $annonce->setDescription($data['description']);
        $annonce->setRemuneration($data['remuneration']);
        $annonce->setCategorie($data['categorie']);
        $annonce->setDateActive(new \DateTime($data['date_active']));
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

    #[Route('/auth/annonce/{id}', name: 'app_auth_update_annonce', methods: ['PUT', 'OPTIONS'])]
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
        if (isset($data['date_active'])) $annonce->setDateActive(new \DateTime($data['date_active']));

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Annonce modifiée.",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/auth/annonce/{id}', name: 'app_auth_delete_annonce', methods: ['DELETE', 'OPTIONS'])]
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

    #[Route('/auth/account', name: 'app_auth_delete_account', methods: ['DELETE', 'OPTIONS'])]
    public function deleteAccount(Request $request): Response
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return $this->json(["status" => "error", "message" => "Non autorisé."]);

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(["status" => "ok", "message" => "Compte et annonces associés supprimés."]);
    }
}
