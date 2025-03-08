<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        return $this->findOneBy(['name' => $identifier]);
    }

    public function studentsByRole()
    {
        return $this->createQueryBuilder('users')
            ->andWhere('users.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getResult();
    }
}
