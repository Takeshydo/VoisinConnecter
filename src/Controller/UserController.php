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
    public function __construct(private UserRepository $userRepo){}

    private function getSalt(): string
    {
        return md5($this->getParameter('app.password_salt'));
    }

    #[Route('/user/sign', name: 'app_auth_sign', methods: ['POST', 'OPTIONS'])]
    public function sign(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return $this->json(["status" => "error", "message" => "données incomplètes"]);
        }

        if ($this->userRepo->findOneBy(['email' => $data['email']])) {
            return $this->json(["status" => "error", "message" => "email déjà utilisé"]);
        }

        $newUser = new User();
        $salt = $this->getSalt();

        $newUser->setEmail($data["email"]);
        $newUser->setNom($data["pseudo"] ?? $data["nom"]);

        $hashedPassword = md5($data['password'] . $salt);
        $newUser->setPassword($hashedPassword);

        $newUser->setToken(hash('sha256', $data["email"] . $salt . uniqid()));

        $newUser->setCreatedAt(new \DateTime());
        $newUser->setRole('ROLE_USER');
        $newUser->setPhotoProfil("");

        $em->persist($newUser);
        $em->flush();

        return $this->json([
            "status" => "ok",
            "message" => "user created",
            "result" => $newUser
        ], 200, [], ['groups' => ['user:sign']]);
    }

    #[Route('/user/login', name: 'app_auth_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return $this->json(["status" => "error", "message" => "données vides"]);
        }

        $user = $this->userRepo->findOneBy(["email" => $data["email"]]);

        if (!$user) {
            return $this->json(["status" => "error", "message" => "user not found"]);
        }

        $salt = $this->getSalt();

        if (md5($data['password'] . $salt) === $user->getPassword()) {
            return $this->json([
                "status" => "ok",
                "message" => "login ok",
                "result" => $user
            ], 200, [], ['groups' => ['user:sign']]);
        }

        return $this->json(["status" => "error", "message" => "login failed, wrong password"]);
    }
}
