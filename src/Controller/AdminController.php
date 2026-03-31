<?php

namespace App\Controller;

use App\Repository\AdminRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(
        private AdminRepository $adminRepo,
        private UserRepository $userRepo,
        private EntityManagerInterface $em
    ) {}

    /**
     * Méthode privée pour vérifier si la requête provient bien d'un Admin valide
     */
    private function getAuthenticatedAdmin(Request $request)
    {
        $token = $request->headers->get('Authorization');
        if (!$token) return null;

        $token = str_replace('Bearer ', '', $token);
        $admin = $this->adminRepo->findOneBy(['token' => $token]);

        if ($admin && $admin->getRole() === 'ROLE_ADMIN') {

            // AJOUT : Vérification de l'expiration du token (Exemple : validité de 24 heures)
            $tokenDate = $admin->getTokenCreatedAt();

            if (!$tokenDate) {
                return null; // Si pas de date, le token est considéré comme invalide par précaution
            }

            $now = new \DateTime();
            $interval = $now->diff($tokenDate);
            $hours = $interval->h + ($interval->days * 24);

            if ($hours >= 2) { // Limite fixée à 24h (tu peux changer cette valeur)
                return null; // Token expiré
            }

            return $admin;
        }

        return null;
    }

    #[Route('/admin/profile', name: 'app_admin_update_profile', methods: ['PUT', 'OPTIONS'])]
    public function updateProfile(Request $request): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé ou session expirée."]);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données invalides."]);
        }

        if (isset($data['nom'])) {
            $admin->setNom($data['nom']);
        }
        if (isset($data['photoProfil'])) {
            $admin->setPhotoProfil($data['photoProfil']);
        }

        $this->em->persist($admin);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Profil administrateur mis à jour.",
            "result" => ["nom" => $admin->getNom(), "photoProfil" => $admin->getPhotoProfil()]
        ]);
    }

    #[Route('/admin/user/{id}', name: 'app_admin_delete_user', methods: ['DELETE', 'OPTIONS'])]
    public function deleteUser(Request $request, int $id): Response
    {
        $admin = $this->getAuthenticatedAdmin($request);
        if (!$admin) {
            return $this->json(["status" => "error", "message" => "Accès non autorisé ou session expirée."]);
        }

        $userToDelete = $this->userRepo->find($id);

        if (!$userToDelete) {
            return $this->json(["status" => "error", "message" => "Utilisateur introuvable."]);
        }

        $this->em->remove($userToDelete);
        $this->em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "L'utilisateur a été supprimé avec succès."
        ]);
    }
}
