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
    public function index(SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
    {
        $sorties = $sortieRepository->findAll();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $siteRepository->findAll()
        ]);
    }

    /**
     * Méthode permettant de filtrer les sorties
     * @param Request $request
     * @param SortieRepository $sortieRepository
     * @return Response
     */
    #[Route('/filter', name: 'app_sortie_filter', methods: ['GET'])]
    public function filter(Request $request, SortieRepository $sortieRepository, SiteRepository $siteRepository): Response
    {
        $user = $this->getUser();
        $filter = $request->query->all();

        if (isset($filter['start_date'], $filter['end_date']) && !empty($filter['start_date']) && !empty($filter['end_date'])) {
            $sorties = $sortieRepository->findBetweenDates(new \DateTime($filter['start_date']), new \DateTime($filter['end_date']));
        } elseif (isset($filter['search']) && !empty($filter['search'])) {
            $sorties = $sortieRepository->findByKeyword($filter['search']);
        } elseif (isset($filter['planner']) && $filter['planner'] === 'organisteur') {
            $sorties = $sortieRepository->findBy(['planner' => $user]);
        } elseif (isset($filter['members']) && $filter['members'] === 'inscrit') {
            $sorties = $sortieRepository->findByUserParticipation($user, true);
        } elseif (isset($filter['members']) && $filter['members'] === 'noInscrit') {
            $sorties = $sortieRepository->findByUserParticipation($user, false);
        } elseif (isset($filter['status']) && $filter['status'] === 'terminée') {
            $sorties = $sortieRepository->findByStatus('terminée');
        } elseif (isset($filter['site']) && !empty($filter['site'])) {
            $sorties = $sortieRepository->findBy(['site' => $filter['site']]);
        } else {
            $sorties = $sortieRepository->findAll();
        }

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'sites' => $siteRepository->findAll(),
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
            $this->addFlash('success', 'Sortie créée avec succès !');
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
            $this->addFlash('success', 'Sortie modifiée avec succès !');
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
            $this->addFlash('success', 'Sortie annulée avec succès !');
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }
}
