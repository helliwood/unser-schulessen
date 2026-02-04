<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-04-01
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    /**
     * QuestionRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Category $category
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(Category $category, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "order"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('q')
            ->groupBy('q')
            ->where('q.category = :category')
            ->setParameter('category', $category)
            ->orderBy("q." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param Questionnaire $questionnaire
     * @return array|string[]
     */
    public function find4MiniCheckByQuestionnaire(Questionnaire $questionnaire): array
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.category', 'category')
            ->where('category.questionnaire = :questionnaire')
            ->orderBy('category.order')
            ->addOrderBy('q.order')
            ->andWhere('q.miniCheck = TRUE')
            ->setParameter('questionnaire', $questionnaire)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $id
     * @throws \Throwable
     */
    public function up(int $id): void
    {
        $question = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->where('c.category = :category')
                ->andWhere('c.order = :lower_order')
                ->setParameter('category', $question->getCategory())
                ->setParameter('lower_order', $question->getOrder() - 1)
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->where('c.category = :category')
                ->andWhere('c.id = :id')
                ->setParameter('category', $question->getCategory())
                ->setParameter('id', $question->getId())
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
        $question = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->Where('c.id = :id')
                ->setParameter('id', $question->getId())
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->where('c.category = :category')
                ->andWhere('c.order = :greater_order')
                ->andWhere('c.id != :id')
                ->setParameter('category', $question->getCategory())
                ->setParameter('greater_order', $question->getOrder() + 1)
                ->setParameter('id', $question->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }
}
