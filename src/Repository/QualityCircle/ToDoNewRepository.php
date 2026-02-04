<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2021-06-07
 * Time: 10:16
 */

namespace App\Repository\QualityCircle;

use App\Entity\QualityCircle\ToDoNew;
use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ToDoNew|null find($id, $lockMode = null, $lockVersion = null)
 * @method ToDoNew|null findOneBy(array $criteria, array $orderBy = null)
 * @method ToDoNew[]    findAll()
 * @method ToDoNew[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ToDoNewRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDoNew::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School $school
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @param bool   $onlyClosed
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(School $school, string $sort, bool $sortDesc, int $page, int $limit, bool $onlyClosed = true): array
    {
        $sortValues = ["createdAt", "createdBy", "closedAt", "closedBy", "action_plans", "category", "question"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $totalRows = $this->createQueryBuilder('td')
            ->select('COUNT(td.id)')
            ->where('td.school = :school')
            ->andWhere('td.closed = :closed')
            ->setParameter('school', $school)
            ->setParameter('closed', $onlyClosed)
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('td')
            ->leftJoin('td.answer', 'a')
            ->leftJoin('a.question', 'q')
            ->where('td.school = :school')
            ->andWhere('td.closed = :closed')
            ->setParameter('school', $school)
            ->setParameter('closed', $onlyClosed);
        if ($sort === "category") {
            $qb->leftJoin('q.category', 'c')
                ->leftJoin('c.parent', 'p')
                ->addSelect("(CASE WHEN p.order IS NULL THEN CONCAT_WS('-', c.order, 0) ELSE CONCAT_WS('-', p.order, c.order) END) AS HIDDEN ORD")
                ->orderBy("ORD", $sortDesc ? 'DESC' : 'ASC')
                ->addOrderBy("q.order", $sortDesc ? 'DESC' : 'ASC');
        } elseif ($sort === "action_plans") {
            $qb->leftJoin('td.actionPlans', 'ap')
                ->groupBy('td')
                ->addSelect("COUNT(ap.id) AS HIDDEN ORD")
                ->orderBy("ORD", $sortDesc ? 'DESC' : 'ASC');
        } elseif ($sort === "question") {
            $qb->addSelect("(CASE WHEN q.question IS NULL THEN td.name ELSE q.question END) AS HIDDEN ORD")
                ->orderBy("ORD", $sortDesc ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy("td." . $sort, $sortDesc ? 'DESC' : 'ASC');
        }

        $items = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
