<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Actor;
use App\Entity\Movie;
use App\Entity\Director;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create();
        $faker->addProvider(new \Xylis\FakerCinema\Provider\Person($faker));

        // =====================
        // 1️⃣ ACTORS
        // =====================
        $actorsArray = [];
        $actors = $faker->actors($gender = null, $count = 190, $duplicates = false);

        foreach ($actors as $item) {
            $actor = new Actor();
            $names = explode(" ", $item);

            $actor->setFirstName($names[0] ?? '');
            $actor->setLastName($names[1] ?? '');
            $actor->setBio($faker->paragraph(6, true));

            $dob = $faker->dateTimeThisCentury();
            $actor->setDob($dob);

            if ($faker->boolean(10)) {
                $actor->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $actorsArray[] = $actor;
            $manager->persist($actor);
        }

        // =====================
        // 2️⃣ DIRECTORS
        // =====================
        $directorsArray = [];
        $directors = $faker->actors($gender = null, $count = 40, $duplicates = false);

        foreach ($directors as $item) {
            $director = new Director();
            $names = explode(" ", $item);

            $director->setFirstname($names[0] ?? '');
            $director->setLastname($names[1] ?? '');

            $dob = $faker->dateTimeBetween('-90 years', '-30 years');
            $director->setDob($dob);

            // Environ 15% de réalisateurs décédés
            if ($faker->boolean(15)) {
                $director->setDod($faker->dateTimeBetween($dob, 'now'));
            }

            $directorsArray[] = $director;
            $manager->persist($director);
        }

        // =====================
        // 3️⃣ MOVIES + CATEGORIES
        // =====================
        $fakerMovie = \Faker\Factory::create();
        $fakerMovie->addProvider(new \Xylis\FakerCinema\Provider\Movie($fakerMovie));

        $categoriesArray = [];
        $movies = $fakerMovie->movies(199);

        foreach ($movies as $item) {
            $movie = new Movie();
            $movie->setName($item);
            $movie->setDescription($fakerMovie->overview);

            // Durée entre 1h et 4h30
            $movie->setDuration($faker->numberBetween(3600, 16200)); // en secondes

            // Release date : entre 1950 et aujourd'hui
            $movie->setReleaseData($faker->dateTimeBetween('-70 years', 'now'));

            // Image & URL factices
            $movie->setImage($faker->imageUrl(400, 600, 'movie', true, $movie->getName()));
            $movie->setUrl($faker->url());

            // Budget entre 1 et 300 millions
            $movie->setBudget($faker->randomFloat(2, 1_000_000, 300_000_000));

            // Nb d'entrées entre 100k et 20M
            $movie->setNbEntries($faker->numberBetween(100_000, 20_000_000));

            // Catégorie (créée si nouvelle)
            $categoryName = $fakerMovie->movieGenre;
            if (!array_key_exists($categoryName, $categoriesArray)) {
                $category = new Category();
                $category->setName($categoryName);
                $manager->persist($category);
                $categoriesArray[$categoryName] = $category;
            } else {
                $category = $categoriesArray[$categoryName];
            }
            $movie->addCategory($category);

            // Acteurs (2 à 6 par film)
            shuffle($actorsArray);
            foreach (array_slice($actorsArray, 0, rand(2, 6)) as $actorObject) {
                $movie->addActor($actorObject);
            }

            // 🎬 Attribution d’un réalisateur aléatoire
            $director = $faker->randomElement($directorsArray);
            $movie->setDirector($director);

            $manager->persist($movie);
        }

        $manager->flush();
    }
}
