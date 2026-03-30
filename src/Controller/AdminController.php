<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\Questions;
use App\Entity\Sondages;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    #[Route('/api/admin/sondage', name: 'admin_sondage_create', methods: ['POST', 'OPTIONS'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        AdminRepository $adminRepo
    ): Response {

        $token = $request->headers->get('Authorization');

        if (!$token) {
            return $this->json(["status" => "error", "message" => "token not found"], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        $admin = $adminRepo->findOneBy(['token' => $token]);

        if (!$admin || $admin->getRole() !== 'ROLE_ADMIN') {
            return $this->json([
                'error' => 'accès refusé, tu dois être admin'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(["status" => "error", "message" => "données JSON invalides"], 400);
        }

        $sondage = new Sondages();
        $sondage->setName($data['name']);
        $sondage->setIsActive($data['visible'] ?? false);

        $question = new Questions();
        $question->setLabel($data['question_label']);
        $question->setMultiple(false);
        $question->setSondage($sondage);

        if (isset($data['choices']) && is_array($data['choices'])) {
            foreach ($data['choices'] as $choiceLabel) {
                $choix = new Choix();
                $choix->setLabel($choiceLabel);
                $choix->setQuestions($question);
                $em->persist($choix);
            }
        }

        $em->persist($sondage);
        $em->persist($question);
        $em->flush();

        return $this->json([
            'status' => 'Sondage QCM créé !',
            'admin' => $admin->getNom()
        ], 201);
    }
}
