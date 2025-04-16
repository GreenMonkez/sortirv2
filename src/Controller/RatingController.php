<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Sortie;
use App\Form\RatingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RatingController extends AbstractController
{
    #[Route('/sortie/{id}/rate', name: 'sortie_rate', methods: ['POST', 'GET'])]
    public function rateSortie(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();

        $existingRating = $em->getRepository(Rating::class)->findOneBy(['user' => $user, 'sortie' => $sortie]);

        if ($existingRating) {
            $this->addFlash('danger', 'Vous avez déjà noté cette sortie.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        $rating = new Rating();
        $rating->setUser($user);
        $rating->setSortie($sortie);

        $form = $this->createForm(RatingType::class, $rating);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($rating);
            $em->flush();

            $this->addFlash('success', 'Votre note a été enregistrée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/rate.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }
}
