<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-27
 * Time: 15:16
 */

namespace App\Repository\Survey;

use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyVoucher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SurveyVoucher|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyVoucher|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyVoucher[]    findAll()
 * @method SurveyVoucher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyVoucherRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyVoucher::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Survey $survey
     * @param string $sort
     * @param bool   $sortDesc
     * @param int    $page
     * @param int    $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function find4Ajax(
        Survey $survey,
        string $sort,
        bool $sortDesc,
        int $page,
        int $limit
    ) {
        $sortValues = ["voucher", "createdAt", "inUse"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "createdAt";
        }

        $totalRows = $this->createQueryBuilder('sv')
            ->select('COUNT(sv.id)')
            ->where('sv.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('sv')
            ->select('sv, COUNT(a.id) as HIDDEN inUse')
            ->leftJoin('sv.answers', 'a')
            ->where('sv.survey = :survey')
            ->setParameter('survey', $survey)
            ->groupBy('sv')
            ->orderBy($sort === "inUse" ? $sort : "sv." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }

    /**
     * @param string $voucher
     * @return bool
     */
    public function voucherExists(string $voucher): bool
    {
        return ! \is_null($this->findOneBy(['voucher' => $voucher]));
    }

    /**
     * @param string $voucher
     * @return SurveyVoucher|null
     */
    public function findByVoucher(string $voucher): ?SurveyVoucher
    {
        return $this->findOneBy(['voucher' => $voucher]);
    }

    /**
     * @param SurveyVoucher $voucher
     * @return bool
     */
    public function isVoucherInUse(SurveyVoucher $voucher): bool
    {
        return $voucher->getAnswers()->count() > 0;
    }
}
