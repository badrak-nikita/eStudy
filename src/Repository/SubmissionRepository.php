<?php

namespace App\Repository;

use App\Entity\Submission;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function findSubmissionsByStudentAndCourse($student, $course): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, g, t, c')
            ->leftJoin('s.task', 't')
            ->leftJoin('t.category', 'c')
            ->leftJoin('s.grade', 'g')
            ->andWhere('s.student = :student')
            ->andWhere('t.course = :course')
            ->setParameter('student', $student)
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
    }
}
