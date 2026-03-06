<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-27
 * Time: 15:16
 */

namespace App\Repository\Survey;

use App\Entity\School;
use App\Entity\SchoolAuthority;
use App\Entity\Survey\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Survey|null find($id, $lockMode = null, $lockVersion = null)
 * @method Survey|null findOneBy(array $criteria, array $orderBy = null)
 * @method Survey[]    findAll()
 * @method Survey[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        bool $closedOnly = false,
        bool $deletedOnly = false
    ) {
        $sortValues = ["name", "createdAt", "closesAt", "state", "questions", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $states = $closedOnly ? [Survey::STATE_CLOSED] : [Survey::STATE_ACTIVE, Survey::STATE_NOT_ACTIVATED];

        $totalRowsQb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.school = :school')
            ->andWhere('s.surveyTemplate = false')
            ->setParameter('school', $school);

        if ($deletedOnly) {
            $totalRowsQb->andWhere('s.deleted = true');
        } else {
            $totalRowsQb->andWhere('s.deleted = false');
            $totalRowsQb->andWhere('s.state IN (:states)')
                ->setParameter('states', $states);
        }

        $totalRows = $totalRowsQb->getQuery()->getSingleScalarResult();

        $itemsQb = $this->createQueryBuilder('s')
            ->select('s, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('s.questions', 'q')
            ->where('s.school = :school')
            ->andWhere('s.surveyTemplate = false')
            ->setParameter('school', $school);

        if ($deletedOnly) {
            $itemsQb->andWhere('s.deleted = true');
        } else {
            $itemsQb->andWhere('s.deleted = false');
            $itemsQb->andWhere('s.state IN (:states)')
                ->setParameter('states', $states);
        }

        $items = $itemsQb
            ->groupBy('s')
            ->orderBy($sort === "questions" ? $sort : "s." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findSurveyTemplates4Ajax(
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit
    ) {
        $sortValues = ["name", "createdAt", "closesAt", "state", "questions", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $totalRows = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.surveyTemplate = true')
            ->andWhere('s.schoolAuthority IS NULL')
            ->andWhere('s.deleted = false')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('s')
            ->select('s, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('s.questions', 'q')
            ->where('s.surveyTemplate = true')
            ->andWhere('s.schoolAuthority IS NULL')
            ->andWhere('s.deleted = false')
            ->groupBy('s')
            ->orderBy($sort === "questions" ? $sort : "s." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param SchoolAuthority $schoolAuthority
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @param bool $closedOnly
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4SchoolAuthority(
        SchoolAuthority $schoolAuthority,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        bool $closedOnly = false
    ): array {
        $sortValues = ["name", "createdAt", "closesAt", "state", "questions", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $states = $closedOnly ? [Survey::STATE_CLOSED] : [Survey::STATE_ACTIVE, Survey::STATE_NOT_ACTIVATED];

        $totalRows = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.school', 'school')
            ->where('school.schoolAuthority = :schoolAuthority')
            ->andWhere('s.state IN (:states)')
            ->andWhere('s.surveyTemplate = false')
            ->andWhere('s.deleted = false')
            ->setParameter('schoolAuthority', $schoolAuthority)
            ->setParameter('states', $states)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('s')
            ->select('s, COUNT(q.id) as HIDDEN questions, school.name as HIDDEN schoolName')
            ->join('s.school', 'school')
            ->leftJoin('s.questions', 'q')
            ->where('school.schoolAuthority = :schoolAuthority')
            ->andWhere('s.state IN (:states)')
            ->andWhere('s.surveyTemplate = false')
            ->andWhere('s.deleted = false')
            ->setParameter('schoolAuthority', $schoolAuthority)
            ->setParameter('states', $states)
            ->groupBy('s')
            ->orderBy($sort === "questions" ? $sort : "s." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param SchoolAuthority $schoolAuthority
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySchoolAuthority4Ajax(
        SchoolAuthority $schoolAuthority,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        string $search = ''
    ): array {
        $sortValues = ['name', 'createdAt', 'closesAt', 'state'];

        if (! \in_array($sort, $sortValues, true)) {
            $sort = 'createdAt';
        }

        $qb = $this->createQueryBuilder('s')
            ->where('s.schoolAuthority = :schoolAuthority')
            ->andWhere('s.deleted = false')
            ->setParameter('schoolAuthority', $schoolAuthority);

        if ($search !== '') {
            $qb->andWhere('LOWER(s.name) LIKE :search')
                ->setParameter('search', '%' . \mb_strtolower($search) . '%');
        }

        $totalRows = (int) $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->select('s, COUNT(sp.id) as HIDDEN participations')
            ->leftJoin('s.schoolParticipations', 'sp')
            ->groupBy('s')
            ->orderBy('s.' . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($items as $item) {
            if (! $item instanceof Survey) {
                continue;
            }
            $createdAt = $item->getCreatedAt();
            $closesAt = $item->getClosesAt();
            $result[] = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'state' => $item->getState(),
                'stateLabel' => Survey::STATE_LABELS[$item->getState()] ?? null,
                'createdAt' => $createdAt ? $createdAt->format('d.m.Y') : '-',
                'closesAt' => $closesAt ? $closesAt->format('d.m.Y') : '-',
                'participationCount' => $item->getSchoolParticipations()->count(),
                'isSchoolAuthoritySurvey' => $item->isSchoolAuthoritySurvey(),
                'isSchoolAuthorityTemplate' => $item->isSchoolAuthorityTemplate(),
            ];
        }

        return ["totalRows" => $totalRows, "items" => $result];
    }

    public function countBySchoolAuthority(SchoolAuthority $schoolAuthority): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.schoolAuthority = :schoolAuthority')
            ->setParameter('schoolAuthority', $schoolAuthority)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param SchoolAuthority $schoolAuthority
     * @param School $school
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findSchoolAuthorityTemplates4Ajax(
        SchoolAuthority $schoolAuthority,
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit
    ): array {
        $sortValues = ['name', 'createdAt', 'closesAt', 'state'];

        if (! \in_array($sort, $sortValues, true)) {
            $sort = 'createdAt';
        }

        $qb = $this->createQueryBuilder('s')
            ->join('s.schoolParticipations', 'sp')
            ->where('s.schoolAuthority = :schoolAuthority')
            ->andWhere('s.surveyTemplate = true')
            ->andWhere('s.state = :state')
            ->andWhere('sp.school = :school')
            ->andWhere('s.deleted = false')
            ->setParameter('schoolAuthority', $schoolAuthority)
            ->setParameter('state', Survey::STATE_ACTIVE)
            ->setParameter('school', $school);

        $totalRows = (int) $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb->select('s')
            ->groupBy('s')
            ->orderBy('s.' . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
