<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function searchBooks(?string $searchTerm, ?string $sortBy = 'title', ?string $direction = 'asc')
    {
        $qb = $this->createQueryBuilder('b');
  
        if($searchTerm)
        {
            $qb->where('b.title LIKE :searchTerm')
                ->orWhere('b.author LIKE :searchTerm')
                ->orWhere('b.isbn LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }


        //handle sorting
        if (in_array($sortBy, ['title', 'author', 'publishedDate']) && in_array($direction, ['asc', 'desc']))
        {
            $qb->orderBy('b.'.$sortBy, $direction);
        } else {
            $qb->orderBy('b.title', 'asc');
        }

        return $qb;
    }

    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
