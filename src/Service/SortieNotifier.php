<?php

namespace App\Service;

use App\Entity\NotificationLog;
use App\Repository\NotificationLogRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SortieNotifier
{
    public function __construct(
        private SortieRepository $sortieRepository,
        private MailerInterface $mailer,
        private NotificationLogRepository $notificationLogRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function notifyLogSorties(): void
    {
        $now = new \DateTime();
        $startDate = (clone $now)->modify('+47 hours');
        $endDate = (clone $now)->modify('+48 hours');

        $sorties = $this->sortieRepository->findByStartDateRange($startDate, $endDate);

        foreach ($sorties as $sortie) {
            foreach ($sortie->getMembers() as $member) {
                // Vérifier si une notification a déjà été envoyée
                $alreadyNotified = $this->notificationLogRepository->findOneBy([
                    'user' => $member,
                    'sortie' => $sortie,
                ]);

                if ($alreadyNotified) {
                    continue; // Passer si déjà notifié
                }

                $email = (new Email())
                    ->from('noreply@votreapp.com')
                    ->to($member->getEmail())
                    ->subject('Rappel : Sortie à venir')
                    ->text(sprintf(
                        'Bonjour %s %s, la sortie "%s" commence bientôt, le %s.',
                        $member->getFirstName(),
                        $member->getLastName(),
                        $sortie->getNom(),
                        $sortie->getStartAt()->format('d/m/Y H:i')
                    ));

                $this->mailer->send($email);

                // Enregistrer la notification
                $log = new NotificationLog();
                $log->setUser($member)
                    ->setSortie($sortie)
                    ->setNotifiedAt(new \DateTimeImmutable());

                $this->entityManager->persist($log);
            }
        }

        $this->entityManager->flush();
    }
}