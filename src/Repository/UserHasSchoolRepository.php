<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-27
 * Time: 08:35
 */

namespace App\Repository;

use App\Entity\School;
use App\Entity\User;
use App\Entity\UserHasSchool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserHasSchool|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserHasSchool|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserHasSchool[]    findAll()
 * @method UserHasSchool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserHasSchoolRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserHasSchool::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param School     $school
     * @param string     $sort
     * @param bool       $sortDesc
     * @param int        $page
     * @param int        $limit
     * @param array|null $onlyStates
     * @return array
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function find4Ajax(School $school, string $sort, bool $sortDesc, int $page, int $limit, ?array $onlyStates = null): array
    {
        $sortValues = ["name", "state", "email", "role", "personType"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "state";
        }

        $totalRows = $this->createQueryBuilder('uhs')
            ->select('COUNT(uhs.user)')
            ->where('uhs.school = :school')
            ->setParameter('school', $school);
        if (\is_array($onlyStates)) {
            $totalRows->andWhere('uhs.state IN (:states)')
                ->setParameter('states', $onlyStates);
        }

        $totalRows = $totalRows->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('uhs')
            //->select('p, user.email as HIDDEN email')
            ->leftJoin('uhs.user', 'user')
            ->leftJoin('user.person', 'person')
            ->where('uhs.school = :school')
            ->setParameter('school', $school)
            ->orderBy($sort === 'name' ? "person.lastName" : "uhs." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        if (\is_array($onlyStates)) {
            $items->andWhere('uhs.state IN (:states)')
                ->setParameter('states', $onlyStates);
        }
        $items = $items
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param User $user
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findSchools4Ajax(User $user, string $sort, bool $sortDesc, int $page, int $limit): array
    {

        $totalRows = $this->createQueryBuilder('uhs')
            ->select('COUNT(uhs.user)')
            ->where('uhs.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('uhs2')
            ->leftJoin('uhs2.school', 'school')
            ->where('uhs2.user = :user')
            ->setParameter('user', $user)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param string $email
     * @param School $school
     * @return bool
     * @throws NonUniqueResultException
     */
    public function emailExistsInSchool(string $email, School $school): bool
    {
        return ! \is_null($this->getEntityManager()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->innerJoin('u.userHasSchool', 'uhs')
            ->where('u.email LIKE :email')
            ->setParameter('email', $email)
            ->andWhere('uhs.school = :school')
            ->setParameter('school', $school)->getQuery()->getOneOrNullResult());
    }
}
