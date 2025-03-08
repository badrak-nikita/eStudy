<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Grade;
use App\Entity\Submission;
use App\Repository\CourseRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    #[Route('/course/{id}/task/create', name: 'task_create')]
    public function createTask(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        CategoryRepository $categoryRepository
    ) {
        $course = $courseRepository->find($id);
        $categories = $categoryRepository->findAll();

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $categoryId = $request->request->get('category_id');
            $dueDateString = $request->request->get('due_date');

            if (!$course) {
                throw $this->createNotFoundException('Курс не знайдено');
            }

            $dueDate = new \DateTime($dueDateString);

            $task = new Task();
            $task->setCourse($course);
            $task->setTitle($title);
            $task->setDescription($description);
            $task->setCategory($categoryRepository->find($categoryId));
            $task->setDueDate($dueDate);

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('course_show', ['id' => $id]);
        }

        return $this->render('task/create.html.twig', [
            'course' => $course,
            'categories' => $categories
        ]);
    }

    #[Route('/task/{id}', name: 'task_show')]
    public function showTask(int $id, EntityManagerInterface $entityManager, Security $security): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            throw $this->createNotFoundException('Завдання не знайдено');
        }

        $user = $security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Ви не авторизовані');
        }

        $submission = $entityManager->getRepository(Submission::class)->findOneBy(['task' => $task, 'student' => $user]);
        $grade = $submission ? $entityManager->getRepository(Grade::class)->findOneBy(['submission' => $submission]) : null;

        $submissionsToCheck = $entityManager->getRepository(Submission::class)->findBy(['task' => $task, 'status' => 1]);

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'submission' => $submission,
            'grade' => $grade,
            'course' => $task->getCourse(),
            'submissionsToCheck' => $submissionsToCheck,
        ]);
    }

    #[Route('/task/{id}/upload', name: 'submission_upload', methods: ['POST'])]
    public function uploadSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json(['success' => false, 'error' => 'Завдання не знайдено'], 404);
        }

        $user = $security->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'Ви не авторизовані'], 403);
        }

        $existingSubmission = $entityManager->getRepository(Submission::class)
            ->findOneBy(['task' => $task, 'student' => $user]);

        if ($existingSubmission) {
            $submission = $existingSubmission;
        } else {
            $submission = new Submission();
            $submission->setTask($task);
            $submission->setStudent($user);
            $submission->setCreatedDate(new \DateTime());
            $submission->setStatus(1);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['success' => false, 'error' => 'Файл не було завантажено.'], 400);
        }

        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/png',
            'image/jpeg',
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['success' => false, 'error' => 'Неправильний формат файлу. Дозволені: PDF, DOC, DOCX, PNG, JPG.'], 400);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['success' => false, 'error' => 'Файл занадто великий (максимум 10MB).'], 400);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        $newFilename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadsDir, $newFilename);

        $submission->setFilePath('/uploads/' . $newFilename);
        $entityManager->persist($submission);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/submission/{id}/grade', name: 'submission_grade', methods: ['POST'])]
    public function gradeSubmission(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $submission = $entityManager->getRepository(Submission::class)->find($id);

        if (!$submission) {
            return new JsonResponse(['success' => false, 'message' => 'Робота не знайдена'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $score = $data['score'] ?? null;
        $comment = $data['comment'] ?? null;

        if ($score === null || $score < 0 || $score > 100) {
            return new JsonResponse(['success' => false, 'message' => 'Некоректна оцінка'], 400);
        }

        $grade = new Grade();
        $grade->setSubmission($submission);
        $grade->setComment($comment);
        $grade->setScore($score);
        $grade->setRatedDate(new \DateTime());

        $submission->setStatus(2);

        $entityManager->persist($grade);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
