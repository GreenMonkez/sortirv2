<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Sortie;
use App\EntityListener\SortieArchiver;
use App\Form\CommentType;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\MotifAnnulationRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use App\Repository\UserRepository;
use App\Service\GeoApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
#[IsGranted('ROLE_USER')]
final class SortieController extends AbstractController
{
    /**
     * Méthode permettant d'afficher toutes les sorties avec des filtres
     * @param SortieRepository $sortieRepository
     * @return Response
     */
    #[Route(name: 'app_sortie_index', methods: ['GET'])]
    public function index(
        SortieRepository $sortieRepository,
        SiteRepository   $siteRepository,
        SortieArchiver   $sortieArchiver
    ): Response
    {
        $sorties = $sortieRepository->findAll();
        $sortieArchiver->archiverSorties();

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
    public function filter(
        Request          $request,
        SortieRepository $sortieRepository,
        SiteRepository   $siteRepository
    ): Response
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
            $sorties = $sortieRepository->findByUserNotParticipation($user);
        } elseif (isset($filter['status']) && $filter['status'] === 'ouverte') {
            $sorties = $sortieRepository->findByStatus('Ouverte');
        } elseif (isset($filter['status']) && $filter['status'] === 'terminée') {
            $sorties = $sortieRepository->findByStatus('Terminée');
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
    public function new(
        Request                $request,
        GeoApiService          $geoApiService,
        EntityManagerInterface $entityManager,
        EtatRepository         $etatRepository
    ): Response
    {
        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);


      if ($form->isSubmitted() && $request->get('sortie')['lieu']['departement'] && $request->get('sortie')['lieu']['city']) {

            // Récupérer les données des champs non mappés
            $region = $sortie->getLieu()->getRegion();
            $departement = $request->get('sortie')['lieu']['departement'];
            $ville = $request->get('sortie')['lieu']['city'];

          // Ajouter les données récupérées au modèle
          $sortie->getLieu()?->setRegion($region);
          $sortie->getLieu()?->setDepartement($departement);
          $sortie->getLieu()?->setCity($ville);


            // Conserver les données existantes
            $sortie->setDuration($sortie->getDuration() * 60);
            $sortie->setStatus($etatRepository->find(2));
            $sortie->setPlanner($this->getUser());

            // Sauvegarder la sortie
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie créée avec succès !');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);

        }

        return $this->render('sortie/new.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    /**
     * Méthode permettant d'afficher une sortie par son id et d'ajouter un commentaire
     * @param Sortie $sortie
     * @return Response
     */
    #[Route('/{id}', name: 'app_sortie_show', methods: ['GET', 'POST'])]
    public function show(
        Request                   $request,
        Sortie                    $sortie,
        SiteRepository            $siteRepository,
        EntityManagerInterface    $entityManager,
        MotifAnnulationRepository $motifAnnulationRepository
    ): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($sortie->getId());
        if (!$sortie) {
            throw $this->createNotFoundException('Sortie not found');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser());
            $comment->setSortie($sortie);
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès !');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'site' => $siteRepository->findAll(),
            'form' => $form,
            'motifs' => $motifAnnulationRepository->findAll(),
        ]);
    }

    /**
     * Méthode permettant de réagir à un commentaire
     * @param Comment $comment
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/comment/{id}/react', name: 'comment_react', methods: ['POST'])]
    public function react(
        Comment                $comment,
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $emoji = $request->request->get('emoji');
        $user = $this->getUser();

        // Vérifie si l'emoji est valide
        $validEmojis = ['👍', '❤️', '😂'];
        if ($emoji && in_array($emoji, $validEmojis, true)) {

            // Vérifie si l'utilisateur a déjà réagi avec cet emoji
            $existingReaction = array_filter($comment->getReactions(), function ($reaction) use ($emoji, $user) {

                return $reaction['emoji'] === $emoji && $reaction['user'] === $user->getId();
            });

            if ($existingReaction) {
                // Si une réaction existe, on l'annule
                $comment->removeReaction($emoji, $user);
            } else {
                // Sinon, on ajoute la réaction
                $comment->addReaction($emoji, $user);
            }

            // Enregistre les modifications dans la base de données
            $entityManager->persist($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $comment->getSortie()->getId()]);
    }

    #[Route('/{id}/inscription', name: 'app_sortie_sub')]
    public function inscription(
        Request                $request,
        Sortie                 $sortie,
        EntityManagerInterface $entityManager,
        MailerInterface        $mailer
    ): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez si le statut de la sortie est "ouverte"
        if ($sortie->getStatus()->getName() !== 'Ouverte') {
            $this->addFlash('error', 'Vous ne pouvez vous inscrire qu\'à des sorties ouvertes.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez si l'utilisateur est déjà inscrit
        if ($sortie->getMembers()->contains($user)) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez si la sortie a atteint la limite d'inscriptions
        if ($sortie->getMembers()->count() >= $sortie->getLimitMembers()) {
            $this->addFlash('error', 'La sortie a atteint le nombre maximum de participants.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez le token CSRF et inscrivez l'utilisateur
        if ($this->isCsrfTokenValid('inscription' . $sortie->getId(), $request->getPayload()->getString('_token'))) {
            $sortie->addMember($user);
            $entityManager->flush();
            // Envoi de l'email
            $email = (new Email())
                ->from('noreply@votreapp.com')
                ->to($user->getEmail())
                ->subject('Confirmation d\'inscription à la sortie')
                ->text(sprintf(
                    'Bonjour %s %s, vous êtes inscrit à la sortie "%s" prévue le %s.',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $sortie->getNom(),
                    $sortie->getStartAt()->format('d/m/Y H:i')
                ));

            $mailer->send($email);

            $this->addFlash('success', 'Inscription réussie ! Un email de confirmation vous a été envoyé.');
        }
        else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/desinscription', name: 'app_sortie_unSub')]
    public function desinscription(
        Request                $request,
        Sortie                 $sortie,
        EntityManagerInterface $entityManager,
        MailerInterface        $mailer
    ): Response
    {
        $user = $this->getUser();

        // Vérifiez si l'utilisateur est connecté
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous désinscrire.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez si l'utilisateur est inscrit à la sortie
        if (!$sortie->getMembers()->contains($user)) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez si la sortie est ouverte ou clôturée
        if ($sortie->getStatus()->getName() !== 'Ouverte' && $sortie->getStatus()->getName() !== 'Cloturée') {
            $this->addFlash('error', 'Vous ne pouvez vous désinscrire que d\'une sortie ouverte ou clôturée.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifiez le token CSRF et désinscrivez l'utilisateur
        if ($this->isCsrfTokenValid('desinscription' . $sortie->getId(), $request->request->get('_token'))) {
            $sortie->removeMember($user);
            $entityManager->persist($sortie); // Persist pour s'assurer que les changements sont suivis
            $entityManager->flush();
            $email = (new Email())
                ->from('noreply@votreapp.com')
                ->to($user->getEmail())
                ->subject('Confirmation de désinscription à la sortie')
                ->text(sprintf(
                    'Bonjour %s %s, vous êtes désinscrit de la sortie "%s" prévue le %s.',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $sortie->getNom(),
                    $sortie->getStartAt()->format('d/m/Y H:i')
                ));

            $mailer->send($email);

            $this->addFlash('success', 'Désinscription réussie ! Un email de confirmation vous a été envoyé.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Méthode permettant de modifier une sortie
     * @param Request $request
     * @param Sortie $sortie
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_sortie_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request                   $request,
        Sortie                    $sortie,
        EntityManagerInterface    $entityManager,
        MotifAnnulationRepository $motifAnnulationRepository
    ): Response
    {
        // Vérifiez si l'utilisateur est le planificateur de la sortie
        $form = $this->createForm(SortieType::class, $sortie, [
            'lieu' => [
                'region' => $request->get('sortie')['lieu']['region'] ?? [],
                'departement' => $request->get('sortie')['lieu']['departement'] ?? [],
            ]
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();
            $this->addFlash('success', 'Sortie modifiée avec succès !');
            return $this->redirectToRoute('app_sortie_index');
        }

        $regionCode = $sortie->getLieu()?->getRegion();
        $departementCode = $sortie->getLieu()?->getDepartement();
        $cityName = $sortie->getLieu()?->getCity();

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'motifs' => $motifAnnulationRepository->findAll(),
            'region_code' => $regionCode,
            'departement_code' => $departementCode,
            'city_name' => $cityName
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
    public function delete(
        Request                   $request,
        Sortie                    $sortie,
        EntityManagerInterface    $entityManager,
        EtatRepository            $etatRepository,
        MotifAnnulationRepository $motifAnnulationRepository
    ): Response
    {
        if ($sortie->getPlanner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        // Vérifiez si le statut de la sortie est "terminée" ou "annulée"
        if ($sortie->getStatus()->getName() === 'Terminée' || $sortie->getStatus()->getName() === 'Annulée') {
            $this->addFlash('error', 'Vous ne pouvez pas annuler une sortie déjà terminée ou annulée.');
            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->getPayload()->getString('_token'))) {
            $sortie->setStatus($etatRepository->findOneBy(['name' => 'Annulée']));
            $cancel = $motifAnnulationRepository->find($request->get('motif'));
            if ($cancel === null) {
                $this->addFlash('danger', 'Erreur lors de l\'annulation de la sortie !');
                return $this->redirectToRoute('app_sortie_edit', [], Response::HTTP_SEE_OTHER);
            }
            $cancel->setCommentaire($request->get('commentaire'));
            $sortie->setMotifsCancel($cancel);
            $entityManager->flush();
            $this->addFlash('success', 'Sortie annulée avec succès !');


            return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('danger', 'Erreur lors de l\'annulation de la sortie !');
        return $this->redirectToRoute('app_sortie_edit', [], Response::HTTP_SEE_OTHER);
    }


}