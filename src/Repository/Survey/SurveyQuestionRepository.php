<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-08-27
 * Time: 15:16
 */

namespace App\Repository\Survey;

use App\Entity\SchoolYear;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Entity\Survey\SurveyQuestionChoiceAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SurveyQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyQuestion[]    findAll()
 * @method SurveyQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyQuestionRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyQuestion::class);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration
     * @param Survey $survey
     * @param string $sort
     * @param bool $sortDesc
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException|\Doctrine\ORM\NoResultException
     */
    public function find4Ajax(Survey $survey, string $sort, bool $sortDesc, int $page, int $limit): array
    {
        $sortValues = ["question", "order", "type"];

        if (! \in_array($sort, $sortValues)) {
            $sort = "order";
        }

        $totalRows = $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getSingleScalarResult();

        $items = $this->createQueryBuilder('q')
            ->groupBy('q')
            ->where('q.survey = :survey')
            ->setParameter('survey', $survey)
            ->orderBy("q." . $sort, $sortDesc ? 'DESC' : 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ["totalRows" => $totalRows, "items" => $items];
    }


    /**
     * @param int $id
     * @throws \Throwable
     */
    public function up(int $id): void
    {
        $question = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('q')
                ->update()
                ->set('q.order', 'q.order+1')
                ->where('q.survey = :survey')
                ->andWhere('q.order = :lower_order')
                ->setParameter('survey', $question->getSurvey())
                ->setParameter('lower_order', $question->getOrder() - 1)
                ->getQuery()->execute();

            $this->createQueryBuilder('q')
                ->update()
                ->set('q.order', 'q.order-1')
                ->where('q.survey = :survey')
                ->andWhere('q.id = :id')
                ->setParameter('survey', $question->getSurvey())
                ->setParameter('id', $question->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param int $id
     * @throws \Throwable
     */
    public function down(int $id): void
    {
        $question = $this->find($id);
        $this->getEntityManager()->beginTransaction();
        try {
            $this->createQueryBuilder('q')
                ->update()
                ->set('q.order', 'q.order+1')
                ->Where('q.id = :id')
                ->setParameter('id', $question->getId())
                ->getQuery()->execute();

            $this->createQueryBuilder('q')
                ->update()
                ->set('q.order', 'q.order-1')
                ->where('q.survey = :survey')
                ->andWhere('q.order = :greater_order')
                ->andWhere('q.id != :id')
                ->setParameter('survey', $question->getSurvey())
                ->setParameter('greater_order', $question->getOrder() + 1)
                ->setParameter('id', $question->getId())
                ->getQuery()->execute();

            $this->getEntityManager()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    /**
     * @param Survey $survey
     */
    public function reorderAll(Survey $survey): void
    {
        foreach ($this->findBy(['survey' => $survey], ['order' => 'ASC']) as $i => $category) {
            $category->setOrder($i + 1);
        }
    }

    /**
     * @return array|string[]
     * @throws \Exception
     */
    public function getSurveyQuestionStats(?string $year = null): array
    {
        $requestedYear = ! \is_null($year) ? $year : (new \DateTime('now'))->format('Y');

        /** @var SchoolYear $schoolYear */
        $schoolYear = $this->getEntityManager()
            ->getRepository(SchoolYear::class)
            ->findOneBy(['year' => $requestedYear]);

        $result = $this->getEntityManager()
            ->getRepository(SurveyQuestion::class)
            ->createQueryBuilder('sq')
            ->innerJoin('sq.answers', 'a')
            ->innerJoin('sq.survey', 's')
            ->select('COUNT(a.id) AS total, SUBSTRING(a.createdAt, 1, 7) month')
            ->andWhere('sq.order = 1')
            ->andWhere('s.surveyTemplate = false')
            ->andWhere('SUBSTRING(s.activatedAt, 1, 7) >= :periodBegin')
            ->andWhere('SUBSTRING(s.activatedAt, 1, 7) <= :periodEnd')
            ->setParameter('periodBegin', $schoolYear->getPeriodBegin()->format('Y-m'))
            ->setParameter('periodEnd', $schoolYear->getPeriodEnd()->format('Y-m'))
            ->orderBy('month', 'ASC')
            ->groupBy('month')
            ->getQuery()
            ->getArrayResult();

//            MUSTERSCHULE RAUS RECHNEN???
//            ->andWhere('s.school != "Musterschule' )

        $period = new \DatePeriod(
            $schoolYear->getPeriodBegin(),
            new \DateInterval('P1M'),
            $schoolYear->getPeriodEnd()
        );

        $total = [];
        foreach ($period as $date) {
            $total[$date->format('m.Y')] = 0;
        }

        foreach ($result as $item) {
            $month = (new \DateTime($item['month']))->format('m.Y');
            if (\array_key_exists($month, $total)) {
                $total[$month] += $item['total'];
            }
        }

        $result2 = $this->getEntityManager()
            ->getRepository(SurveyQuestionChoiceAnswer::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.question', 'sq')
            ->innerJoin('sq.survey', 's')
            ->select('COUNT(a.id) AS total, SUBSTRING(a.createdAt, 1, 7) month')
            ->andWhere('sq.order = 1')
            ->andWhere('s.surveyTemplate = false')
            ->andWhere('SUBSTRING(s.activatedAt, 1, 7) >= :periodBegin')
            ->andWhere('SUBSTRING(s.activatedAt, 1, 7) <= :periodEnd')
            ->setParameter('periodBegin', $schoolYear->getPeriodBegin()->format('Y-m'))
            ->setParameter('periodEnd', $schoolYear->getPeriodEnd()->format('Y-m'))
            ->orderBy('month', 'ASC')
            ->groupBy('month')
            ->getQuery()
            ->getResult();

        foreach ($result2 as $item) {
            $month = (new \DateTime($item['month']))->format('m.Y');
            $total[$month] += $item['total'];
        }

        return $total;
    }
}
