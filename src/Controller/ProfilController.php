<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private AnnonceRepository $annonceRepo
    ) {}

    #[Route('/auth/update', name: 'app_auth_update_profil', methods: ['PUT', 'OPTIONS'])]
    public function updateProfil(Request $request): Response
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

    #[Route('/auth/delete', name: 'app_auth_delete_account', methods: ['DELETE', 'OPTIONS'])]
    public function deleteAccount(Request $request): Response
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->json(["status" => "error", "message" => "Non autorisé."]);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(["status" => "ok", "message" => "Compte et annonces associés supprimés."]);
    }

    #[Route('/stats', name: 'app_public_stats', methods: ['GET', 'OPTIONS'])]
    public function getStats(): Response
    {
        $totalUsers = $this->userRepo->count([]);
        $totalAnnonces = $this->annonceRepo->count([]);

        $qb = $this->annonceRepo->createQueryBuilder('a')
            ->select('a.categorie as categorieNom', 'COUNT(a.id) as total')
            ->groupBy('a.categorie')
            ->orderBy('total', 'DESC')
            ->setMaxResults(1);

        $topCategoryResult = $qb->getQuery()->getOneOrNullResult();
        $topCategoryName = $topCategoryResult ? $topCategoryResult['categorieNom'] : 'Aucune';

        return $this->json([
            "status" => "ok",
            "result" => [
                "totalUsers" => $totalUsers,
                "totalAnnonces" => $totalAnnonces,
                "topCategory" => $topCategoryName
            ]
        ]);
    }

    #[Route('/admin/update', name: 'app_admin_update_profil', methods: ['PUT', 'OPTIONS'])]
    public function updateAdminProfil(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé."]);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données invalides."]);
        }

        if (isset($data['nom'])) $admin->setNom($data['nom']);
        if (isset($data['prenom'])) $admin->setPrenom($data['prenom']);
        if (isset($data['photoProfil'])) $admin->setPhotoProfil($data['photoProfil']);

        $this->em->persist($admin);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Profil mis à jour.",
            "result" => [
                "nom" => $admin->getNom(),
                "prenom" => $admin->getPrenom(),
                "photoProfil" => $admin->getPhotoProfil()
            ]
        ]);
    }

    #[Route('/admin/user/{id}', name: 'app_admin_delete_user', methods: ['DELETE', 'OPTIONS'])]
    public function deleteUser(Request $request, int $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé."]);
        }

        $userToDelete = $this->userRepo->find($id);
        if (!$userToDelete) {
            return $this->json(["status" => "error", "message" => "Utilisateur introuvable."]);
        }

        $this->em->remove($userToDelete);
        $this->em->flush();

        return $this->json(["status" => "ok", "message" => "Utilisateur supprimé."]);
    }
}
