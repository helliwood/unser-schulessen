<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-28
 * Time: 14:35
 */

namespace App\Repository;

use App\Entity\Person;
use App\Entity\School;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Person|null find($id, $lockMode = null, $lockVersion = null)
 * @method Person|null findOneBy(array $criteria, array $orderBy = null)
 * @method Person[]    findAll()
 * @method Person[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonRepository extends ServiceEntityRepository
{
    /**
     * SchoolRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
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
        $sortValues = ["firstName", "lastName", "personType", "email"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "lastName";
        }

        $totalRows = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.school = :school')
            ->setParameter('school', $school)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('p')
            //->select('p, user.email as HIDDEN email')
            ->leftJoin('p.personType', 'personType')
            ->leftJoin('p.user', 'user')
            ->where('p.school = :school')
            ->setParameter('school', $school)
            ->orderBy("p." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }
}
