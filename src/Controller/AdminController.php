<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private AnnonceRepository $annonceRepo,
        private EntityManagerInterface $em
    ) {}

    private function getAuthenticatedAdmin(Request $request)
    {
        $token = $request->headers->get('Authorization');
        if (!$token) return null;

        $token = str_replace('Bearer ', '', $token);
        $admin = $this->userRepo->findOneBy(['token' => $token]);

        if ($admin && in_array('ROLE_ADMIN', $admin->getRole())) {
            return $admin;
        }

        return null;
    }

    #[Route('/admin/profile', name: 'app_admin_update_profile', methods: ['PUT', 'OPTIONS'])]
    public function updateProfile(Request $request): Response
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

    #[Route('/admin/annonce/{id}', name: 'app_admin_delete_annonce', methods: ['DELETE', 'OPTIONS'])]
    public function deleteAnnonce(Request $request, int $id): Response
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

    #[Route('/admin/annonce/{id}', name: 'app_admin_edit_annonce', methods: ['PUT', 'OPTIONS'])]
    public function editAnnonce(Request $request, int $id): Response
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

        if (isset($data['date_active'])) {
            $annonce->setDateActive(new \DateTime($data['date_active']));
        }

        $this->em->persist($annonce);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Annonce modifiée par l'administrateur.",
            "result" => $annonce
        ], 200, [], ['groups' => ['annonce:info']]);
    }

    #[Route('/admin/stats', name: 'app_admin_stats', methods: ['GET', 'OPTIONS'])]
    public function getStats(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé."]);
        }

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
}
