<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

final class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
    ) {}

    private function getSalt(): string
    {
        return md5($this->getParameter('app.password_salt'));
    }

    #[Route('/auth/register', name: 'app_auth_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(["status" => "error", "message" => "Données incomplètes."]);
        }

        if ($this->userRepo->findOneBy(['email' => $data['email']])) {
            return $this->json(["status" => "error", "message" => "Email déjà utilisé."]);
        }

        $user = new User();
        $salt = $this->getSalt();

        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);

        // Hachage du mot de passe
        $hashedPassword = md5($data['password'] . $salt);
        $user->setPassword($hashedPassword);

        // --- tokenraw => email+uniqid => token en sha256 ---
        $tokenRaw = $data['email'] . uniqid('token_', true);
        $user->setToken(hash('sha256', $tokenRaw));

        $user->setRole(['ROLE_USER']);
        $user->setCreatedAt(new \DateTime());
        $user->setPhotoProfil('');

        $em->persist($user);
        $em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Compte créé !",
            "result" => $user,
        ], 200, [], ['groups' => ['user:info']]);
    }

    #[Route('/auth/login', name: 'app_auth_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(["status" => "error", "message" => "Email et mot de passe requis."]);
        }

        $account = $this->userRepo->findOneBy(['email' => $data['email']]);

        if (!$account) {
            return $this->json(["status" => "error", "message" => "Email inexistant."]);
        }

        $salt = $this->getSalt();

        if(md5(($data['password'] . $salt)) === $account->getPassword()) {
            return $this->json([
                "status" => "ok",
                "message" => "Connecté !",
                "result" => $account,
            ], 200, [], ['groups' => ['user:info']]);
        } else{
            return $this->json(["status" => "error", "message" => "Email ou mot de passe invalides."]);
        }
    }

    #[Route('/user/token', name: 'user_token', methods: ['GET', 'OPTIONS'])]
    public function token(Request $request): Response
    {
        $token = $request->headers->get('Authorization');

        if(!$token) {
            return $this->json(["status" => "error", "message" => "Aucun token."]);
        }
        $token = substr($token, 7);

        $user = $this->userRepo->findOneBy(['token' => $token]);

        if(!$user) {
            return $this->json(["status"=>"error", "message"=>"Token not valide."]);
        }

        return $this->json([
            "status" => "ok",
            "message" => "connected",
            "result" => $user,
        ], 200, [], ['groups' => ['user:info']]);
    }

    #[Route('/auth/logout', name: 'app_auth_logout', methods: ['POST', 'OPTIONS'])]
    public function logout(Request $request, EntityManagerInterface $em): Response
    {
        // 1. Récupérer le token depuis les headers (comme tu le fais dans AdminController)
        $tokenHeader = $request->headers->get('Authorization');

        if (!$tokenHeader) {
            return $this->json(["status" => "error", "message" => "Aucun token fourni."]);
        }

        // On nettoie la chaîne pour ne garder que le token
        $token = str_replace('Bearer ', '', $tokenHeader);

        // 2. Chercher à qui appartient ce token (User ou Admin)
        $account = $this->userRepo->findOneBy(['token' => $token]) ?? $this->adminRepo->findOneBy(['token' => $token]);

        if (!$account) {
            return $this->json(["status" => "error", "message" => "Token invalide ou compte déjà déconnecté."]);
        }

        $em->persist($account);
        $em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Déconnexion réussie."
        ]);
    }


}
