<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepo,
        private AdminRepository $adminRepo
    ) {}

    private function getSalt(): string
    {
        return md5($this->getParameter('app.password_salt'));
    }

    #[Route('/auth/register', name: 'app_auth_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom']) || empty($data['email']) || empty($data['password'])) {
            return $this->json(["status" => "error", "message" => "Données incomplètes."]);
        }

        if ($this->userRepo->findOneBy(['email' => $data['email']]) || $this->adminRepo->findOneBy(['email' => $data['email']])) {
            return $this->json(["status" => "error", "message" => "Email déjà utilisé."]);
        }

        $user = new User();
        $salt = $this->getSalt();

        $user->setNom($data['nom']);
        $user->setEmail($data['email']);

        // Hachage du mot de passe
        $hashedPassword = md5($data['password'] . $salt);
        $user->setPassword($hashedPassword);

        // --- GÉNÉRATION DU TOKEN AVEC EMAIL + PASSWORD ---
        $tokenRaw = $data['email'] . $hashedPassword . uniqid('token_', true);
        $user->setToken(hash('sha256', $tokenRaw));

        // AJOUT : Date de création du token
        $user->setTokenCreatedAt(new \DateTimeImmutable());

        $user->setRole('ROLE_USER');
        $user->setCreatedAt(new \DateTime());
        $user->setPhotoProfil('');

        $em->persist($user);
        $em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "Compte créé !",
            "result" => ["nom" => $user->getNom(), "token" => $user->getToken()]
        ]);
    }

    #[Route('/auth/login', name: 'app_auth_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(["status" => "error", "message" => "Email et mot de passe requis."]);
        }

        $salt = $this->getSalt();
        $hashedPassword = md5($data['password'] . $salt);

        $account = $this->userRepo->findOneBy(["email" => $data["email"]]) ?? $this->adminRepo->findOneBy(["email" => $data["email"]]);

        if ($account && $account->getPassword() === $hashedPassword) {

            // AJOUT : Régénération du token à chaque connexion pour prolonger la session
            $newTokenRaw = $account->getEmail() . $hashedPassword . uniqid('token_', true);
            $account->setToken(hash('sha256', $newTokenRaw));
            $account->setTokenCreatedAt(new \DateTimeImmutable());


            $em->persist($account);
            $em->flush();

            return $this->json([
                "status" => "ok",
                "message" => "Connecté !",
                "token" => $account->getToken(),
                "role" => $account->getRole()
            ], );
        }

        return $this->json(["status" => "error", "message" => "Identifiants invalides."]);
    }
}
