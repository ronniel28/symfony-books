<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\ImportBooksType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BookController extends AbstractController
{

    #[Route('/', name: 'app_book')]
    public function index(Request $request, BookRepository $books, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        $input = '7:05:45AM';
        $timeArray = explode(':', $input);

        if(str_contains($timeArray[2], 'AM')){
            dd (date("H:i:s", strtotime($input)));

        }else{
            dd (date("H:i:s", strtotime($input)));
        }
        
        // dd($this->getUser());
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sortBy');
        $direction = $request->query->get('direction');
        $page = $request->query->get('page');
   
        $queryBuilder = $books->searchBooks($searchTerm, $sortBy, $direction);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        // importing
        $form = $this->createForm(ImportBooksType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the uploaded file
            $csvFile = $form->get('csvFile')->getData();
            if ($csvFile) {
                try {
                     // Open the CSV file and process each row
                     $fileHandle = fopen($csvFile->getPathname(), 'r');
                    
                     $header = fgetcsv($fileHandle); // Assumes the first row contains the headers
                   
                     while (($row = fgetcsv($fileHandle)) !== false) {
                         $this->processCsvRow($row, $header, $entityManager);
                     }
 
                     fclose($fileHandle);
 
                     // Persist all the data to the database
                     $entityManager->flush();
 
                     $this->addFlash('success', 'Books imported successfully!');
                     return $this->redirectToRoute('app_book');
                } catch (FileException $e) {
                    $this->addFlash('error', 'An error occurred while processing the CSV file.');
                }
            }
        }
   
        $totalPage = ceil($pagination->getTotalItemCount()/$pagination->getItemNumberPerPage());
        // dd($pagination);
        return $this->render('book/index.html.twig', [
            'pagination' => $pagination,
            'totalPage' =>($totalPage),
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'direction' => $direction,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/book/{id}', name: 'app_book_show')]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/book/add', name: 'app_book_add', priority: 2)]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addBook(Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = new Book();
        
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $book = $form->getData();
            $entityManager->persist($book);
            $entityManager->flush();
        
            $this->addFlash('success', "Book have been successfully added!");

            return $this->redirectToRoute('app_book');
        }

        return $this->render('book/add.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/book/{id}/edit', name: 'app_book_edit')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function editBook(Request $request, EntityManagerInterface $entityManager, Book $book): Response
    {
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $book = $form->getData();
            $entityManager->persist($book);
            $entityManager->flush();
        
            $this->addFlash('success', "Book have been successfully updated!");

            return $this->redirectToRoute('app_book');
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form
        ]);
    }

    #[Route('/book/{id}/delete', name: 'app_book_delete')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteBook(Request $request, EntityManagerInterface $entityManager, Book $book): Response
    {
        $entityManager->remove($book);
        $entityManager->flush();

        $this->addFlash('success', 'Book deleted successfully');
        return $this->redirectToRoute('app_book');

    }

    #[Route('/books/export', name: 'app_book_export')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function exportBooksToCsv(Request $request, BookRepository $books, PaginatorInterface $paginator)
    {
    
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sortBy', 'title');
        $direction = $request->query->get('direction', 'asc');

        // Check if the user wants to export filtered results or all books
        $exportAll = $request->query->get('export_all', false);

        // Set a limit of 10 records per CSV file
        $limit = 10;

        // Create a temporary directory to store CSV files
        $tempDir = sys_get_temp_dir() . '/books_csv_' . uniqid();
        mkdir($tempDir);

        // Total number of books (for looping)
        $totalBooks = $exportAll ? count($books->findAll()) : count($books->searchBooks($searchTerm, $sortBy, $direction)->getQuery()->getResult());
        $totalPages = ceil($totalBooks / $limit);

        // Loop through each page and create individual CSV files

        for ($page = 1; $page <= $totalPages; $page++) {
            if ($exportAll) {
                $queryBuilder = $books->createQueryBuilder('b')->orderBy("b.$sortBy", $direction);
            } else {
                $queryBuilder = $books->searchBooks($searchTerm, $sortBy, $direction);
            }

            $pagination = $paginator->paginate(
                $queryBuilder,
                $page, // Current page
                $limit // Fetch 10 items per page
            );
            
            $paginatedBooks = $pagination->getItems();
            
            // Generate CSV file for the current page
            $csvFile = $tempDir . '/books_page_' . $page . '.csv';
            $handle = fopen($csvFile, 'w+');
            
            // Write the CSV column headers
            fputcsv($handle, ['ID', 'Title', 'Author', 'Published Date', 'ISBN', 'Description']);
            
            // Write each book's data to the CSV file
            foreach ($paginatedBooks as $book) {
                fputcsv($handle, [
                    $book->getId(),
                    $book->getTitle(),
                    $book->getAuthor(),
                    $book->getPublishedDate()->format('Y-m-d'),
                    $book->getIsbn(),
                    $book->getDescription()
                ]);
            }

            fclose($handle);
        }

        // Create a ZIP file and add all CSVs to it
        $zipFile = $tempDir . '/books_export.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            foreach (glob($tempDir . '/*.csv') as $csvFile) {
                $zip->addFile($csvFile, basename($csvFile));
            }
            $zip->close();
        }

        // Create a response to download the ZIP file
        $response = new BinaryFileResponse($zipFile);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="books_export.zip"');

        // Cleanup: delete temporary files after sending the response
        $response->deleteFileAfterSend(true);

        return $response;

    }

    private function processCsvRow(array $row, array $header, EntityManagerInterface $entityManager)
    {
        // Map CSV columns to the Book fields
        $bookData = array_combine($header, $row);

        // Validate data format (e.g., title, author, ISBN, etc.)
        if (!$this->validateCsvData($bookData)) {
         
            return;  // Skip invalid data
        }

        // Check if the book already exists based on the ISBN
        $existingBook = $entityManager->getRepository(Book::class)->findOneBy(['isbn' => $bookData['ISBN']]);
    
        if ($existingBook) {
            // Update the existing book with the new data
            $existingBook->setTitle($bookData['Title']);
            $existingBook->setAuthor($bookData['Author']);
            $existingBook->setPublishedDate(new \DateTime($bookData['Published Date']));
            $existingBook->setDescription($bookData['Description']);
        } else {
 
            // Create a new book
            $newBook = new Book();
            $newBook->setTitle($bookData['Title']);
            $newBook->setAuthor($bookData['Author']);
            $newBook->setIsbn($bookData['ISBN']);
            $newBook->setPublishedDate(new \DateTime($bookData['Published Date']));
            $newBook->setDescription($bookData['Description']);

            $entityManager->persist($newBook);
        }
    }

    private function validateCsvData(array $data): bool
    {
      
        // Ensure all required fields are present and valid
        if (empty($data['Title']) || empty($data['Author']) || empty($data['ISBN']) || empty($data['Published Date'])) {
           
            return false;
        }

        // You can add more validations (e.g., ISBN format, date format, etc.)
        if (!preg_match('/^\d{13}$/', $data['ISBN'])) {

            return false;  // Invalid ISBN
        }

        return true;
    }
}
