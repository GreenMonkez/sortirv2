<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
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

}
