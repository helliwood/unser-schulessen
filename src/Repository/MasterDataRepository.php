<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-06-12
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\MasterData;
use App\Entity\School;
use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MasterData|null find($id, $lockMode = null, $lockVersion = null)
 * @method MasterData|null findOneBy(array $criteria, array $orderBy = null)
 * @method MasterData[]    findAll()
 * @method MasterData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MasterDataRepository extends ServiceEntityRepository
{
    /**
     * MasterDataRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MasterData::class);
    }

    /**
     * @param School $school
     * @return MasterData|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByCurrentSchoolYear(School $school): ?MasterData
    {
        return $this->createQueryBuilder('md')
            ->innerJoin('md.schoolYear', 'sy')
            ->where('md.school = :school')
            ->andWhere('sy.periodBegin <= :now')
            ->andWhere('sy.periodEnd >= :now')
            ->setParameter('now', new \DateTime())
            ->setParameter('school', $school)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $year
     * @return array|string[]
     * @throws \DateMalformedPeriodStringException
     * @throws \Exception
     */
    public function getMasterDataStats(?string $year = null): array
    {
        $requestedYear = new \DateTimeImmutable($year ? "$year-09-01" : 'now');

        /** @var SchoolYear|null $schoolYear */
        $schoolYear = $this->getEntityManager()
            ->getRepository(SchoolYear::class)
            ->createQueryBuilder('sy')
            ->where('sy.periodBegin <= :now')
            ->andWhere('sy.periodEnd >= :now')
            ->setParameter('now', $requestedYear)
            ->getQuery()
            ->getOneOrNullResult();

        $result = $this->getEntityManager()
            ->getRepository(MasterData::class)
            ->createQueryBuilder('m')
            ->select('COUNT(m.id) as total, SUBSTRING(m.finalisedAt, 1, 7) AS month')
            ->where('m.finalised = 1')
            ->andWhere('m.schoolYear = ' . $schoolYear->getYear())
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
            $month = (new \DateTimeImmutable($item['month']))->format('m.Y');
            if (\array_key_exists($month, $total)) {
                $total[$month] += $item['total'];
            }
        }

        return $total;
    }
}
