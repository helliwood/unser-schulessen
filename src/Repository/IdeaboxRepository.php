<?php

namespace App\Repository;

use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Throwable;

class IdeaboxRepository extends ServiceEntityRepository
{
    /**
     * IdeaboxRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ideabox::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Question $question
     * @param string   $sort
     * @param bool     $sortDesc
     * @param int      $page
     * @param int      $limit
     * @return array
     * @throws NonUniqueResultException
     */
    public function find4Ajax(Question $question, string $sort, bool $sortDesc, int $page, int $limit): array
    {

        $sortValues = ["order"];
        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.question = :question')
            ->setParameter('question', $question)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('i')
            ->groupBy('i')
            ->where('i.question = :question')
            ->setParameter('question', $question)
            ->orderBy("i." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param int $id
     * @throws Throwable
     */
    public function up(int $id): void
    {
        $ideabox = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->where('c.question = :question')
                ->andWhere('c.order = :lower_order')
                ->setParameter('question', $ideabox->getQuestion())
                ->setParameter('lower_order', $ideabox->getOrder() - 1)
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->where('c.question = :question')
                ->andWhere('c.id = :id')
                ->setParameter('question', $ideabox->getQuestion())
                ->setParameter('id', $ideabox->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param int $id
     * @throws Throwable
     */
    public function down(int $id): void
    {
        $ideabox = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order+1')
                ->Where('c.id = :id')
                ->setParameter('id', $ideabox->getId())
                ->getQuery()->execute();

            $this->createQueryBuilder('c')
                ->update()
                ->set('c.order', 'c.order-1')
                ->where('c.question = :question')
                ->andWhere('c.order = :greater_order')
                ->andWhere('c.id != :id')
                ->setParameter('question', $ideabox->getQuestion())
                ->setParameter('greater_order', $ideabox->getOrder() + 1)
                ->setParameter('id', $ideabox->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }
}
