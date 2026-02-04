<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-06-12
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\MasterData;
use App\Entity\MasterDataEntry;
use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MasterDataEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method MasterDataEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method MasterDataEntry[]    findAll()
 * @method MasterDataEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MasterDataEntryRepository extends ServiceEntityRepository
{
    /**
     * MasterDataEntryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MasterDataEntry::class);
    }

    /**
     * @param MasterData $masterData
     * @param string     $step
     * @param string     $key
     * @return MasterDataEntry|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByMasterDataAndStepAndKey(MasterData $masterData, string $step, string $key): ?MasterDataEntry
    {
        return $this->createQueryBuilder('mde')
            ->where('mde.masterData = :masterData')
            ->andWhere('mde.step = :step')
            ->andWhere('mde.key = :key')
            ->setParameters(['masterData' => $masterData, 'step' => $step, 'key' => $key])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param School $school
     * @param string $step
     * @param string $key
     * @return MasterDataEntry|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySchoolAndStepAndKey(School $school, string $step, string $key): ?MasterDataEntry
    {
        return $this->createQueryBuilder('mde')
            ->join('mde.masterData', 'md')
            ->where('md.school = :school')
            ->andWhere('mde.step = :step')
            ->andWhere('mde.key = :key')
            ->setParameters(['school' => $school, 'step' => $step, 'key' => $key])
            ->orderBy('md.finalisedAt', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @param MasterData $masterData
     * @param string     $step
     * @return MasterDataEntry[]|ArrayCollection|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByMasterDataAndStep(MasterData $masterData, string $step): ?array
    {
        return $this->createQueryBuilder('mde')
            ->where('mde.masterData = :masterData')
            ->andWhere('mde.step = :step')
            ->setParameters(['masterData' => $masterData, 'step' => $step])
            ->getQuery()->getResult();
    }
}
