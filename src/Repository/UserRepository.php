<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-27
 * Time: 08:35
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array|string[]
     */
    public function getNewMemberStats(): array
    {
        return \array_reverse($this->createQueryBuilder('u')
            ->select('COUNT(u.id) as total, SUBSTRING(u.createdAt, 1, 7) as month')
            ->where('u.createdAt IS NOT NULL')
            ->orderBy('u.createdAt', 'DESC')
            ->groupBy('month')
            ->setMaxResults(13)
            ->getQuery()
            ->getArrayResult());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $username
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function loadUserByUsername($username): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email LIKE :email')
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $token
     * @return User
     * @throws NonUniqueResultException
     */
    public function findUserByToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('MD5(CONCAT(u.email,u.createdAt)) = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findEmployees4Ajax(string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["name", "state", "email", "role", "personType"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "name";
        }

        $totalRows = $this->createQueryBuilder('user')
            ->select('COUNT(user)')
            ->where('user.employee = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('user')
            ->leftJoin('user.person', 'person')
            ->where('user.employee = 1')
            ->orderBy($sort === 'name' ? "person.lastName" : "uhs." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
