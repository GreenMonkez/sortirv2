<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Form\CsvUploadType;
use App\Form\UserFilterType;
use App\Form\UserType;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository     $userRepository,
        PaginatorInterface $paginator,
        Request            $request
    ): Response
    {
        $form = $this->createForm(UserFilterType::class, null, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);

        $queryBuilder = $userRepository->createQueryBuilder('u');

        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            $sort = $filters['sort'] ?? null;
            $order = $filters['order'] ?? 'ASC';

            if (!empty($filters['pseudo'])) {
                $queryBuilder->andWhere('u.pseudo LIKE :pseudo')
                    ->setParameter('pseudo', '%' . $filters['pseudo'] . '%');
            }

            if (!empty($filters['firstName'])) {
                $queryBuilder->andWhere('u.firstName LIKE :firstName')
                    ->setParameter('firstName', '%' . $filters['firstName'] . '%');
            }

            if (!empty($filters['lastName'])) {
                $queryBuilder->andWhere('u.lastName LIKE :lastName')
                    ->setParameter('lastName', '%' . $filters['lastName'] . '%');
            }

            if (!empty($filters['email'])) {
                $queryBuilder->andWhere('u.email LIKE :email')
                    ->setParameter('email', '%' . $filters['email'] . '%');
            }

            if (isset($filters['isActive'])) {
                $queryBuilder->andWhere('u.isActive = :isActive')
                    ->setParameter('isActive', $filters['isActive']);
            }

            if ($sort) {
                $queryBuilder->orderBy('u.' . $sort, $order);
            }
        }

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request                      $request,
        EntityManagerInterface       $entityManager,
        UserPasswordHasherInterface  $passwordHasher,
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface              $mailer
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upload the profile picture
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Generate a unique filename for the new photo
                $newFilename = uniqid() . '.' . $photoFile->guessExtension();

                try {
                    // Move the file to the directory where profile pictures are stored
                    $photoFile->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                    return $this->redirectToRoute('app_user_new');
                }

                // Update the 'photo' property to store the file name
                $user->setPhoto($newFilename);
            }

            // Generate a temporary password
            $temporaryPassword = bin2hex(random_bytes(4));
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $temporaryPassword
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // Generate a reset token
            $resetToken = $resetPasswordHelper->generateResetToken($user);

            // Send the email
            $email = (new Email())
                ->from('sortirv2@campus-eni.fr')
                ->to($user->getEmail())
                ->subject('[Sortir V2] Bienvenue !')
                ->html(sprintf(
                    '<p>Kakou kakou %s,</p>
                            <p>Un compte a été créé pour vous. Veuillez cliquer sur le lien ci-dessous pour définir votre mot de passe :</p>
                            <p><a href="%s">Définir mon mot de passe</a></p>',
                    $user->getFirstName(),
                    $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)
                ));

            $mailer->send($email);

            $this->addFlash('success', 'L\'utilisateur a bien été créé !');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        User $user
    ): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/edit', name: 'app_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                     $request,
        User                        $user,
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // Check if the user is the same as the logged-in user or if the user has ROLE_ADMIN
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have permission to edit this user.');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $oldPassword = $user->getPassword();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upload the profile picture
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Get the old photo filename
                $oldPhoto = $user->getPhoto();

                // Generate a unique filename for the new photo
                $newFilename = uniqid() . '.' . $photoFile->guessExtension();

                try {
                    // Move the file to the directory where profile pictures are stored
                    $photoFile->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }

                // Delete the old photo if it exists
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('photo_directory') . '/' . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Update the 'photo' property to store the file name
                $user->setPhoto($newFilename);
            }

            if (!$this->isGranted('ROLE_ADMIN')) {
                // Check if the password field is empty
                $newPassword = $form->get('password')->getData();
                if ($newPassword) {
                    $user->setPassword(
                        $passwordHasher->hashPassword(
                            $user,
                            $newPassword
                        )
                    );
                } else {
                    $user->setPassword($oldPassword);
                }
            }

            $entityManager->flush();

            // If the user is not an admin, redirect to sortie index, else, redirect to user index
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('success', 'Votre compte a bien été mis à jour !');
                return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('success', 'L\'utilisateur a bien été mis à jour !');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request                $request,
        User                   $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->getString('_token'))) {
            // Get the old photo filename
            $oldPhoto = $user->getPhoto();

            $user->setEmail('anonyme_' . uniqid() . '@campus-eni.fr');
            $user->setPseudo('Utilisateur_' . uniqid());
            $user->setFirstName('Anonyme');
            $user->setLastName('Anonyme');
            $user->setPhoneNumber(null);
            $user->setIsActive(false);
            $user->setPassword('');
            $user->setPhoto(null);

            // Delete the old photo if it exists
            if ($oldPhoto) {
                $oldPhotoPath = $this->getParameter('photo_directory') . '/' . $oldPhoto;
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }

            $entityManager->flush();
        }

        $this->addFlash('success', 'L\'utilisateur a bien été supprimé !');

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/import', name: 'app_user_import', methods: ['GET', 'POST'])]
    public function importCsv(
        Request                      $request,
        EntityManagerInterface       $entityManager,
        UserPasswordHasherInterface  $passwordHasher,
        SiteRepository               $siteRepository,
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface              $mailer
    ): Response
    {
        $form = $this->createForm(CsvUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csv_file')->getData();

            if ($csvFile) {
                $filePath = $csvFile->getPathname();
                $importedUsers = []; // Array to store imported users
                $importedCount = 0; // Counter for imported users
                $errors = []; // Array to store error messages
                $seenEmails = []; // Array to track seen emails
                $seenPseudos = []; // Array to track seen pseudos

                if (($handle = fopen($filePath, 'r')) !== false) {
                    $entityManager->beginTransaction(); // Start transaction

                    try {
                        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                            $data = array_map('trim', $data); // Remove whitespace from each field

                            // Check if the line has the correct number of fields
                            if (count($data) !== 6) {
                                $errors[] = 'Ligne invalide : ' . implode(', ', $data);
                                continue;
                            }

                            // Map the CSV fields to variables
                            [$email, $pseudo, $lastName, $firstName, $phoneNumber, $siteName] = $data;

                            // Validate the email
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $errors[] = "Email invalide : $email";
                                continue;
                            }

                            // Check if the email is from the allowed domain
                            if (!str_ends_with($email, '@campus-eni.fr')) {
                                $errors[] = "Email non autorisé : $email";
                                continue;
                            }

                            // Check if there are any duplicate emails or pseudos in the current import
                            if (in_array($email, $seenEmails, true) || in_array($pseudo, $seenPseudos, true)) {
                                $errors[] = "Doublon détecté : $email ou $pseudo";
                                continue;
                            }

                            // Check if the email already exists in the database
                            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                            if ($existingUser) {
                                $errors[] = "Email existant : $email";
                                continue;
                            }

                            // Check if the pseudo already exists in the database
                            $existingPseudo = $entityManager->getRepository(User::class)->findOneBy(['pseudo' => $pseudo]);
                            if ($existingPseudo) {
                                $errors[] = "Pseudo existant : $pseudo";
                                continue;
                            }

                            // Validate the site
                            $site = $siteRepository->findOneBy(['name' => $siteName]);
                            if (!$site) {
                                $errors[] = "Site introuvable : $siteName";
                                continue;
                            }

                            $user = new User();
                            $user->setEmail($email);
                            $user->setPseudo($pseudo);
                            $user->setLastName($lastName);
                            $user->setFirstName($firstName);
                            $user->setPhoneNumber($phoneNumber);
                            $user->setPhoto(null);
                            $user->setSite($site);
                            $user->setIsActive(true);

                            // Generate a temporary password
                            $temporaryPassword = bin2hex(random_bytes(4));
                            $user->setPassword(
                                $passwordHasher->hashPassword(
                                    $user,
                                    $temporaryPassword
                                )
                            );

                            $entityManager->persist($user);
                            $importedUsers[] = $user;
                            $importedCount++;
                            $seenEmails[] = $email;
                            $seenPseudos[] = $pseudo;
                        }

                        fclose($handle);
                        $entityManager->flush();
                        $entityManager->commit(); // Commit the transaction

                        // Send emails to imported users
                        foreach ($importedUsers as $importedUser) {
                            // Generate a reset token
                            $resetToken = $resetPasswordHelper->generateResetToken($importedUser);

                            // Send the email
                            $email = (new Email())
                                ->from('sortirv2@campus-eni.fr')
                                ->to($importedUser->getEmail())
                                ->subject('[Sortir V2] Bienvenue !')
                                ->html(sprintf(
                                    '<p>Kakou kakou %s,</p>
                                            <p>Un compte a été créé pour vous. Veuillez cliquer sur le lien ci-dessous pour définir votre mot de passe :</p>
                                            <p><a href="%s">Définir mon mot de passe</a></p>',
                                    $importedUser->getFirstName(),
                                    $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL)
                                ));

                            $mailer->send($email);
                        }

                        $this->addFlash('success', "$importedCount utilisateurs importés avec succès.");
                    } catch (\Exception $e) {
                        $entityManager->rollback(); // Rollback the transaction
                        $this->addFlash('danger', 'Erreur lors de l\'importation : ' . $e->getMessage());
                    }
                } else {
                    $this->addFlash('danger', 'Impossible de lire le fichier CSV.');
                }

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->addFlash('warning', $error);
                    }
                }

                return $this->redirectToRoute('app_user_index');
            }
        }

        return $this->render('user/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/export', name: 'app_user_export', methods: ['GET'])]
    public function exportCsv(
        UserRepository $userRepository
    ): Response
    {
        // Get all users
        $users = $userRepository->findAll();

        // Create a CSV file in memory
        $csvContent = fopen('php://memory', 'r+');

        // Set the CSV header to UTF-8
        fprintf($csvContent, "\xEF\xBB\xBF");

        // Add the header to the CSV
        fputcsv($csvContent, ['Pseudo', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Site', 'Actif'], ';');

        // Add each user to the CSV
        foreach ($users as $user) {
            fputcsv($csvContent, [
                $user->getPseudo(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
                $user->getPhoneNumber(),
                $user->getSite() ? $user->getSite()->getName() : '',
                $user->isActive() ? 'Oui' : 'Non',
            ], ';');
        }

        // Rewind the file pointer to the beginning
        rewind($csvContent);

        // Get the contents of the file
        $csvOutput = stream_get_contents($csvContent);

        // Close the file
        fclose($csvContent);

        // Set the filename for the download
        $filename = sprintf('utilisateurs_%s.csv', (new \DateTime())->format('Y-m-d_H-i-s'));

        // Create a response with the CSV content
        return new Response($csvOutput, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/ajax/users-by-site/{id}', name: 'ajax_users_by_site')]
    public function getUsersBySite(
        Site           $site,
        UserRepository $userRepository
    ): JsonResponse
    {
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.site = :site')
            ->setParameter('site', $site)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'name' => $user->getFirstname() . ' ' . $user->getLastname(),
            ];
        }

        return new JsonResponse($data);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/stats', name: 'app_user_stats', methods: ['GET'])]
    public function stats(
        UserRepository $userRepository
    ): JsonResponse
    {
        $activeUsers = $userRepository->count(['isActive' => true]);
        $inactiveUsers = $userRepository->count(['isActive' => false]);

        return new JsonResponse([
            'labels' => ['Actifs', 'Inactifs'],
            'data' => [$activeUsers, $inactiveUsers],
        ]);
    }
}
