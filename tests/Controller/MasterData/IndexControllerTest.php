<?php


namespace App\Tests\Controller\MasterData;


use App\Tests\Controller\AbstractTestController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends AbstractTestController
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
        $crawler = $this->client->request('GET', '/master_data/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Unsere Schule', $crawler->filter('h1')->text());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/show');
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testEditSchool()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/edit-school');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Schule bearbeiten', $crawler->filter('h1')->text());
    }

    public function testListMembersAjax()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/members/?page=1&size=10&sort=state&sortDesc=false', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $JSON_response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($JSON_response);
    }

    public function testEditSchoolPost()
    {
        $postData = [];
        $postData["school"]["name"] = "Testschule Neu";
        $postData['school']['schoolNumber'] = "012";
        $postData["school"]["headmaster"] = "";
        $postData["school"]["phoneNumber"] = "";
        $postData["school"]["faxNumber"] = "";
        $postData["school"]["emailAddress"] = "";
        $postData["school"]["webpage"] = "";
        $postData["school"]["educationAuthority"] = "";
        $postData["school"]["schoolType"] = "";
        $postData["school"]["schoolOperator"] = "";
        $postData["school"]["particularity"] = "";
        $postData["school"]["address"]["street"] = "";
        $postData["school"]["address"]["postalcode"] = "";
        $postData["school"]["address"]["city"] = "Berlin";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit-school', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        // Prüfe auf Erfolgsmeldung (kann b-alert oder andere Alert-Komponenten sein)
        $alertFound = false;
        try {
            $alertText = trim($crawler->filter("b-alert")->text());
            if (strpos($alertText, "Der Datensatz wurde erfolgreich gespeichert!") !== false) {
                $alertFound = true;
            }
        } catch (\Exception $e) {
            // b-alert nicht gefunden, versuche andere Alert-Selektoren
        }
        if (!$alertFound) {
            // Versuche andere mögliche Alert-Selektoren
            $pageContent = $crawler->text();
            $this->assertStringContainsString("Der Datensatz wurde erfolgreich gespeichert!", $pageContent, "Erfolgsmeldung nicht gefunden");
        }
        
        // Suche nach dem H2-Element, das den Schulnamen enthält (Überschriften wurden von H3 zu H2 geändert)
        $schoolNameFound = false;
        $h2Texts = [];
        $h2Elements = $crawler->filter("h2");
        if ($h2Elements->count() > 0) {
            $h2Elements->each(function($node) use (&$schoolNameFound, &$h2Texts) {
                $text = trim($node->text());
                $h2Texts[] = $text;
                if (strpos($text, "Testschule Neu") !== false) {
                    $schoolNameFound = true;
                }
            });
        }
        $this->assertTrue($schoolNameFound, "Schulname 'Testschule Neu' wurde nicht in H2-Elementen gefunden. Gefundene H2-Texte: " . implode(", ", $h2Texts));
    }

    public function testEdit()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/edit');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $postData = ['general' => []];
        $postData['general']['potential_current'] = "100";
        $postData['general']['potential_feature'] = "200";
        $postData['general']['students_current'] = "100";
        $postData['general']['class_level_1_4'] = "";
        $postData['general']['class_level_5_10'] = "";
        $postData['general']['class_level_11_13'] = "";
        $postData['general']['teacher_current'] = "5";
        $postData['general']['teacher_obligatory'] = "no";
        $postData['general']['description'] = "10-12";
        $postData['general']['minutes'] = "2";
        $postData['general']['opening_hours_from']['hour'] = "10";
        $postData['general']['opening_hours_from']['minute'] = "0";
        $postData['general']['opening_hours_to']['hour'] = "12";
        $postData['general']['opening_hours_to']['minute'] = "0";
        if ($_ENV['APP_STATE_COUNTRY'] != 'rp') {
            $postData['general']['midday_band'] = "no";
        }
        $postData['general']['opening_hours_kiosk_from']['hour'] = "";
        $postData['general']['opening_hours_kiosk_from']['minute'] = "";
        $postData['general']['opening_hours_kiosk_to']['hour'] = "";
        $postData['general']['opening_hours_kiosk_to']['minute'] = "";
        $postData['general']['bus_driving_times_earliest']['hour'] = "";
        $postData['general']['bus_driving_times_earliest']['minute'] = "";
        $postData['general']['bus_driving_times_latest']['hour'] = "";
        $postData['general']['bus_driving_times_latest']['minute'] = "";
        $postData['general']['dining_room'] = "yes";
        $postData['general']['dining_only'] = "no";
        $postData['general']['other_uses'] = "";
        $postData['general']['places'] = "50";
        $postData['general']['features'] = "";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['catering_system' => []];
        $postData['catering_system']['form_of_management_self'] = "1";
        $postData['catering_system']['form_of_management_external'] = "1";
        $postData['catering_system']['hot_meals'] = "1";
        $postData['catering_system']['cook_and_chill'] = "1";
        $postData['catering_system']['cook_and_freeze'] = "1";
        $postData['catering_system']['mixed_kitchen'] = "1";
        $postData['catering_system']['fresh'] = "1";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/2', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['standards_for_catering' => []];
        $postData['standards_for_catering']['number_menu_lines'] = "5";
        $postData['standards_for_catering']['vegetarian_menu'] = "1";
        $postData['standards_for_catering']['vegetarian_menu_line'] = "1";
        $postData['standards_for_catering']['menu_parts'] = "1";
        $postData['standards_for_catering']['other_quality_criterias'] = "";
        $postData['standards_for_catering']['daily_salat'] = "1";
        $postData['standards_for_catering']['free_drink'] = "1";
        $postData['standards_for_catering']['dessert'] = "1";
        $postData['standards_for_catering']['other_offers'] = "1";
        $postData['standards_for_catering']['dge'] = "yes_one";
        $postData['standards_for_catering']['dge_checks'] = "yes";
        $postData['standards_for_catering']['food_supplier_in_ag'] = "yes";
        $postData['standards_for_catering']['food_counter'][] = "table_fellowships";
        $postData['standards_for_catering']['additional_self_service'][] = "salat";
        $postData['standards_for_catering']['warm_keeping_period']['hour'] = "1";
        $postData['standards_for_catering']['warm_keeping_period']['minute'] = "0";
        $postData['standards_for_catering']['communication'] = "";
        $postData['standards_for_catering']['imbiss'] = "yes";
        $postData['standards_for_catering']['imbiss_offer'] = "";
        $postData['standards_for_catering']['imbiss_dge'] = "";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/3', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['ordering_system' => []];
        $postData['ordering_system']['period'] = "";
        $postData['ordering_system']['ordering_type'][] = "internet_in_writing";
        $postData['ordering_system']['ordering_cancellation'] = "";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/4', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['accounting_system' => []];
        $postData['accounting_system']['by'][] = "cash";
        $postData['accounting_system']['by'][] = "tokens";
        $postData['accounting_system']['by'][] = "scheduled_meal_vouchers";
        $postData['accounting_system']['by'][] = "prepaid_card_with_cash_loading";
        $postData['accounting_system']['by'][] = "prepaid_card_with_cashless_loading";
        $postData['accounting_system']['by'][] = "bank_card";
        $postData['accounting_system']['by'][] = "invoice";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/5', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['complaint_management' => []];
        $postData['complaint_management']['recipient'] = "";
        $postData['complaint_management']['catering_officer'] = "yes";
        $postData['complaint_management']['catering_officer_contact'] = "zuhause@mail.de";
        $postData['complaint_management']['student_survey'] = "no";
        $postData['complaint_management']['last_student_survey']['day'] = "";
        $postData['complaint_management']['last_student_survey']['month'] = "";
        $postData['complaint_management']['last_student_survey']['year'] = "";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/6', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['prices_and_subsidies' => []];
        $postData['prices_and_subsidies']['uniform_selling_price'] = "yes";
        $postData['prices_and_subsidies']['price'] = "3";
        $postData['prices_and_subsidies']['price_from'] = "2";
        $postData['prices_and_subsidies']['price_to'] = "4";
        $postData['prices_and_subsidies']['school_subsidies'] = "yes";
        $postData['prices_and_subsidies']['school_subsidies_amount'] = "";
        $postData['prices_and_subsidies']['school_indirect_subsidies'] = "no";
        $postData['prices_and_subsidies']['bund_subsidies'] = "";
        $postData['prices_and_subsidies']['other_subsidies'] = "";
        $postData['next'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/7', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData = ['term_of_contract' => []];
        $postData['term_of_contract']['start']['day'] = "1";
        $postData['term_of_contract']['start']['month'] = "1";
        $postData['term_of_contract']['start']['year'] = date("Y") - 1;
        $postData['term_of_contract']['end']['day'] = "1";
        $postData['term_of_contract']['end']['month'] = "4";
        $postData['term_of_contract']['end']['year'] = date("Y") + 1;
        if ($_ENV['APP_STATE_COUNTRY'] != 'rp') {
            $postData['term_of_contract']['catering_provider'] = "";
            $postData['term_of_contract']['catering_provider_mail'] = "";
            $postData['term_of_contract']['catering_provider_phone'] = "";
        }
        $postData['finalise'] = "";

        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/8', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $postData['back'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/edit/8', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/edit/9');
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    public function testShow()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', '/master_data/show');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSame('Stammdaten: Allgemein', $crawler->filter('h1')->text());

        $postData['next'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/show', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        unset($postData);

        $postData['back'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/show/2', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        unset($postData);

        $postData['close'] = "";
        /** @var Crawler $crawler */
        $crawler = $this->client->request('POST', '/master_data/show', $postData);
        $this->assertSame(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

//    public function testExport(){
//
//        if(headers_sent($f,$l))
//        {
//            if (!function_exists('getallheaders'))
//            {
//                function getallheaders()
//                {
//                    $headers = [];
//                    foreach ($_SERVER as $name => $value)
//                    {
//                        if (substr($name, 0, 5) == 'HTTP_')
//                        {
//                            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
//                        }
//                    }
//                    return $headers;
//                }
//            }
//
//            die('now detect line');
//        }
//
//        /** @var Crawler $crawler */
//        $crawler = $this->client->request('GET', '/master_data/export');
//
//        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
//
//        $this->assertTrue($this->client->getResponse()->isOk());
//
//    }
}
