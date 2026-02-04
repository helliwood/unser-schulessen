<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-01-13
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\Media;
use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    /**
     * MediaRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School     $school
     * @param string     $sort
     * @param bool       $sortDesc
     * @param int        $page
     * @param int        $limit
     * @param Media|null $parent
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        ?Media $parent
    ): array {
        $sortValues = ["description", "fileName", "fileSize", "createdAt"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "fileName";
        }

        if (! \is_null($parent)) {
            $totalRows = $this->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.school = :school')
                ->setParameter('school', $school)
                ->andWhere('m.parent = :parent')
                ->setParameter('parent', $parent)
                ->getQuery()
                ->getSingleScalarResult();

            $items = $this->createQueryBuilder('m')
                ->groupBy('m')
                ->where('m.school = :school')
                ->setParameter('school', $school)
                ->andWhere('m.parent = :parent')
                ->setParameter('parent', $parent)
                ->orderBy('m.directory', 'DESC')
                ->addOrderBy("m." . $sort, $sortDesc ? 'DESC' : 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } else {
            $totalRows = $this->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->where('m.school = :school')
                ->setParameter('school', $school)
                ->andWhere('m.parent IS NULL')
                ->getQuery()
                ->getSingleScalarResult();

            $items = $this->createQueryBuilder('m')
                ->groupBy('m')
                ->where('m.school = :school')
                ->setParameter('school', $school)
                ->andWhere('m.parent IS NULL')
                ->orderBy('m.directory', 'DESC')
                ->addOrderBy("m." . $sort, $sortDesc ? 'DESC' : 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School     $school
     * @param string     $sort
     * @param bool       $sortDesc
     * @param int        $page
     * @param int        $limit
     * @param Media|null $parent
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findDirectoryContent4Ajax(
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        Media $parent
    ): array {
        $sortValues = ["description", "fileName", "fileSize", "createdAt"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "fileName";
        }
        $totalRows = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.school = :school')
            ->setParameter('school', $school)
            ->andWhere('m.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('m')
            ->groupBy('m')
            ->where('m.school = :school')
            ->setParameter('school', $school)
            ->andWhere('m.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy("m." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
//dd($items);
        return ["totalRows" => $totalRows, "items" => $items];
    }
}
