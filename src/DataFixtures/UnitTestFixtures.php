<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Person;
use App\Entity\PersonType;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\User;
use App\Entity\UserHasSchool;
use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Formula;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\QualityCheck\Result;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UnitTestFixtures extends Fixture
{
    public const TESTUSER_EMAIL = 'test@helliwood.com';
    public const TESTUSER2_EMAIL = 'test2@helliwood.com';
    public const TESTUSER_PASSWORD = 'abcdef12345!';
    public const TESTUSER_CITY = 'Teststadt';
    public const TESTUSER_SCHOOL = 'Testschule';
    public const TESTUSER_SALUTATION = 'Herr';
    public const TESTUSER_ACADEMIC_TITLE = 'Dr.';
    public const TESTUSER_FIRST_NAME = 'Test';
    public const TESTUSER_LAST_NAME = 'Tester';

    /**
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        /** Erzeugt letztes, dieses und nächstes Schuljahr in der DB */
        for ($year = date('Y') - 1; $year <= date('Y') + 1; $year++) {
            $schoolYear = new SchoolYear();
            $schoolYear->setLabel($year);
            $schoolYear->setPeriodBegin(new \DateTime(($year) . '-09-01'));
            $schoolYear->setPeriodEnd(new \DateTime(($year + 1) . '-08-31'));
            $schoolYear->setYear($year);
            $manager->persist($schoolYear);
            $manager->flush();
        }

        $schoolAddress = new Address();
        $schoolAddress->setCity(self::TESTUSER_CITY);
        $school = new School();
        $school->setName(self::TESTUSER_SCHOOL);
        $school->setAddress($schoolAddress);
        $manager->persist($school);

        $user = new User();
        $user->setEmail(self::TESTUSER_EMAIL);
        $user->setPassword($this->encoder->hashPassword($user, self::TESTUSER_PASSWORD));
        $user->setState(User::STATE_ACTIVE);
        $user->addRole(User::ROLE_ADMIN);
        $manager->persist($user);

        $person = new Person();
        $person->setFirstName('Test');
        $person->setLastName('Tester');
        $person->setUser($user);
        $manager->persist($person);

        $user2 = new User();
        $user2->setEmail(self::TESTUSER2_EMAIL);
        $user2->setPassword($this->encoder->hashPassword($user, self::TESTUSER_PASSWORD));
        $user2->setState(User::STATE_ACTIVE);
        $user2->addRole(User::ROLE_HEADMASTER);
        $manager->persist($user2);

        $person2 = new Person();
        $person2->setFirstName('Test2');
        $person2->setLastName('Tester2');
        $person2->setUser($user2);
        $manager->persist($person2);

        $personType = new PersonType();
        $personType->setName(PersonType::TYPE_HEADMASTER);
        $manager->persist($personType);

        $personType = new PersonType();
        $personType->setName(PersonType::TYPE_KITCHEN);
        $manager->persist($personType);

        $personType = new PersonType();
        $personType->setName(PersonType::TYPE_FOOD_COMMISSIONER);
        $manager->persist($personType);

        $personType = new PersonType();
        $personType->setName(PersonType::TYPE_MEMBER);
        $manager->persist($personType);

        $personType = new PersonType();
        $personType->setName(PersonType::TYPE_SCHOOL_AUTHORITIES);
        $manager->persist($personType);

        $userHasSchool = new UserHasSchool();
        $userHasSchool->setRole(User::ROLE_HEADMASTER);
        $userHasSchool->setSchool($school);
        $userHasSchool->setUser($user);
        $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
        $userHasSchool->setRespondedAt(new \DateTime());
        $userHasSchool->setPersonType($manager->find(PersonType::class, PersonType::TYPE_HEADMASTER));
        $manager->persist($userHasSchool);

        $userHasSchool2 = new UserHasSchool();
        $userHasSchool2->setRole(User::ROLE_HEADMASTER);
        $userHasSchool2->setSchool($school);
        $userHasSchool2->setUser($user2);
        $userHasSchool2->setState(UserHasSchool::STATE_ACCEPTED);
        $userHasSchool2->setRespondedAt(new \DateTime());
        $userHasSchool2->setPersonType($manager->find(PersonType::class, PersonType::TYPE_HEADMASTER));
        $manager->persist($userHasSchool2);

        $user->getUserHasSchool()->add($userHasSchool);
        $user->setCurrentSchool($school);
        $user2->getUserHasSchool()->add($userHasSchool2);
        $user2->setCurrentSchool($school);

        // Minimaler aktiver Qualitäts-Check Fragebogen für Controller-Tests.
        $questionnaire = new Questionnaire();
        $questionnaire
            ->setName('Test Fragebogen')
            ->setCreatedBy($user)
            ->setState(Questionnaire::STATE_ACTIVE);
        $manager->persist($questionnaire);

        $category = new Category();
        $category
            ->setQuestionnaire($questionnaire)
            ->setName('Test Kategorie')
            ->setOrder(1);
        $manager->persist($category);

        $question = new Question();
        $question
            ->setCategory($category)
            ->setQuestion('Test Frage')
            ->setOrder(1)
            ->setType(Question::TYPE_NEEDED);
        $formula = new Formula();
        $formula
            ->setFormulaTrue('>= 4')
            ->setFormulaFalse('<= 2');
        $question->setFormula($formula);
        $manager->persist($question);

        $result = new Result();
        $result
            ->setSchool($school)
            ->setCreatedBy($user)
            ->setQuestionnaire($questionnaire)
            ->setFinalised(true)
            ->setFinalisedBy($user)
            ->setFinalisedAt(new \DateTime());
        $answer = new Answer();
        $answer
            ->setQuestion($question)
            ->setResult($result)
            ->setAnswer(Answer::ANSWER_TRUE);
        $result->setAnswers(new ArrayCollection([$answer]));
        $manager->persist($result);
        $manager->persist($answer);

        $manager->flush();
    }
}
