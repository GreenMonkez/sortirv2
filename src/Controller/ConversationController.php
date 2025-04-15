<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Form\ConversationType;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/conversation')]
final class ConversationController extends AbstractController
{

    // Show a specific conversation and its messages with the possibility to send a new message
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'app_conversation_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        Conversation $conversation,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if the user is a participant in the conversation
        if (!$conversation->getParticipants()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette conversation.');
        }

        // Create a new message
        $message = new Message();

        // Create the form for sending a message
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setConversation($conversation);
            $message->setSender($this->getUser());

            $entityManager->persist($message);
            $entityManager->flush();

            // Redirect to the same conversation page after sending the message
            return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
        }


        // Regrouper les réactions par emoji
        $messagesWithReactions = [];
        foreach ($conversation->getMessages() as $message) {
            $reactionsGrouped = [];
            foreach ($message->getReactions() as $reaction) {
                $emoji = $reaction['emoji'];
                if (!isset($reactionsGrouped[$emoji])) {
                    $reactionsGrouped[$emoji] = 0;
                }
                $reactionsGrouped[$emoji]++;
            }
            $messagesWithReactions[] = [
                'message' => $message,
                'reactions' => $reactionsGrouped,
            ];
        }

        return $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
            'form' => $form,
            'messagesWithReactions' => $messagesWithReactions,
        ]);
    }

    // Create a new conversation
    #[IsGranted('ROLE_USER')]
    #[Route('/new', name: 'app_conversation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        // Create a new conversation
        $conversation = new Conversation();

        // Create the form for creating a new conversation
        $form = $this->createForm(ConversationType::class, $conversation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conversation->addParticipant($this->getUser());
            $entityManager->persist($conversation);
            $entityManager->flush();

            // Redirect to the newly created conversation
            return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
        }

        return $this->render('conversation/new.html.twig', [
            'conversation' => $conversation,
            'form' => $form,
        ]);
    }

   #[Route('/message/{id}/react', name: 'message_react', methods: ['POST'])]
    public function react(Message $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        $emoji = $request->request->get('emoji');
        $user = $this->getUser();

        // Vérifie si l'emoji est valide
        $validEmojis = ['👍', '❤️', '😂'];
        if ($emoji && in_array($emoji, $validEmojis, true)) {
            // Vérifie si l'utilisateur a déjà réagi avec cet emoji
            $existingReaction = array_filter($message->getReactions(), function ($reaction) use ($emoji, $user) {
                return $reaction['emoji'] === $emoji && $reaction['user'] === $user->getId();
            });

            if ($existingReaction) {
                // Si une réaction existe, on l'annule
                $message->removeReaction($emoji, $user);
            } else {
                // Sinon, on ajoute la réaction
                $message->addReaction($emoji, $user);
            }

            $entityManager->persist($message);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_conversation_show', ['id' => $message->getConversation()->getId()]);
    }
}
