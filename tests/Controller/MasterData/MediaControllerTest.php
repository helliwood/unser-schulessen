<?php

namespace App\Tests\Controller\MasterData;

use App\Entity\Media;
use App\Entity\User;
use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class MediaControllerTest extends AbstractTestController
{

    protected $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $this->logIn();

    }

    public function testIndex()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/media/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Dokumentenspeicher', $crawler->filter('h1')->text());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/media/?page=1&size=10&sort=fileName&sortDesc=false', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

    }

    public function test4Ajax(){
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/media/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }

    const ORIG_FILE = __DIR__ . "/orig.pdf";
    const TEST_FILE_NAME = "test.pdf";
    const TEST_FILE = __DIR__ . "/" . self::TEST_FILE_NAME;

    public function testNew()
    {
        copy(
            self::ORIG_FILE,
            self::TEST_FILE
        );

        /** @var  $crawler */
        $crawler = $this->client->request('POST', '/master_data/media/file/new');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var  $pdf */
        $pdf = new UploadedFile(
            self::TEST_FILE,
            'test.pdf',
            'application/pdf'
        );

        $postData = ['media' => []];
        $postData['media']['description'] = "Description of the media";
        $postData['save'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'POST',
            '/master_data/media/file/new',
            $postData,
            ['media' => ['file' => $pdf]]
        );

        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var  $current_school */
        $current_school = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'test@helliwood.com'])->getId();

        /** @var  $mediaId */
        $mediaId = $this->getEntityManager()->getRepository(Media::class)->findOneBy(['fileName' => 'test.pdf'])->getId();

        $this->assertTrue(is_resource(fopen(__DIR__ . "/../../../var/data/documents/" . $current_school . "/" . $mediaId, 'r')));

    }

    public function testDownload()
    {
        /** @var  $mediaId */
        $mediaId = $this->getEntityManager()->getRepository(Media::class)->findOneBy(['fileName' => 'test.pdf'])->getId();

        ob_start();
        /** @var  $crawler */
        $crawler = $this->client->request('GET', '/master_data/media/download/' . $mediaId);
        $getContent = ob_get_contents();
        ob_end_clean();
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

//
//    public function testDownloadDGE()
//    {
//        ob_start();
////        /** @var  $crawler */
////        $crawler = $this->client->request('GET', );
//
//        $this->client->request('GET', '/master_data/media/');
//        $this->client->clickLink('<i class="fas fa-download"/> Der DGE-Qualitätsstandard');
//        dump($this->client->getResponse()->getContent());die;
////        dump($crawler); die;
//
////        $crawler->selectLink('')->link();
//
//        $getContent = ob_get_contents();
//        ob_end_clean();
//
//
//        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//    }
//
//    public function testDownloadVNS()
//    {
//        ob_start();
//        /** @var  $crawler */
//        $crawler = $this->client->request('GET', '/documents/Materialien_VNS.pdf');
//        $getContent = ob_get_contents();
//        ob_end_clean();
//        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//    }

//    public function testDownloadWrongId()
//    {
//        /** Response is an exception! */
//        $crawler = $this->client->request('GET', '/master_data/media/download/99999999');
//
//        // normally Symfony catches exceptions internal
//        $this->client->catchExceptions(false);
////dump($this->client->getResponse());die;
//        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
//
//    }

    public function testDeleteDownload()
    {
        /** @var  $mediaId */
        $mediaId = $this->getEntityManager()->getRepository(Media::class)->findOneBy(['fileName' => 'test.pdf'])->getId();

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/media/download/');

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/media/', ['action' => 'delete', 'id' => $mediaId], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($JSON_response);
    }
}
