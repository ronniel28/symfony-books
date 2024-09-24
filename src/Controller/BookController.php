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

class BookController extends AbstractController
{

    #[Route('/', name: 'app_book')]
    public function index(Request $request, BookRepository $books, PaginatorInterface $paginator): Response
    {
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
   
        $totalPage = ceil($pagination->getTotalItemCount()/$pagination->getItemNumberPerPage());
        // dd($pagination);
        return $this->render('book/index.html.twig', [
            'pagination' => $pagination,
            'totalPage' =>($totalPage),
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'direction' => $direction,
            'page' => $page
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

        // Add flash message (optional)
        $this->addFlash('success', 'Book deleted successfully');
        return $this->redirectToRoute('app_book');

    }

    #[Route('/books/export', name: 'app_book_export')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function exportBooksToCsv(Request $request, BookRepository $books, PaginatorInterface $paginator): StreamedResponse
    {
    
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sortBy', 'title');
        $direction = $request->query->get('direction', 'asc');

          // Check if the user wants to export filtered results or all books
        $exportAll = $request->query->get('export_all', false);

              // If the user opts to export all, fetch all books, otherwise use the current search and sorting
            if ($exportAll) {
                $books = $books->findAll();
            } else {
                $queryBuilder = $books->searchBooks($searchTerm, $sortBy, $direction);
                $pagination = $paginator->paginate(
                    $queryBuilder, 
                    1, // Page is irrelevant for export, fetch all filtered results
                    PHP_INT_MAX // Fetch all items for export
                );
                $books = $pagination->getItems(); // Get the filtered list
            }
            // Generate the CSV response
            $response = new StreamedResponse(function () use ($books) {
                $handle = fopen('php://output', 'w+');
                
                // Write the CSV column headers
                fputcsv($handle, ['ID', 'Title', 'Author', 'Published Date', 'ISBN', 'Description']);
                
                // Write each book's data to the CSV file
                foreach ($books as $book) {
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
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="books.csv"');
    
            return $response;
    
    }
}
