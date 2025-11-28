<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\UserRepositoryInterface;
use App\Service\UserImporterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserImporterService $importerService
    ) {}

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryParams = $request->query->all();

        try {
            $users = $this->userRepository->getUsers($queryParams);
            $ageDistribution = $this->userRepository->getAgeDistribution();

        } catch (\Exception $e) {
            $this->addFlash('danger', 'API connection error: ' . $e->getMessage());
            $users = [];
            $ageDistribution = [
                "0-18"  => 0,
                "19-27" => 0,
                "28-40" => 0,
                "41+"   => 0,
            ];
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'queryParams' => $queryParams,
            'ageDistribution' => $ageDistribution,
        ]);
    }

    #[Route('/import', name: 'app_user_import', methods: ['POST'])]
    public function import(): Response
    {
        $this->importerService->importUsers();
        $this->addFlash('success', 'Test data has been imported!');
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userRepository->createUser($form->getData());
                $this->addFlash('success', 'User added.');
                return $this->redirectToRoute('app_user_index');
            } catch (ClientExceptionInterface $e) {
                $response = $e->getResponse();
                $errorMessage = 'Validation error from the Phoenix API.';
                if ($response) {
                    try {
                        $errorMessage = $response->toArray(false)['error'] ?? 'Validation error from the Phoenix API.';
                    } catch (\Exception $jsonE) {
                        error_log('Error parsing JSON from Phoenix API: ' . $jsonE->getMessage());
                    }
                }
                $this->addFlash('error', 'Creation failed (4xx): ' . $errorMessage);
            } catch (ServerExceptionInterface $e) {
                $this->addFlash('error', 'Creation failed (5xx): Phoenix API server error.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An unknown error occurred: ' . $e->getMessage());
            }
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Add user'
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->userRepository->getUser($id);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userRepository->updateUser($id, $form->getData());
            $this->addFlash('success', 'Data updated.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit user'
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->userRepository->deleteUser($id);
        $this->addFlash('success', 'User deleted.');
        return $this->redirectToRoute('app_user_index');
    }
}
