<?php

namespace App\DataFixtures;

use App\Entity\Book;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        // $product = new Product();
        // $manager->persist($product);
        for ($i = 0; $i < 15; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3)); // Generates a title
            $book->setAuthor($faker->name()); // Generates an author name
            $book->setPublishedDate($faker->dateTimeBetween('-10 years', 'now')); // Random date in the last 10 years
            $book->setIsbn($faker->isbn13()); // Generates a random ISBN-13
            $book->setDescription($faker->paragraph()); // Generates a description

            $manager->persist($book);
        }


        $manager->flush();
    }


}
