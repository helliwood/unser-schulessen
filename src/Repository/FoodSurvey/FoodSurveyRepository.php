<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2022-09-27
 * Time: 12:16
 */

namespace App\Repository\FoodSurvey;

use App\Entity\FoodSurvey\FoodSurvey;
use App\Entity\School;
use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FoodSurvey|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodSurvey|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodSurvey[]    findAll()
 * @method FoodSurvey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodSurveyRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodSurvey::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School $school
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param bool $closedOnly
     * @return array
     * @throws NonUniqueResultException|NoResultException
     */
    public function find4Ajax(
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        bool $closedOnly = false
    ) {
        $sortValues = ["name", "createdAt", "closesAt", "state", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $states = $closedOnly ? [FoodSurvey::STATE_CLOSED] : [FoodSurvey::STATE_ACTIVE, FoodSurvey::STATE_NOT_ACTIVATED];

        $totalRows = $this->createQueryBuilder('fs')
            ->select('COUNT(fs.id)')
            ->where('fs.school = :school')
            ->andWhere('fs.state IN (:states)')
            ->setParameter('school', $school)
            ->setParameter('states', $states)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('fs')
            ->select('fs')
            ->where('fs.school = :school')
            ->andWhere('fs.state IN (:states)')
            ->setParameter('school', $school)
            ->setParameter('states', $states)
            ->groupBy('fs')
            ->orderBy("fs." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @return array
     * @throws \Exception
     */
    public function getFoodSurveyStats(): array
    {
        $currentYear = (new \DateTime('now'))->format('Y');

        /** @var SchoolYear $schoolYear */
        $schoolYear = $this->getEntityManager()
            ->getRepository(SchoolYear::class)
            ->findOneBy(['year' => $currentYear], []);

        $result = $this->getEntityManager()
            ->getRepository(FoodSurvey::class)
            ->createQueryBuilder('fs')
            ->select('COUNT(fs.id) AS total, SUBSTRING(fs.activatedAt, 1, 7) month')
            ->andWhere('SUBSTRING(fs.activatedAt, 1, 7) BETWEEN :periodBegin AND :periodEnd')
            ->setParameter('periodBegin', $schoolYear->getPeriodBegin()->format('Y-m'))
            ->setParameter('periodEnd', $schoolYear->getPeriodEnd()->format('Y-m'))
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
            $total[$date->format('m.Y')] = 0;
        }

        foreach ($result as $item) {
            $month = (new \DateTime($item['month']))->format('m.Y');
            $total[$month] += $item['total'];
        }

        return $total;
    }
}
