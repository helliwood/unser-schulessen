<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-01
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Questionnaire;
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
     * @param Category $parent
     * @param string   $sort
     * @param bool     $sortDesc
     * @param int      $page
     * @param int      $limit
     * @return array<any>
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByParent4Ajax(Category $parent, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "order", "questions"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('c')
            ->select('c, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('c.questions', 'q')
            ->groupBy('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy($sort === "questions" ? $sort : "c." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Questionnaire $questionnaire
     * @param string        $sort
     * @param bool          $sortDesc
     * @param int           $page
     * @param int           $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function find4Ajax(Questionnaire $questionnaire, string $sort, bool $sortDesc, int $page, int $limit)
    {
        $sortValues = ["name", "order", "questions"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->andWhere('c.parent IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('c')
            ->select('c, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('c.questions', 'q')
            ->groupBy('c')
            ->where('c.questionnaire = :questionnaire')
            ->setParameter('questionnaire', $questionnaire)
            ->andWhere('c.parent IS NULL')
            ->orderBy($sort === "questions" ? $sort : "c." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param int      $id
     * @param int|null $parentId
     * @throws \Throwable
     */
    public function up(int $id, ?int $parentId = null): void
    {
        $category = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->where('c.questionnaire = :questionnaire')
                ->andWhere('c.order = :lower_order')
                ->andWhere(\is_null($parentId) ? 'c.parent IS NULL' : 'c.parent = ' . (int)$parentId)
                ->setParameter('questionnaire', $category->getQuestionnaire())
                ->setParameter('lower_order', $category->getOrder() - 1)
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->where('c.questionnaire = :questionnaire')
                ->andWhere('c.id = :id')
                ->setParameter('questionnaire', $category->getQuestionnaire())
                ->setParameter('id', $category->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param int      $id
     * @param int|null $parentId
     * @throws \Throwable
     */
    public function down(int $id, ?int $parentId = null): void
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
                ->where('c.questionnaire = :questionnaire')
                ->andWhere('c.order = :greater_order')
                ->andWhere('c.id != :id')
                ->andWhere(\is_null($parentId) ? 'c.parent IS NULL' : 'c.parent = ' . (int)$parentId)
                ->setParameter('questionnaire', $category->getQuestionnaire())
                ->setParameter('greater_order', $category->getOrder() + 1)
                ->setParameter('id', $category->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }
}
