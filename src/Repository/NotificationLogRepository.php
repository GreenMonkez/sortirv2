<?php

    namespace App\Repository;

    use App\Entity\NotificationLog;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;

    class NotificationLogRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, NotificationLog::class);
        }
    }