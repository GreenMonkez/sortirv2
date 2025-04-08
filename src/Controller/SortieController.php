<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
#[IsGranted('ROLE_USER')]
final class SortieController extends AbstractController
{

    public function __construct(private EntityManagerInterface $entityManager,
                                private EtatRepository $etatRepository)

    {
    }

    /**
     * Méthode permettant d'afficher toutes les sorties avec des filtres
     * @param SortieRepository $sortieRepository
     * @return Response
     */
    #[Route(name: 'app_sortie_index', methods: ['GET'])]
    public function index(SortieRepository $sortieRepository): Response
    {
        $sorties = $sortieRepository->findAll();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties
        ]);
    }

    /**
     * Méthode permettant de créer une sortie
     * @param Request $request
     * @param SiteRepository $repository
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/new', name: 'app_sortie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SiteRepository $repository, UserRepository $userRepository): Response
    {

        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {
                $sortie->setDuration($sortie->getDuration() * 60);
                $sortie->setStatus($this->etatRepository->find(2));
                $sortie->setPlanner($this->getUser());
            $this->entityManager->persist($sortie);

            $this->entityManager->flush();

            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }
    /**
     * Méthode permettant d'afficher une sortie par son id
     * @param Sortie $sortie
     * @return Response
     */
    #[Route('/{id}', name: 'app_sortie_show', methods: ['GET'])]
    public function show(Sortie $sortie): Response
    {
        $sortie= $this->entityManager->getRepository(Sortie::class)->find($sortie->getId());
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie not found');
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    /**
     * Méthode permettant de modifier une sortie
     * @param Request $request
     * @param Sortie $sortie
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_sortie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * Méthode permettant d'annuler une sortie
     * @param Request $request
     * @param Sortie $sortie
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_sortie_delete', methods: ['POST'])]
    public function delete(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sortie->getId(), $request->getPayload()->getString('_token'))) {
            $sortie->setStatus($this->etatRepository->find(6));
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
