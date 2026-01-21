<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Actor;
use App\Entity\Movie;
use App\Entity\Director;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Xylis\FakerCinema\Provider\Person;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class AppFixtures extends Fixture
{
    private Generator $faker;
    private array $actors = [];
    private array $directors = [];
    private array $categories = [];

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->faker->addProvider(new Person($this->faker));
        $this->faker->addProvider(new MovieProvider($this->faker));
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadActors($manager);
        $this->loadDirectors($manager);
        $this->loadMovies($manager);

        $manager->flush();
    }

    private function loadActors(ObjectManager $manager): void
    {
        /** @phpstan-ignore-next-line */
        $actorNames = $this->faker->actors($gender = null, $count = 190, $duplicates = false);

        foreach ($actorNames as $item) {
            $actor = new Actor();
            $names = explode(" ", $item);

            $actor->setFirstName($names[0]);
            $actor->setLastName($names[1] ?? $names[0]);
            $actor->setBio($this->faker->paragraph(6, true));
            $actor->setDob($this->faker->dateTimeThisCentury());

            if ($this->faker->boolean(10)) {
                $actor->setDod($this->faker->dateTimeBetween($actor->getDob(), 'now'));
            }

            $this->actors[] = $actor; // On stocke dans la propriété de classe
            $manager->persist($actor);
        }
    }

    private function loadDirectors(ObjectManager $manager): void
    {
        /** @phpstan-ignore-next-line */
        $directorNames = $this->faker->actors($gender = null, $count = 40, $duplicates = false);

        foreach ($directorNames as $item) {
            $director = new Director();
            $names = explode(" ", $item);

            $director->setFirstname($names[0]);
            $director->setLastname($names[1] ?? $names[0]);
            $director->setDob($this->faker->dateTimeBetween('-90 years', '-30 years'));

            if ($this->faker->boolean(15)) {
                $director->setDod($this->faker->dateTimeBetween($director->getDob(), 'now'));
            }

            $this->directors[] = $director;
            $manager->persist($director);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function loadMovies(ObjectManager $manager): void
    {
        // Cette méthode reste un peu longue, mais c'est acceptable
        /** @phpstan-ignore-next-line */
        $movieTitles = $this->faker->movies(199);

        foreach ($movieTitles as $item) {
            $movie = new Movie();
            $movie->setName($item);

            // @phpstan-ignore-next-line
            $movie->setDescription($this->faker->overview);
            $movie->setDuration($this->faker->numberBetween(3600, 16200));
            $movie->setReleaseData($this->faker->dateTimeBetween('-70 years', 'now'));
            $movie->setImage($this->faker->imageUrl(400, 600, 'movie', true, $movie->getName()));
            $movie->setUrl($this->faker->url());
            $movie->setBudget($this->faker->randomFloat(2, 1_000_000, 300_000_000));
            $movie->setNbEntries($this->faker->numberBetween(100_000, 20_000_000));

            $this->handleCategory($movie, $manager);

            // Acteurs
            shuffle($this->actors);
            foreach (array_slice($this->actors, 0, rand(2, 6)) as $actor) {
                $movie->addActor($actor);
            }

            // Directeur
            $movie->setDirector($this->faker->randomElement($this->directors));

            $manager->persist($movie);
        }
    }

    private function handleCategory(Movie $movie, ObjectManager $manager): void
    {
        // @phpstan-ignore-next-line
        $categoryName = $this->faker->movieGenre;

        if (!array_key_exists($categoryName, $this->categories)) {
            $category = new Category();
            $category->setName($categoryName);
            $manager->persist($category);
            $this->categories[$categoryName] = $category;
        }

        $movie->addCategory($this->categories[$categoryName]);
    }
}
