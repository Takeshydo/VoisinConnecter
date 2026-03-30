<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(private UserRepository $userRepo) {}

    private function getSalt(): string
    {
        return md5($this->getParameter('app.password_salt'));
    }

    #[Route('/user/register', name: 'app_user_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom']) || empty($data['email']) || empty($data['password'])) {
            return $this->json([
                "status" => "error",
                "message" => "Les champs 'nom', 'email' et 'password' sont obligatoires." ],);
        }

        if ($this->userRepo->findOneBy(['email' => $data['email']])) {
            return $this->json(["status" => "error", "message" => "Cet email est déjà utilisé."],);
        }

        $user = new User();
        $salt = $this->getSalt();

        $user->setNom($data['nom']);
        $user->setEmail($data['email']);
        $user->setPassword(md5($data['password'] . $salt));

        $user->setRole('ROLE_USER');
        $user->setCreatedAt(new \DateTime());
        $user->setPhotoProfil('');
        $user->setToken(hash('sha256', uniqid() . $data['email']));
        $em->persist($user);
        $em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Utilisateur créé avec succès",
            "result" => [
                "id" => $user->getId(),
                "nom" => $user->getNom(),
                "email" => $user->getEmail(),
                "role" => $user->getRole(),
                "token" => $user->getToken()
            ]
        ],);
    }

    #[Route('/user/login', name: 'app_user_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(["status" => "error", "message" => "Email et mot de passe requis."],);
        }

        $user = $this->userRepo->findOneBy(["email" => $data["email"]]);

        if (!$user) {
            return $this->json(["status" => "error", "message" => "Utilisateur introuvable."],);
        }

        $salt = $this->getSalt();

        if (md5($data['password'] . $salt) === $user->getPassword()) {
            return $this->json([
                "status" => "ok",
                "message" => "Connexion réussie",
                "token" => $user->getToken(),
                "role" => $user->getRole()
            ],);
        }

        return $this->json(["status" => "error", "message" => "Mot de passe incorrect."],);
    }
}
