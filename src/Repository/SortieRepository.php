<?php

namespace App\Repository;

use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

// src/Repository/SortieRepository.php
    public function findByUserParticipation($user, bool $isInscrit): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.members', 'm')
            ->where($isInscrit ? 'm = :user' : 'm != :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.status', 'st')
            ->where('st.name = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    public function findByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.nom LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();
    }

    public function findBetweenDates(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.startAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('end', $endDate->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getResult();
    }

    public function findByUserNotParticipation(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.members', 'm')
            ->where('m.id != :userId OR m.id IS NULL')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function findByStartDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.startAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
