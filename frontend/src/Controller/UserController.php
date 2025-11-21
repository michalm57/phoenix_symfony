<?php

namespace App\Controller;

use App\Form\UserType;
use App\Service\PhoenixApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(private PhoenixApiService $apiService) {}

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $queryParams = $request->query->all();

        try {
            $users = $this->apiService->getUsers($queryParams);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'API connection error: ' . $e->getMessage());
            $users = [];
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'queryParams' => $queryParams
        ]);
    }

    #[Route('/import', name: 'app_user_import', methods: ['POST'])]
    public function import(): Response
    {
        $this->apiService->importUsers();
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
                $this->apiService->createUser($form->getData());
                $this->addFlash('success', 'User added.');
                return $this->redirectToRoute('app_user_index');
            } catch (ClientExceptionInterface $e) {
                $errorMessage = $e->getResponse()->toArray(false)['error'] ?? 'Validation error from the Phoenix API.';
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
        $user = $this->apiService->getUser($id);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->apiService->updateUser($id, $form->getData());
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
        $this->apiService->deleteUser($id);
        $this->addFlash('success', 'User deleted.');
        return $this->redirectToRoute('app_user_index');
    }
}
