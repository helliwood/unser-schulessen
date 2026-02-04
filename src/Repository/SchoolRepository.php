<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\School;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolRepository extends ServiceEntityRepository
{
    /**
     * SchoolRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry, string $app_state_country)
    {
        parent::__construct($registry, School::class);
        $this->app_state_country = $app_state_country;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param string|null $flag
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(string $sort, bool $sortDesc, int $page, int $limit, ?string $flag = null): array
    {
        $sortValues = ["name", "createdAt", "address.city", "hasCurrentMasterData", "lastQcDate", "finalisedCount", "unfinalisedCount", "surveys", "foodSurveys", "userHasSchoolCount", "id"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "name";
        }

        $qbTotal = $this->createQueryBuilder('s');
        $qbTotal->leftJoin('s.address', 'address')
            ->select('COUNT(s.id)');

        $qbItems = $this->createQueryBuilder('s');
        $qbItems->leftJoin('s.address', 'address');

        $currentYear = (int)\date('Y');

        $qbItems->leftJoin('s.masterData', 'md')
            ->leftJoin('md.schoolYear', 'sy')
            ->addSelect('MAX(CASE WHEN sy.year = :year THEN 1 ELSE 0 END) AS hasCurrentMasterData')
            ->leftJoin('s.results', 'r')
            ->addSelect('(SELECT COUNT(r1.id)
                 FROM App\Entity\QualityCheck\Result r1
                 WHERE r1.school = s AND r1.finalised = true) AS finalisedCount')
            ->addSelect('(SELECT COUNT(r2.id)
                 FROM App\Entity\QualityCheck\Result r2
                 WHERE r2.school = s AND r2.finalised = false) AS unfinalisedCount')
            ->addSelect('(SELECT COUNT(su1.id) 
                FROM App\Entity\Survey\Survey su1 
                WHERE su1.school = s) AS surveys')
            ->addSelect('(SELECT COUNT(fs1.id) 
                FROM App\Entity\FoodSurvey\FoodSurvey fs1 
                WHERE fs1.school = s) AS foodSurveys')
            ->addSelect('(SELECT MAX(r3.createdAt)
                 FROM App\Entity\QualityCheck\Result r3
                 WHERE r3.school = s) AS lastQcDate')
            ->addSelect('(SELECT COUNT(uhs1.user)
                 FROM App\Entity\UserHasSchool uhs1
                 WHERE uhs1.school = s) AS userHasSchoolCount')
            ->addSelect('(SELECT COUNT(uhs2.user)
                 FROM App\Entity\UserHasSchool uhs2
                 WHERE uhs2.school = s AND uhs2.state = 0) AS invitationCount')
            ->addSelect('(SELECT COUNT(uhs3.user)
                 FROM App\Entity\UserHasSchool uhs3
                 WHERE uhs3.school = s AND uhs3.state = 1) AS memberCount')
            ->setParameter('year', $currentYear)
            ->groupBy('s.id, address.id')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($flag === 'mini-check') {
            $qbItems->andWhere("s.miniCheck = true");
            $qbTotal->andWhere("s.miniCheck = true");
        } elseif ($flag === 'no-mini-check') {
            $qbItems->andWhere("s.miniCheck = false");
            $qbTotal->andWhere("s.miniCheck = false");
        } else {
            if ($this->app_state_country === 'bb' && ! \is_null($flag)) {
                $qbTotal->andWhere("JSON_VALUE(s.flags, '$." . $flag . "') = true");
                $qbItems->andWhere("JSON_VALUE(s.flags, '$." . $flag . "') = true");
            }

            if ($this->app_state_country === 'by' & ! \is_null($flag)) {
                $qbTotal->andWhere("address.district = :district")
                    ->setParameter('district', $flag)
                    ->orderBy("address.district");
                $qbItems->andWhere("address.district = :district")
                    ->setParameter('district', $flag)
                    ->orderBy("address.district");
            }
        }


        $totalRows = $qbTotal
            ->getQuery()
            ->getSingleScalarResult();

        // Mapping für Sortierung
        $sortMap = [
            'id' => 's.id',
            'name' => 's.name',
            'createdAt' => 's.createdAt',
            'address.city' => 'address.city',
            'lastQcDate' => 'lastQcDate',
            'hasCurrentMasterData' => 'hasCurrentMasterData',
            'finalisedCount' => 'finalisedCount',
            'unfinalisedCount' => 'unfinalisedCount',
            'surveys' => 'surveys',
            'foodSurveys' => 'foodSurveys',
            'userHasSchoolCount' => 'userHasSchoolCount',
        ];

        $sortExpr = $sortMap[$sort] ?? 's.name';
        $qbItems->orderBy($sortExpr, $sortDesc ? 'DESC' : 'ASC');

        $rows = $qbItems->getQuery()->getResult();

        $items = [];
        foreach ($rows as $row) {
            /** @var School $school */
            $school = $row[0];
            $items[] = \array_merge($school->jsonSerialize(), [
                'hasCurrentMasterData' => (bool)$row['hasCurrentMasterData'],
                'finalisedCount' => $row['finalisedCount'],
                'unfinalisedCount' => $row['unfinalisedCount'],
                'lastQcDate' => $row['lastQcDate'] ? DateTime::createFromFormat('Y-m-d H:i:s', $row['lastQcDate'])->format('d.m.Y') : null,
                'surveys' => $row['surveys'],
                'foodSurveys' => $row['foodSurveys'],
                'userHasSchoolCount' => $row['userHasSchoolCount'],
                'invitationCount' => $row['invitationCount'],
                'memberCount' => $row['memberCount'],
            ]);
        }

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
