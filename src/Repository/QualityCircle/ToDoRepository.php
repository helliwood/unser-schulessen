<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-12
 * Time: 16:16
 */

namespace App\Repository\QualityCircle;

use App\Entity\QualityCircle\ToDo;
use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ToDo|null find($id, $lockMode = null, $lockVersion = null)
 * @method ToDo|null findOneBy(array $criteria, array $orderBy = null)
 * @method ToDo[]    findAll()
 * @method ToDo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ToDoRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDo::class);
    }

    /**
     * @param School $school
     * @return ToDo|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUnarchivedToDoBySchool(School $school): ?ToDo
    {
        return $this->createQueryBuilder('todo')
            ->innerJoin('todo.result', 'r')
            ->where('r.school = :school')
            ->andWhere('todo.archived = FALSE')
            ->setParameter('school', $school)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param School $school
     * @return ToDo[]|ArrayCollection
     */
    public function getArchivedToDosBySchool(School $school): array
    {
        return $this->createQueryBuilder('todo')
            ->innerJoin('todo.result', 'r')
            ->where('r.school = :school')
            ->andWhere('todo.archived = TRUE')
            ->setParameter('school', $school)
            ->getQuery()
            ->getResult();
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
        $sortValues = ["createdAt", "archivedAt"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $totalRows = $this->createQueryBuilder('td')
            ->select('COUNT(td.id)')
            ->innerJoin('td.result', 'r')
            ->where('r.school = :school')
            ->setParameter('school', $school)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('td')
            ->innerJoin('td.result', 'r')
            ->where('r.school = :school')
            ->setParameter('school', $school)
            ->orderBy("td." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
