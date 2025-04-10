<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CsvUploadType;
use App\Form\UserType;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository
    ): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upload the profile picture
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Generate a unique filename for the new photo
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

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

            // Hash the password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    'password'
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

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
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // Check if the user is the same as the logged-in user or if the user has ROLE_ADMIN
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have permission to edit this user.');
        }

        $form = $this->createForm(UserType::class, $user);
        $oldPassword = $user->getPassword();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upload the profile picture
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Get the old photo filename
                $oldPhoto = $user->getPhoto();

                // Generate a unique filename for the new photo
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    // Move the file to the directory where profile pictures are stored
                    $photoFile->move(
                        $this->getParameter('photo_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if something happens during file upload
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                    return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                }

                // Delete the old photo if it exists
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('photo_directory').'/'.$oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Update the 'photo' property to store the file name
                $user->setPhoto($newFilename);
            }

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

            $entityManager->flush();

            // If the user is not an admin, redirect to sortie index, else, redirect to user index
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('success', 'Votre compte a bien été mis à jour !');
                return $this->redirectToRoute('app_sortie_index', [], Response::HTTP_SEE_OTHER);
            }else{
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
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
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
                $oldPhotoPath = $this->getParameter('photo_directory').'/'.$oldPhoto;
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
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SiteRepository $siteRepository
    ): Response {
        $form = $this->createForm(CsvUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csv_file')->getData();

            if ($csvFile) {
                $filePath = $csvFile->getPathname();
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
                            if (count($data) !== 7) {
                                $errors[] = 'Ligne invalide : ' . implode(', ', $data);
                                continue;
                            }

                            // Map the CSV fields to variables
                            [$email, $pseudo, $password, $lastName, $firstName, $phoneNumber, $siteName] = $data;

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
                            $user->setPassword($passwordHasher->hashPassword($user, $password));
                            $user->setLastName($lastName);
                            $user->setFirstName($firstName);
                            $user->setPhoneNumber($phoneNumber);
                            $user->setPhoto(null);
                            $user->setSite($site);
                            $user->setRoles(['ROLE_USER']);
                            $user->setIsActive(true);

                            $entityManager->persist($user);
                            $importedCount++;
                            $seenEmails[] = $email;
                            $seenPseudos[] = $pseudo;
                        }

                        fclose($handle);
                        $entityManager->flush();
                        $entityManager->commit(); // Commit the transaction

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
}
