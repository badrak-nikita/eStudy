<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'Лабораторна робота',
            'Практична робота',
            'Модульний тест',
            'Самостiйна робота',
            'Екзамен'
        ];

        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setCategoryName($categoryName);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
