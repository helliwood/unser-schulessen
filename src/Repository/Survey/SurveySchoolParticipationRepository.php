<?php

namespace App\Repository\Survey;

use App\Entity\School;
use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveySchoolParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SurveySchoolParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveySchoolParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveySchoolParticipation[]    findAll()
 * @method SurveySchoolParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveySchoolParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveySchoolParticipation::class);
    }

    /**
     * @return SurveySchoolParticipation[]
     */
    public function findBySurvey(Survey $survey): array
    {
        return $this->createQueryBuilder('sp')
            ->addSelect('school')
            ->join('sp.school', 'school')
            ->where('sp.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getResult();
    }

    public function findParticipationBySchoolAndSurvey(School $school, Survey $survey): ?SurveySchoolParticipation
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.school = :school')
            ->andWhere('sp.survey = :survey')
            ->orderBy('sp.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('school', $school)
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return SurveySchoolParticipation[]
     */
    public function findActiveSurveysBySchool(School $school): array
    {
        return $this->createQueryBuilder('sp')
            ->addSelect('survey')
            ->join('sp.survey', 'survey')
            ->where('sp.school = :school')
            ->andWhere('survey.state = :state')
            ->andWhere('sp.hasParticipated = false')
            ->andWhere('survey.closesAt IS NULL OR survey.closesAt >= :now')
            ->setParameter('school', $school)
            ->setParameter('state', Survey::STATE_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function countParticipationsBySurvey(Survey $survey): int
    {
        return (int) $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('sp.survey = :survey')
            ->andWhere('sp.hasParticipated = true')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotalInvitationsBySurvey(Survey $survey): int
    {
        return (int) $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('sp.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return SurveySchoolParticipation[]
     */
    public function findParticipatedSurveysBySchool(School $school): array
    {
        return $this->createQueryBuilder('sp')
            ->addSelect('survey')
            ->join('sp.survey', 'survey')
            ->where('sp.school = :school')
            ->andWhere('sp.hasParticipated = true')
            ->andWhere('survey.schoolAuthority IS NOT NULL')
            ->andWhere('survey.school IS NULL')
            ->andWhere('survey.surveyTemplate = false')
            ->orderBy('sp.participatedAt', 'DESC')
            ->setParameter('school', $school)
            ->getQuery()
            ->getResult();
    }
}
