<?php

namespace App\Tests\Controller\Admin;

use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class Questionnaire2ControllerTest extends AbstractTestController
{
    protected $client = null;
    protected const QUESTIONNAIRE_NEW = 'Fragebogen Neu';
    protected const QUESTIONNAIRE_CLONED = 'Fragebogen gekloned';

    public function setUp(): void
    {
        $this->client = static::createClient();
        $this->logIn();
    }

    public function testActivate()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        $postData = ['action' => 'activate_questionnaire', 'questionnaire_id' => $questionnaire->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    public function testNewBasedOn()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);

        $postData = ['questionnaire' => []];
        $postData['questionnaire']['name'] = self::QUESTIONNAIRE_CLONED;
        $postData['questionnaire']['basedOn'] = $questionnaire->getId();
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/new', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_NEW]);
        /** @var Questionnaire $clonedQuestionnaire */
        $clonedQuestionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_CLONED]);

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
            ->getResult()[0];

        $clonedSubCategoryQuestion = $this->getEntityManager()->getRepository(Category::class)->findOneBy(['id' => $clonedCategories['id']]);

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/admin/questionnaire/category/questions/' . $clonedSubCategoryQuestion->getId());
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame($clonedSubCategoryQuestion->getName() . ' Fragen', $crawler->filter('h1')->text());
    }

    public function testDelete()
    {
        /** @var Questionnaire $questionnaire */
        $questionnaire = $this->getEntityManager()->getRepository(Questionnaire::class)->findOneBy(['name' => self::QUESTIONNAIRE_CLONED]);

        $postData = ['action' => 'delete_questionnaire', 'questionnaire_id' => $questionnaire->getId()];

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/admin/questionnaire/', $postData, [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }
}
