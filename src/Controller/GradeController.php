<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Service\GradeService;
use App\Entity\CourseStudents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GradeController extends AbstractController
{
    #[Route('/grades', name: 'grades')]
    public function index(EntityManagerInterface $entityManager, Security $security, GradeService $gradeService): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $courseStudents = $entityManager->getRepository(CourseStudents::class)->findBy(['student' => $user]);
        $submissionRepository = $entityManager->getRepository(Submission::class);

        $grades = [];

        foreach ($courseStudents as $courseStudent) {
            $course = $courseStudent->getCourse();
            $submissions = $submissionRepository->findSubmissionsByStudentAndCourse($user, $course);
            
            $finalGrade = $gradeService->calculateFinalGrade($submissions);

            $grades[] = [
                'course' => $course,
                'finalGrade' => $finalGrade,
            ];
        }

        return $this->render('grades/show.html.twig', [
            'grades' => $grades,
        ]);
    }

    #[Route('/grades/course/{courseId}', name: 'grades_course')]
    public function courseGrades(EntityManagerInterface $entityManager, Security $security, GradeService $gradeService, int $courseId): Response
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $submissionRepository = $entityManager->getRepository(Submission::class);
        $submissions = $submissionRepository->findSubmissionsByStudentAndCourse($user, $courseId);

        $grades = [];

        foreach ($submissions as $submission) {
            $grades[] = [
                'task' => $submission->getTask(),
                'grade' => $submission->getGrade(),
            ];
        }

        $finalGrade = $gradeService->calculateFinalGrade($submissions);

        return $this->render('grades/course.html.twig', [
            'grades' => $grades,
            'finalGrade' => $finalGrade,
        ]);
    }
}
