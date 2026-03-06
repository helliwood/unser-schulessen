<?php

namespace App\Repository;

use App\Entity\SchoolAuthority;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SchoolAuthority|null find($id, $lockMode = null, $lockVersion = null)
 * @method SchoolAuthority|null findOneBy(array $criteria, array $orderBy = null)
 * @method SchoolAuthority[]    findAll()
 * @method SchoolAuthority[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SchoolAuthorityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchoolAuthority::class);
    }

    /**
     * @return SchoolAuthority[]
     */
    public function findAllWithSchools(): array
    {
        return $this->createQueryBuilder('sa')
            ->leftJoin('sa.schools', 's')
            ->addSelect('s')
            ->orderBy('sa.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithSchools(int $id): ?SchoolAuthority
    {
        return $this->createQueryBuilder('sa')
            ->leftJoin('sa.schools', 's')
            ->addSelect('s')
            ->where('sa.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array
     * @throws NonUniqueResultException
     */
    public function find4Ajax(
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit,
        string $search = ''
    ): array {
        $sortValues = ['name', 'contactPerson', 'contactEmail', 'schoolCount', 'userCount'];
        if (! \in_array($sort, $sortValues, true)) {
            $sort = 'name';
        }

        $qbTotal = $this->createQueryBuilder('sa')
            ->select('COUNT(sa.id)');

        $qbItems = $this->createQueryBuilder('sa')
            ->leftJoin('sa.schools', 's')
            ->leftJoin(User::class, 'u', 'WITH', 'u.schoolAuthority = sa')
            ->select('sa')
            ->addSelect('COUNT(DISTINCT s.id) AS schoolCount')
            ->addSelect('COUNT(DISTINCT u.id) AS userCount')
            ->groupBy('sa.id')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $search = \trim($search);
        if ($search !== '') {
            $tokens = \preg_split('/\s+/', $search);
            if ($tokens === false) {
                $tokens = [];
            }

            $tokenIndex = 0;
            foreach ($tokens as $token) {
                if ($token === '') {
                    continue;
                }
                $paramName = 'searchToken' . $tokenIndex;
                $condition = 'LOWER(sa.name) LIKE :' . $paramName
                    . ' OR LOWER(sa.contactPerson) LIKE :' . $paramName
                    . ' OR LOWER(sa.contactEmail) LIKE :' . $paramName;
                $qbItems->andWhere('(' . $condition . ')')
                    ->setParameter($paramName, '%' . \mb_strtolower($token) . '%');
                $qbTotal->andWhere('(' . $condition . ')')
                    ->setParameter($paramName, '%' . \mb_strtolower($token) . '%');
                $tokenIndex++;
            }
        }

        $totalRows = (int) $qbTotal->getQuery()->getSingleScalarResult();

        $sortMap = [
            'name' => 'sa.name',
            'contactPerson' => 'sa.contactPerson',
            'contactEmail' => 'sa.contactEmail',
            'schoolCount' => 'schoolCount',
            'userCount' => 'userCount',
        ];
        $qbItems->orderBy($sortMap[$sort] ?? 'sa.name', $sortDesc ? 'DESC' : 'ASC');

        $rows = $qbItems->getQuery()->getResult();
        $items = [];
        foreach ($rows as $row) {
            /** @var SchoolAuthority $authority */
            $authority = $row[0];
            $items[] = [
                'id' => $authority->getId(),
                'name' => $authority->getName(),
                'contactPerson' => $authority->getContactPerson(),
                'contactEmail' => $authority->getContactEmail(),
                'schoolCount' => (int) $row['schoolCount'],
                'userCount' => (int) $row['userCount'],
            ];
        }

        return ['items' => $items, 'totalRows' => $totalRows];
    }
}
