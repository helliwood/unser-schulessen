<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-27
 * Time: 15:16
 */

namespace App\Repository\Survey;

use App\Entity\School;
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
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @param bool   $closedOnly
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(
        School $school,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        bool $closedOnly = false
    ) {
        $sortValues = ["name", "createdAt", "closesAt", "state", "questions", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $states = $closedOnly ? [Survey::STATE_CLOSED] : [Survey::STATE_ACTIVE, Survey::STATE_NOT_ACTIVATED];

        $totalRows = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.school = :school')
            ->andWhere('s.state IN (:states)')
            ->andWhere('s.surveyTemplate = false')
            ->setParameter('school', $school)
            ->setParameter('states', $states)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('s')
            ->select('s, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('s.questions', 'q')
            ->where('s.school = :school')
            ->andWhere('s.state IN (:states)')
            ->andWhere('s.surveyTemplate = false')
            ->setParameter('school', $school)
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
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
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
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('s')
            ->select('s, COUNT(q.id) as HIDDEN questions')
            ->leftJoin('s.questions', 'q')
            ->where('s.surveyTemplate = true')
            ->groupBy('s')
            ->orderBy($sort === "questions" ? $sort : "s." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
