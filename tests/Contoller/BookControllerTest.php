<?php

namespace App\Tests\Contoller;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{

    public function loginUser($client)
    {
        // Load the user from the test database
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $testUser = $userRepository->findOneByEmail('testuser@example.com');

        // Simulate the login with the test user
        $client->loginUser($testUser);
    }
    public function testVisitingWhileLoggedIn(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByEmail('testuser@example.com');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        // test e.g. the profile page
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(
            0,
            $crawler->filter('a:contains("testuser@example.com")')->count()
        );
    }

    public function testCreateNewBook()
    {
        $client = static::createClient();
        $this->loginUser($client); // Simulate user login

        $crawler = $client->request('GET', '/book/add');

        // Check if the form loads successfully
        $this->assertResponseIsSuccessful();

        // Submit the form to create a new book
        $client->submitForm('SAVE', [
            'book[title]' => 'Test Book',
            'book[author]' => 'Test Author',
            'book[isbn]' => '1234567890123',
            'book[publishedDate]' => '2024-01-01',
            'book[description]' => 'Test book description'
        ]);

        // Assert the response redirects to the book list
        $this->assertResponseRedirects('/');

        // Follow the redirect
        $client->followRedirect();

        // Assert the book was saved in the database
        $bookRepository = static::getContainer()->get('doctrine')->getRepository(Book::class);
        $savedBook = $bookRepository->findOneBy(['title' => 'Test Book']);

        $this->assertNotNull($savedBook);  // Ensure the book exists in the database
        $this->assertEquals('Test Author', $savedBook->getAuthor()); // Ensure the correct data was saved
        $this->assertEquals('1234567890123', $savedBook->getIsbn());
    }

    public function testEditBook()
    {
        $client = static::createClient();
        $this->loginUser($client); // Simulate user login

        $bookRepository = static::getContainer()->get('doctrine')->getRepository(Book::class);
        $book = $bookRepository->findOneBy(['title' => 'Test Book']);

        $crawler = $client->request('GET', '/book/' . $book->getId() . '/edit');

        // Check if the edit form loads successfully
        $this->assertResponseIsSuccessful();

        // Submit the form with updated data
        $client->submitForm('SAVE', [
            'book[title]' => 'Updated Test Book',
            'book[author]' => 'Updated Author'
        ]);

        // Assert the response redirects to the book list
        $this->assertResponseRedirects('/');

        // Follow the redirect
        $client->followRedirect();

        // Assert the book was updated in the database
        $updatedBook = $bookRepository->find($book->getId());

        $this->assertEquals('Updated Test Book', $updatedBook->getTitle());  // Check if the title was updated
        $this->assertEquals('Updated Author', $updatedBook->getAuthor());  // Check if the author was updated
    }

    public function testDeleteBook()
    {
        $client = static::createClient();
        $this->loginUser($client); // Simulate user login

        $bookRepository = static::getContainer()->get('doctrine')->getRepository(Book::class);
        $book = $bookRepository->findOneBy(['title' => 'Updated Test Book']);

        // Ensure that the book has a valid ID before attempting to delete
        $this->assertNotNull($book);
        $this->assertNotNull($book->getId());

        $crawler = $client->request('GET', '/book/' . $book->getId() . '/delete');

 
        // Assert the response redirects to the book list
        $this->assertResponseRedirects('/');

        // Follow the redirect
        $client->followRedirect();

        // Assert the book was deleted from the database
        $this->assertNull($book->getId());  // Ensure the book no longer exists
    }

}
