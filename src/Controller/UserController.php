<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
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
    public function index(UserRepository $userRepository): Response
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
                    $form->get('password')->getData()
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
    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
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
                    return $this->redirectToRoute('app_user_new');
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

            $this->addFlash('success', 'Votre compte a bien été mis à jour !');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Get the old photo filename
            $oldPhoto = $user->getPhoto();

            $user->setEmail('anonyme_' . uniqid() . '@example.com');
            $user->setPseudo('Utilisateur_' . uniqid());
            $user->setFirstName('Anonyme');
            $user->setLastName('Anonyme');
            $user->setPhoneNumber(null);
            $user->setIsActive(false);
            $user->setRoles(['ROLE_ANONYMOUS']);
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
}
