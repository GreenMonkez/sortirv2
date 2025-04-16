<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Group;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/group')]
#[IsGranted('ROLE_USER')]
final class GroupController extends AbstractController
{
    /**
     * Méthode pour afficher la liste des groupes
     * @param GroupRepository $groupRepository
     * @return Response
     */
    #[Route(name: 'app_group_index', methods: ['GET'])]
    public function index(
        GroupRepository $groupRepository,
        SiteRepository  $siteRepository
    ): Response
    {
        return $this->render('group/index.html.twig', [
            'groups' => $groupRepository->findAll(),
            'sites' => $siteRepository->findAll()
        ]);
    }

    /***
     * Méthode pour filtrer les groupes
     * @param Request $request
     * @param GroupRepository $groupRepository
     * @param SiteRepository $siteRepository
     * @return Response
     */
    #[Route('/filter', name: 'app_group_filter', methods: ['GET'])]
    public function filter(
        Request         $request,
        GroupRepository $groupRepository,
        SiteRepository  $siteRepository
    ): Response
    {
        $user = $this->getUser();
        $filter = $request->query->all();

        $myGroups = [];

        if (!empty($filter['owner']) && $filter['owner'] === 'organisateur') {
            $myGroups = $groupRepository->findBy(['owner' => $user]);
        } elseif (!empty($filter['teammate']) && $filter['teammate'] === 'inscrit') {
            $myGroups = $groupRepository->createQueryBuilder('g')
                ->join('g.teammate', 't')
                ->where('t = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        } elseif (isset($filter['site']) && !empty($filter['site'])) {
            $myGroups = $groupRepository->findBy(['site' => $filter['site']]);
        } else {
            $myGroups = $groupRepository->findAll();
        }

        return $this->render('group/index.html.twig', [
            'groups' => $myGroups,
            'sites' => $siteRepository->findAll(),
        ]);
    }

    /**
     * Méthode pour créer un groupe
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/new', name: 'app_group_new', methods: ['GET', 'POST'])]
    public function new(
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {

        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group->setOwner($this->getUser());
            $group->setSite($this->getUser()->getSite());
            foreach ($form->get('teammate')->getData() as $teammate) {
                $group->addTeammate($teammate);
            }
            // INSTANCIER UNE CONVERSATION LORS DE LA CREATION DU GROUPE
            $conversation = new Conversation();
            $conversation->setPrivateGroup($group);
            $conversation->setName($group->getName());
            $group->setConversation($conversation);
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success', 'Groupe créé avec succès !');

            return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);

        }
        return $this->render('group/new.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }


    /**
     * Méthode pour rejoindre un groupe
     * @param Request $request
     * @param Group $group
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/join', name: 'app_group_join', methods: ['POST'])]
    public function joinGroup(
        Request                $request,
        Group                  $group,
        EntityManagerInterface $entityManager,
        MailerInterface        $mailer
    ): Response
    {
        $user = $this->getUser();


        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour rejoindre un groupe !');
            return $this->redirectToRoute('app_group_index');
        }

        if ($group->getOwner() === $user) {
            $this->addFlash('danger', 'Vous ne pouvez pas rejoindre votre propre groupe !');
            return $this->redirectToRoute('app_group_index');
        }


        if ($group->getTeammate()->contains($user)) {
            $this->addFlash('danger', 'Vous êtes déjà membre de ce groupe !');
            return $this->redirectToRoute('app_group_index');
        }


        if (!$this->isCsrfTokenValid('join' . $group->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Erreur lors de la validation du token CSRF.');
            return $this->redirectToRoute('app_group_index');
        }

        $group->addTeammate($user);
        $entityManager->flush();


        try {
            $email = (new Email())
                ->from('noreply@votreapp.com')
                ->to($user->getEmail())
                ->subject('Vous avez rejoint un groupe !')
                ->text(sprintf(
                    'Bonjour %s %s, vous avez rejoint le groupe "%s" créé par %s.',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $group->getName(),
                    $group->getOwner()->getFirstName()
                ));

            $mailer->send($email);
            $this->addFlash('success', 'Vous avez rejoint le groupe avec succès !');

        } catch (\Exception $e) {
            $this->addFlash('danger', 'Vous avez rejoint le groupe, mais l\'email de confirmation n\'a pas pu être envoyé.');
        }

        return $this->redirectToRoute('app_group_index');
    }


    /**
     * Méthode pour quitter un groupe
     * @param Request $request
     * @param Group $group
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/leave', name: 'app_group_leave', methods: ['POST'])]
    public function leaveGroup(
        Request                $request,
        Group                  $group,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();

        if ($group->getOwner() === $user) {
            $this->addFlash('danger', 'Vous ne pouvez pas quitter votre propre groupe !');
            return $this->redirectToRoute('app_group_index');
        }

        if (!$group->getTeammate()->contains($user)) {
            $this->addFlash('danger', 'Vous n\'êtes pas membre de ce groupe !');
            return $this->redirectToRoute('app_group_index');
        }

        // Vérifiez le token CSRF et retirez l'utilisateur
        if ($this->isCsrfTokenValid('leave' . $group->getId(), $request->request->get('_token'))) {
            $group->removeTeammate($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vous avez quitté le groupe avec succès !');
            return $this->redirectToRoute('app_group_index');
        } else {
            $this->addFlash('danger', 'Erreur lors de la validation du token CSRF.');
            return $this->redirectToRoute('app_group_index');
        }
    }

    /**
     * Méthode pour afficher le détail d'un groupe
     * @param Group $group
     * @return Response
     */
    #[Route('/{id}', name: 'app_group_show', methods: ['GET'])]
    public function show(Group $group): Response
    {
        $futureSorties = [];

        // Récupérer les sorties futures des membres
        foreach ($group->getTeammate() as $user) {
            foreach ($user->getSorties() as $sortie) {
                if ($sortie->getStartAt() > new \DateTimeImmutable()) {
                    $futureSorties[] = $sortie;
                }
            }
        }


        return $this->render('group/show.html.twig', [
            'group' => $group,
            'futureSorties' => $futureSorties,
        ]);
    }

    /**
     * Méthode pour modifier un groupe
     * @param Request $request
     * @param Group $group
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_group_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        Group                  $group,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Groupe modifié avec succès !');

            return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    /**
     * Méthode pour supprimer un groupe
     * @param Request $request
     * @param Group $group
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}', name: 'app_group_delete', methods: ['POST'])]
    public function delete(
        Request                $request,
        Group                  $group,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!($group->getOwner() === $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $group->getId(), $request->request->get('_token'))) {
            $entityManager->remove($group);
            $entityManager->flush();
            $this->addFlash('success', 'Groupe supprimé avec succès !');
        }

        return $this->redirectToRoute('app_group_index', [], Response::HTTP_SEE_OTHER);
    }
}
