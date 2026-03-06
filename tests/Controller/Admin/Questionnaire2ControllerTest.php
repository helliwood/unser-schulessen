<?php

namespace App\Tests\Controller\Admin;

use App\DataFixtures\UnitTestFixtures;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\User;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class Questionnaire2ControllerTest extends AbstractTestController
{
    protected $client = null;
    protected static $questionnaireBaseName;
    protected static $questionnaireClonedName;

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
        if (self::$questionnaireBaseName === null) {
            $suffix = uniqid('', true);
            self::$questionnaireBaseName = 'Fragebogen Neu '.$suffix;
            self::$questionnaireClonedName = 'Fragebogen gekloned '.$suffix;
        }
    }

    public function testActivate()
    {
        $questionnaire = $this->getOrCreateBaseQuestionnaire();

        $postData = ['action' => 'activate_questionnaire', 'questionnaire_id' => $questionnaire->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNewBasedOn()
    {
        $questionnaire = $this->getOrCreateBaseQuestionnaire();

        $postData = ['questionnaire' => []];
        $postData['questionnaire']['name'] = self::$questionnaireClonedName;
        $postData['questionnaire']['basedOn'] = $questionnaire->getId();
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Questionnaire $clonedQuestionnaire */
        $clonedQuestionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::$questionnaireClonedName]);
        $this->assertNotNull($clonedQuestionnaire);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/show/' . $clonedQuestionnaire->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($clonedQuestionnaire->getName(), $crawler->filter('h1')->text());

        $clonedCategories = $this
            ->getEntityManager()
            ->createQueryBuilder('c')
            ->select('c.id')
            ->from('App:QualityCheck\Category', 'c')
            ->where('c.questionnaire = :questionnaire')
            ->andWhere('c.parent IS NOT NULL')
            ->setParameter('questionnaire', $clonedQuestionnaire->getId())
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($clonedCategories);

        $clonedSubCategoryQuestion = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['id' => $clonedCategories[0]['id']]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/' . $clonedSubCategoryQuestion->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($clonedSubCategoryQuestion->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testDelete()
    {
        /** @var Questionnaire|null $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::$questionnaireClonedName]);
        if ($questionnaire === null) {
            $questionnaire = new Questionnaire();
            $questionnaire->setName(self::$questionnaireClonedName);
            $questionnaire->setCreatedBy($this->getFixtureUser());
            $this->getEntityManager()->persist($questionnaire);
            $this->getEntityManager()->flush();
        }

        $postData = ['action' => 'delete_questionnaire', 'questionnaire_id' => $questionnaire->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    private function getOrCreateBaseQuestionnaire(): Questionnaire
    {
        $em = $this->getEntityManager();
        /** @var Questionnaire|null $questionnaire */
        $questionnaire = $em->getRepository(Questionnaire::class)->findOneBy(['name' => self::$questionnaireBaseName]);
        if ($questionnaire !== null) {
            return $questionnaire;
        }

        $questionnaire = new Questionnaire();
        $questionnaire->setName(self::$questionnaireBaseName);
        $questionnaire->setCreatedBy($this->getFixtureUser());
        $em->persist($questionnaire);
        $em->flush();

        $parentCategory = new Category();
        $parentCategory->setName('Kategorie Parent '.uniqid('', true));
        $parentCategory->setOrder(1);
        $parentCategory->setQuestionnaire($questionnaire);
        $em->persist($parentCategory);

        $subCategory = new Category();
        $subCategory->setParent($parentCategory);
        $subCategory->setName('Kategorie Child '.uniqid('', true));
        $subCategory->setOrder(1);
        $subCategory->setQuestionnaire($questionnaire);
        $em->persist($subCategory);

        $em->flush();

        return $questionnaire;
    }

    private function getFixtureUser(): User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => UnitTestFixtures::TESTUSER_EMAIL]);
    }
}
