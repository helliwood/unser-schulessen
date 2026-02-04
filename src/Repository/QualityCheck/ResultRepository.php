<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-12
 * Time: 12:16
 */

namespace App\Repository\QualityCheck;

use App\Entity\QualityCheck\Result;
use App\Entity\School;
use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Result|null find($id, $lockMode = null, $lockVersion = null)
 * @method Result|null findOneBy(array $criteria, array $orderBy = null)
 * @method Result[]    findAll()
 * @method Result[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResultRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School $school
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(School $school, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["createdAt", "finalisedAt"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $totalRows = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.school = :school')
            ->setParameter('school', $school)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('r')
            ->where('r.school = :school')
            ->setParameter('school', $school)
            ->orderBy("r." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param string|null $year
     * @return array|string[]
     * @throws \DateMalformedPeriodStringException
     */
    public function getResultStats(?string $year = null): array
    {
        $requestedYear = ! \is_null($year) ? $year : (new \DateTime('now'))->format('Y');

        /** @var SchoolYear $schoolYear */
        $schoolYear = $this->getEntityManager()
            ->getRepository(SchoolYear::class)
            ->findOneBy(['year' => $requestedYear]);

        $result = $this->getEntityManager()
            ->getRepository(Result::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id) AS total, SUBSTRING(r.finalisedAt, 1, 7) AS month')
            ->where('r.finalised = 1')
            ->andWhere('SUBSTRING(r.finalisedAt, 1, 7) >= :periodBegin')
            ->andWhere('SUBSTRING(r.finalisedAt, 1, 7) <= :periodEnd ')
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
            $total[$date->format('Y-m')] = 0;
        }

        foreach ($result as $item) {
            $total[$item['month']] += $item['total'];
        }

        return $total;
    }
}
