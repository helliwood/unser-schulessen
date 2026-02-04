<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\QualityCheck\Questionnaire;
use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Questionnaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Questionnaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Questionnaire[]    findAll()
 * @method Questionnaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionnaireRepository extends ServiceEntityRepository
{
    /**
     * QuestionnaireRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questionnaire::class);
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
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "date", "categories"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "date";
        }

        $totalRows = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('q')
            ->select('q, COUNT(c.id) as HIDDEN categories')
            ->leftJoin('q.categories', 'c')
            ->groupBy('q')
            ->orderBy($sort === "categories" ? $sort : "q." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }


    /**
     * @return array|string[]
     */
    public function getQuestionnaireStats(): array
    {
        /** @var SchoolYear $schoolYear */
        $schoolYear = $this->getEntityManager()
            ->getRepository(SchoolYear::class)
            ->findOneBy(['year' => \date('Y')]);

        $result = $this->getEntityManager()
            ->getRepository(Questionnaire::class)
            ->createQueryBuilder('q')
            ->select('COUNT(q.id) total, SUBSTRING(q.date, 1, 7) month')
            ->where('q.state = ' . Questionnaire::STATE_ARCHIVED)
            ->andWhere('SUBSTRING(q.date, 1, 4) = ' . $schoolYear->getYear())
            ->orderBy('month', 'ASC')
            ->groupBy('month')
            ->getQuery()
            ->getArrayResult();

        $period = new \DatePeriod(
            $schoolYear->getPeriodBegin(),
            new \DateInterval('P1M'),
            $schoolYear->getPeriodEnd()
        );

        $total = [];
        foreach ($period as $date) {
            $total[$date->format('Y-m')] = 0;
        }

        foreach ($result as $item) {
            $total[$item['month']] += $item['total'];
        }
//dd($total);
        return $total;
    }
//    /**
//     * @return User[] Returns an array of User objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
