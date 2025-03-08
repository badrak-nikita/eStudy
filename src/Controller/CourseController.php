<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Course;
use App\Entity\CourseStudents;
use App\Repository\UserRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CourseController extends AbstractController
{
    #[Route('/course/create', name: 'course_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $courseName = $request->request->get('course_name');
            $courseDescription = $request->request->get('course_description');

            $course = new Course();
            $course->setCourseName($courseName);
            $course->setCourseDescription($courseDescription);

            $teacher = $this->getUser();
            if ($teacher) {
                $course->setTeacher($teacher);
            }

            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('course/create.html.twig');
    }

    #[Route('/course/show/{id}', name: 'course_show')]
    public function show(Course $course, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $tasks = $entityManager->getRepository(Task::class)->findBy(['course' => $course]);

        $studentsInCourse = $entityManager->getRepository(CourseStudents::class)->findBy(['course' => $course]);
        $allStudents = $userRepository->studentsByRole();

        $addedStudentIds = [];
        foreach ($studentsInCourse as $students) {
            $addedStudentIds[] = $students->getStudent()->getId();
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'tasks' => $tasks,
            'students' => $studentsInCourse,
            'allStudents' => $allStudents,
            'addedStudentIds' => $addedStudentIds,
        ]);
    }

    #[Route('/course/{courseId}/add-student/{studentId}', name: 'add_student', methods: ['POST'])]
    public function addStudent(
        $courseId,
        $studentId,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $course = $courseRepository->find($courseId);
        $student = $userRepository->find($studentId);

        if (!$course || !$student) {
            return $this->json(['success' => false, 'error' => 'Дисциплiну або студента не знайдено'], 404);
        }

        $existingStudent = $entityManager->getRepository(CourseStudents::class)->findOneBy([
            'course' => $course,
            'student' => $student
        ]);

        if ($existingStudent) {
            return $this->json(['success' => false, 'error' => 'Студент уже доданий до дисциплiни'], 400);
        }

        $courseStudent = new CourseStudents();
        $courseStudent->setCourse($course);
        $courseStudent->setStudent($student);

        $entityManager->persist($courseStudent);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/course/{courseId}/remove-student/{studentId}', name: 'remove_student', methods: ['POST'])]
    public function removeStudent(
        int $courseId,
        int $studentId,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $course = $courseRepository->find($courseId);
        $student = $userRepository->find($studentId);

        if (!$course || !$student) {
            return $this->json(['success' => false, 'error' => 'Дисциплiну або студента не знайдено'], 404);
        }

        $courseStudent = $entityManager->getRepository(CourseStudents::class)->findOneBy([
            'course' => $course,
            'student' => $student
        ]);

        if (!$courseStudent) {
            return $this->json(['success' => false, 'error' => 'Студента не знайдено у дисциплiнi'], 404);
        }

        $entityManager->remove($courseStudent);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
