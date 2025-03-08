<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Course;
use App\Entity\CourseStudents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        $courses = [];
        $deadlines = [];

        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $courses = $entityManager->getRepository(Course::class)->findBy(['teacher' => $user]);
            } else {
                $studentCourses = $entityManager->getRepository(CourseStudents::class)->findBy(['student' => $user]);
                
                foreach ($studentCourses as $courseStudent) {
                    $courses[] = $courseStudent->getCourse();
                }

                $tasks = $entityManager->getRepository(Task::class)->findBy(['course' => $courses]);

                foreach ($tasks as $task) {
                    if ($task->getDueDate() && $task->getDueDate()->format('m') == date('m')) {
                        $deadlines[] = [
                            'title' => $task->getTitle(),
                            'date' => $task->getDueDate()->format('Y-m-d'),
                            'category' => $task->getCategory()->getCategoryName(),
                        ];
                    }
                }
            }
        }

        return $this->render('index.html.twig', [
            'courses' => $courses,
            'deadlines' => $deadlines,
        ]);
    }
}
