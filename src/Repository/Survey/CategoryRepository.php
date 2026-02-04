<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-08
 * Time: 15:16
 */

namespace App\Repository\Survey;

use App\Entity\Survey\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit)
    {
        $sortValues = ["name", "order", "questions"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('c')
            ->select('c, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('c.questions', 'q')
            ->groupBy('c')
            ->orderBy($sort === "questions" ? $sort : "c." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param int $id
     * @throws \Throwable
     */
    public function up(int $id): void
    {
        $category = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->andWhere('c.order = :lower_order')
                ->setParameter('lower_order', $category->getOrder() - 1)
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->andWhere('c.id = :id')
                ->setParameter('id', $category->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param int $id
     * @throws \Throwable
     */
    public function down(int $id): void
    {
        $category = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->Where('c.id = :id')
                ->setParameter('id', $category->getId())
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->andWhere('c.order = :greater_order')
                ->andWhere('c.id != :id')
                ->setParameter('greater_order', $category->getOrder() + 1)
                ->setParameter('id', $category->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    public function reorderAll(): void
    {
        foreach ($this->findBy([], ['order' => 'ASC']) as $i => $category) {
            $category->setOrder($i + 1);
        }
    }
}
