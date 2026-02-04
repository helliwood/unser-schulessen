<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-06-12
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchoolYear|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolYear|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolYear[]    findAll()
 * @method SchoolYear[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolYearRepository extends ServiceEntityRepository
{
    /**
     * MasterDataRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolYear::class);
    }

    /**
     * @return SchoolYear|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findCurrent(): ?SchoolYear
    {
        return $this->createQueryBuilder('sy')
            ->andWhere('sy.periodBegin <= :now')
            ->andWhere('sy.periodEnd >= :now')
            ->setParameter('now', (new \DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return SchoolYear|null
     * @throws \DateMalformedStringException
     * @throws NonUniqueResultException
     */
    public function findPrevious(): ?SchoolYear
    {
        return $this->createQueryBuilder('sy')
            ->andWhere('sy.periodBegin <= :now')
            ->andWhere('sy.periodEnd >= :now')
            ->setParameter('now', (new \DateTime())->modify("-1 year")->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
