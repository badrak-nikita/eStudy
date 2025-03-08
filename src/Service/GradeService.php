<?php

namespace App\Service;

use App\Entity\Submission;

class GradeService
{
    /**
     * Розрахунок загального балу студента за дисциплiну
     *
     * @param Submission[] $submissions
     * @return float
     */
    public function calculateFinalGrade(array $submissions): float
    {
        $totalScore = 0;

        foreach ($submissions as $submission) {
            $grade = $submission->getGrade();

            if ($grade) {
                $category = $submission->getTask()->getCategory();
                $score = $grade->getScore();

                if (in_array($category->getId(), [1, 2, 3, 4])) {
                    $totalScore += $score * 0.7;
                } elseif ($category->getId() === 5) {
                    $totalScore += $score;
                }
            }
        }

        return $totalScore;
    }
}
