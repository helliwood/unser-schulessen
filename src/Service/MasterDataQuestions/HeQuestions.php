<?php

namespace App\Service\MasterDataQuestions;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class HeQuestions
{
    public const TEMPLATE_ROOT = 'master_data/forms/he' ;

    /**
     * @var string[]
     */
    public static $config = [
        [
            'label' => 'Allgemein',
            'name' => 'general',
            'template' => self::TEMPLATE_ROOT . '/general.html.twig',
            'items' => [
                "potential_current" => [
                    'type' => IntegerType::class,
                    'label' => 'Aktuelle Anzahl Schülerinnen und Schüler',
                    'required' => true,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999],
                ],
                "potential_feature" => [
                    'type' => IntegerType::class,
                    'label' => 'Prognose der Anzahl von Schülerinnen und Schülern in 2 Jahren',
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999],
                ],
                "students_current" => [
                    'type' => IntegerType::class,
                    'label' => "Essensteilnehmende aktuell",
                    'help' => 'Angabe in Ziffern',
                    'required' => true,
                    'range' => ['min' => 0, 'max' => 9999],
                    'validation' => [['LessThanOrEqual' => 'potential_current'],]
                ],
                "class_level_1_4" => [
                    'type' => IntegerType::class,
                    'label' => "Essensteilnehmende Klassen 1-4",
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999]
                ],
                "class_level_5_10" => [
                    'type' => IntegerType::class,
                    'label' => "Essensteilnehmende Klassen 5-10",
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999]
                ],
                "class_level_11_13" => [
                    'type' => IntegerType::class,
                    'label' => "Essensteilnehmende Klassen 11-13",
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999]
                ],
                "teacher_current" => [
                    'type' => IntegerType::class,
                    'label' => "Essensteilnehmer Lehrkräfte:",
                    'required' => false,
                    'help' => 'Angabe in Ziffern',
                    'range' => ['min' => 0, 'max' => 9999]
                ],
                "teacher_obligatory" => [
                    'type' => ChoiceType::class,
                    'label' => "Ist die Teilnahme verpflichtend?",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                // Pausenzeiten
                "description" => [
                    'type' => TextareaType::class,
                    'label' => "Mittagspausen",
                    'required' => false,
                    'max_length' => 1000,
                    'help' => "Anzahl der Mittagspausen, Pausenzeiten, welche Jahrgangsstufen essen wann, zu beachtende Rahmenbedingungen, wie Busfahrzeiten"
                ],
                "minutes" => [
                    'type' => IntegerType::class,
                    'label' => "Pausenzeiten für das Mittagessen in Minuten",
                    'required' => true,
                    'help' => 'Angabe in Ziffern. Zeit, die mindestens jedem Tischgast vom Anstehen bis zur Geschirrrückgabe zur Verfügung steht.',
                    'range' => ['min' => 0, 'max' => 999],
                    'validation' => [['LessThanOpeningHours' => 'Pausenzeiten']]
                ],
                "opening_hours_from" => [
                    'type' => TimeType::class,
                    'placeholder' => '--',
                    'label' => "Öffnungszeiten der Speisenausgabe von",
                    'required' => false,
                    'transformer' => 'datetime'
                ],
                "opening_hours_to" => [
                    'type' => TimeType::class,
                    'placeholder' => '--',
                    'label' => "Öffnungszeiten der Speisenausgabe bis",
                    'required' => false,
                    'transformer' => 'datetime',
                    'validation' => [['GreaterThanOrEqual' => 'opening_hours_from']]
                ],
                "opening_hours_kiosk" => [
                    'type' => TextareaType::class,
                    'label' => "Öffnungszeiten des Kiosk/Imbiss von",
                    'required' => false,
                ],
                "bus_driving_times_earliest" => [
                    'type' => TimeType::class,
                    'placeholder' => '--',
                    'label' => "Busfahrzeiten nach Schulschluss frühestens",
                    'required' => false,
                    'transformer' => 'datetime',
                ],
                "bus_driving_times_latest" => [
                    'type' => TimeType::class,
                    'placeholder' => '--',
                    'label' => "Busfahrzeiten nach Schulschluss spätestens",
                    'required' => false,
                    'transformer' => 'datetime',
                    'validation' => [['GreaterThanOrEqual' => 'bus_driving_times_earliest']],
                ],
                // Ausstattung
                "dining_room" => [
                    'type' => ChoiceType::class,
                    'label' => "Für die Ausgabe und den Verzehr der Speisen steht ein gesonderter Raum zur Verfügung?",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "dining_only" => [
                    'type' => ChoiceType::class,
                    'label' => "Die Räumlichkeiten werden ausschließlich für die Verpflegung genutzt?",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "other_uses" => [
                    'type' => TextareaType::class,
                    'label' => "Sonstige Nutzungen",
                    'required' => false,
                    'max_length' => 1000,
                    'help' => "Wofür wird der Speiseraum noch genutzt? Was schränkt die Nutzung ein?"
                ],
                "places" => [
                    'type' => IntegerType::class,
                    'label' => "Anzahl der Plätze im Speiseraum",
                    'required' => true,
                    'range' => ['min' => 0, 'max' => 999],
                    'help' => "Sollte kein separater Raum zur Verfügung stehen bitte Null eintragen.",
                ],
                "features" => [
                    'type' => TextareaType::class,
                    'label' => "Der Speiseraum ist wie folgt ausgestattet",
                    'required' => false,
                    'max_length' => 1000,
                    'help' => "Möbelierung, Inventar, z.B. Wasserspender o.ä."
                ],
            ]
        ],
        [
            'label' => 'Verpflegungssystem',
            'name' => 'catering_system',
            'template' => self::TEMPLATE_ROOT . '/catering_system.html.twig',
            'items' => [
                "form_of_management_self" => [
                    'type' => CheckboxType::class,
                    'label' => "Eigenbewirtschaftung",
                    'required' => false,
                    'help' => "Eigenbewirtschaftung liegt vor, wenn die Schule, der Schulträger oder ein Mensaverein Arbeitgeber der Küchenkräfte ist, die das Schulessen zubereiten."
                ],
                "form_of_management_external" => [
                    'type' => CheckboxType::class,
                    'label' => "Fremdbewirtschaftung",
                    'required' => false,
                    'help' => "Wenn ein externer Dienstleister für das Schulessen zuständig ist, wird von Fremdbewirtschaftung gesprochen."
                ],
                "hot_meals" => [
                    'type' => CheckboxType::class,
                    'label' => "Warmverpflegung",
                    'required' => false,
                    'help' => "Bei Warmverpflegung (Cook&Hold) werden die Speisen in einer Zentralküche verzehrfertig produziert und heiß angeliefert."
                ],
                "cook_and_chill" => [
                    'type' => CheckboxType::class,
                    'label' => "Cook and Chill",
                    'required' => false,
                    'help' => "Beim Cook&Chill-System (Kühlkostsystem) verwendet die Schulküche fast ausschließlich fertige, gekühlte Speisen, die angeliefert und vor Ort regeneriert werden."
                ],
                "cook_and_freeze" => [
                    'type' => CheckboxType::class,
                    'label' => "Cook and Freeze",
                    'required' => false,
                    'help' => "Bei Cook&Freeze (Tiefkühlkostsystem) verwenden die Schulküchen ausschließlich oder überwiegend tiefgekühlte Speisen, die vor Ort regeneriert werden."
                ],
                "mixed_kitchen" => [
                    'type' => CheckboxType::class,
                    'label' => "Mischküche",
                    'required' => false,
                    'help' => "In der Mischküche werden die Speisen aus Lebensmitteln mit unterschiedlichem Fertigungsgrad frisch vor Ort zubereitet und serviert. (DGE)"
                ],
                "fresh" => [
                    'type' => CheckboxType::class,
                    'label' => "Frischzubereitung einzelner Menükomponenten, wie Salate oder Desserts",
                    'required' => false
                ],
            ]
        ],
        [
            'label' => 'Standards für die Mittagsverpflegung',
            'name' => 'standards_for_catering',
            'template' => self::TEMPLATE_ROOT . '/standards_for_catering.html.twig',
            'items' => [
                "number_menu_lines" => [
                    'type' => ChoiceType::class,
                    'label' => "Anzahl der Menülinien",
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'choices' => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]
                ],
                "vegetarian_menu" => [
                    'type' => CheckboxType::class,
                    'label' => "Täglich ein vegetarisches Menü",
                    'required' => false,
                ],
                "vegetarian_menu_line" => [
                    'type' => CheckboxType::class,
                    'label' => "Separate vegetarische Menülinie",
                    'required' => false,
                ],
                "menu_parts" => [
                    'type' => TextareaType::class,
                    'label' => "Zum Menü gehören",
                    'required' => false,
                    'max_length' => 1000,
                    'help' => "Was gehört laut Vertrag zum Mittagessen dazu? Bsp. Getränk, Dessert etc."
                ],
                "daily_salat" => [
                    'type' => CheckboxType::class,
                    'label' => "Täglich werden angeboten: Salat/Rohkost/Obst",
                    'required' => false
                ],
                "free_drink" => [
                    'type' => CheckboxType::class,
                    'label' => "kostenloses Getränk",
                    'required' => false
                ],
                "dessert" => [
                    'type' => CheckboxType::class,
                    'label' => "Dessert",
                    'required' => false
                ],
                "other_offers" => [
                    'type' => TextareaType::class,
                    'label' => "Sonstige Angebote",
                    'required' => false,
                    'max_length' => 1000,
                    'help' => "sonstige nicht tägliche Angebote, wie z.B. 2-mal wöchentlich Frischetheke"
                ],
                "dge" => [
                    'type' => ChoiceType::class,
                    'label' => "Im Speiseplan werden die Qualitätskriterien der DGE umgesetzt",
                    'required' => true,
                    'choices' => [
                        'Ja eine Menülinie' => 'yes_one',
                        'Ja alle Menülinien' => 'yes_all',
                        'Nein' => 'no',
                    ]
                ],
                "dge_checks" => [
                    'type' => ChoiceType::class,
                    'label' => "Der Caterer/Speisenanbieter ist verpflichtet Eigenkontrollen zur Umsetzung des DGE-Qualitätsstandards durchzuführen und zu dokumentieren",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "food_supplier_in_ag" => [
                    'type' => ChoiceType::class,
                    'label' => "Der Caterer/Speisenanbieter beteiligt sich aktiv an AG zur Verpflegungsqualität",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "other_quality_criterias" => [
                    'type' => TextareaType::class,
                    'label' => "Sonstige Qualitätskriterien sind vereinbart",
                    'required' => false,
                    'max_length' => 3000,
                    'help' => "z.B. Bioanteil, regionale Produkte, Fairtrade"
                ],
                "food_counter" => [
                    'type' => ChoiceType::class,
                    'choiceType' => "checkbox",
                    'label' => "Ausgabe",
                    'required' => false,
                    'choices' => [
                        'Schüsselsystem/Tischgemeinschaften' => 'table_fellowships',
                        'Thekenausgabe/Tellergericht' => 'counter_output',
                        'Selbstbedienungsbuffet/Teilbuffet' => 'free_flow',
                        'Teilbuffet' => 'partial_buffet',
                    ],
//                    'help' => "Caféteria-Linie: Die Tischgäste bestücken ihr Tablett entlang einer Theke z.T. selbst (z.B. mit den vorportionierten kalten Speisen (eventuell auch mit Getränken)), warme Hauptgerichte werden vom Personal ausgegeben. Free-Flow: die Tischgäste können sich frei an verschiedenen Ausgabestationen ihre Mittagsmahlzeit zusammenstellen"
                ],
                "additional_self_service" => [
                    'type' => ChoiceType::class,
                    'label' => "Zusätzliche Selbstbedienung",
                    'required' => false,
                    'choiceType' => "checkbox",
                    'choices' => [
                        'Salat-/Rohkost-/Obsttheke' => 'salat',
                        'Dessert' => 'dessert',
                        'einzelne Menükomponenten' => 'menu_parts',
                    ],
                ],
                "warm_keeping_period" => [
                    'type' => TimeType::class,
                    'placeholder' => '--',
                    'label' => "Die maximale Warmhaltezeit für das Mittagessen beträgt (in Stunden)",
                    'required' => true,
                    'transformer' => 'datetime',
                    'help' => "Zur Warmhaltezeit gehören die Standzeiten im Herstellungsbetrieb, die Transportzeiten von der Küche zur Schule und die Standzeiten in der Schule bis zum Ende der Speisenausgabe. Gibt es Vereinbarungen dazu im Leistungsverzeichnis?"
                ],
                "communication" => [
                    'type' => TextareaType::class,
                    'label' => "Kommunikation zwischen Anbieter und Träger/Schule/Schüler/Eltern",
                    'required' => false,
                    'max_length' => 3000,
                    'help' => 'Wie wird ein Austausch gewährleistet? Z.B. über Umfragen, App, Mailkontakt usw..',
                ],
                // Imbissangebot
                "imbiss" => [
                    'type' => ChoiceType::class,
                    'label' => "Zusätzlich zur Mittagsverpflegung gibt es ein Imbiss-/Kioskangebot",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "imbiss_offer" => [
                    'type' => TextareaType::class,
                    'label' => "Das Angebot des Imbiss/Kiosk umfasst",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "imbiss_dge" => [
                    'type' => ChoiceType::class,
                    'label' => "Das Angebot des Imbiss/Kiosk entspricht dem DGE-Standard für Snackangebote bzw. für die Zwischenverpflegung",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ],
                    'help' => "Ist hier in der Leistungsbeschreibung eine entsprechende Vereinbarung getroffen?"
                ],
            ]
        ],
        [
            'label' => 'Bestellsystem',
            'name' => 'ordering_system',
            'template' => self::TEMPLATE_ROOT . '/ordering_system.html.twig',
            'items' => [
                "period" => [
                    'type' => TextareaType::class,
                    'label' => "Angaben zum Bestellzeitraum und Bestellzeitpunkt",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "ordering_type" => [
                    'type' => ChoiceType::class,
                    'choiceType' => "checkbox",
                    'label' => "Die Bestellung erfolgt",
                    'required' => false,
                    'choices' => [
                        'über digitales Kundenkonto' => 'internet_in_writing',
                        'schriftlich über Bestellschein' => 'by_paper',
                        'mündlich direkt bei der Ausgabe' => 'verbally',
                        'über Bestellterminal' => 'magnetic_card',
                    ]
                ],
                "ordering_cancellation" => [
                    'type' => TextareaType::class,
                    'label' => "Bestellungen und Stornierungen erfolgen per",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "ordering_email" => [
                    'type' => TextareaType::class,
                    'label' => "(Mail-)Kontakt für Bestellungen",
                    'required' => false,
                    'max_length' => 1000,
                ],
            ]
        ],
        [
            'label' => 'Abrechnungssystem',
            'name' => 'accounting_system',
            'template' => self::TEMPLATE_ROOT . '/accounting_system.html.twig',
            'items' => [
                "by" => [
                    'type' => ChoiceType::class,
                    'label' => "Abrechnung erfolgt",
                    'required' => false,
                    'choiceType' => "checkbox",
                    'choices' => [
                        'bar' => 'cash',
                        'Wertmarken mit Geldwert' => 'tokens',
                        'Termingebundene Essensmarken' => 'scheduled_meal_vouchers',
                        'Guthabenkarte mit Bargeldaufladung' => 'prepaid_card_with_cash_loading',
                        'Guthabenkarte mit bargeldloser Aufladung' => 'prepaid_card_with_cashless_loading',
                        'Geldkarte der Bank mit Aufladung per Überweisung' => 'bank_card',
                        'per Rechnung und Lastschriftverfahren' => 'invoice',
                        'per Rechnung und Überweisung' => 'transfer',
                    ]
                ],
            ]
        ],
        [
            'label' => 'Beschwerdemanagement',
            'name' => 'complaint_management',
            'template' => self::TEMPLATE_ROOT . '/complaint_management.html.twig',
            'items' => [
                "recipient" => [
                    'type' => TextareaType::class,
                    'label' => "Tagesaktuelle Beschwerden zum Schulessen selbst oder dem Service sind zu richten an",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "catering_officer" => [
                    'type' => ChoiceType::class,
                    'label' => "Eine verpflegungsbeauftragte Person von Seiten der Schule ist benannt",
                    'required' => true,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "catering_officer_contact" => [
                    'type' => TextareaType::class,
                    'label' => "(Mail-)Kontakt der verpflegungsbeauftragten Person in der Schule",
                    'required' => false,
                ],
                "mensa_circle" => [
                    'type' => ChoiceType::class,
                    'label' => "Es gibt einen Mensakreis",
                    'required' => true,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "student_survey" => [
                    'type' => ChoiceType::class,
                    'label' => "Der Caterer/Speisenanbieter ist verpflichtet mindestens einmal jährlich eine Schülerbefragung zur Zufriedenheit mit dem Schulessen durchzuführen.",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ]
                ],
                "last_student_survey" => [
                    'type' => DateType::class,
                    'label' => "Die letzte Schülerbefragung war",
                    'required' => false,
                    'now' => true,
                    'validation' => [['LessThanToday' => '']]
                ],
            ]
        ],
        [
            'label' => 'Preise und Zuschüsse',
            'name' => 'prices_and_subsidies',
            'template' => self::TEMPLATE_ROOT . '/prices_and_subsidies.html.twig',
            'items' => [
                "uniform_selling_price" => [
                    'type' => ChoiceType::class,
                    'label' => "Für ein Mittagessen gibt es einen einheitlichen Abgabepreis für ein Gericht bzw. frei wählbare Menükomponenten",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ],
                    'help' => "Zahlen alle Schüler den gleichen Preis? Ein evtl. höherer Betrag bei Lehrern wird hier nicht erfasst!"
                ],
                "price_from" => [
                    'type' => MoneyType::class,
                    'label' => "Für ein Mittagessen gibt es eine Preisspanne von",
                    'required' => true,
                    'help' => "EE,CC",
                    'validation' => [['LessThanOrEqual' => 'price_to']]
                ],
                "price_to" => [
                    'type' => MoneyType::class,
                    'label' => "Für ein Mittagessen gibt es eine Preisspanne bis",
                    'required' => true,
                    'help' => "EE,CC"
                ],
                "school_subsidies" => [
                    'type' => ChoiceType::class,
                    'label' => "Für das Mittagessen werden von Seiten des Schulträgers direkte Zuschüsse an den Essenanbieter gezahlt",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ],
                    'help' => "Ist im Vertrag mit dem Caterer/Speisenanbieter ein direkter Zuschuss des Schulträgers zum Mittagessen vereinbart?",
                ],
                "school_subsidies_amount" => [
                    'type' => MoneyType::class,
                    'label' => "Der Zuschuss beträgt pro Portion",
                    'required' => false,
                    'help' => "EE,CC",
                    'validation' => [['LessThanOrEqual' => 'price_from']],
                ],
                "school_indirect_subsidies" => [
                    'type' => ChoiceType::class,
                    'label' => "Für das Mittagessen werden von Seiten des Schulträgers indirekte Zuschüsse aufgewandt.",
                    'required' => false,
                    'choices' => [
                        'Ja' => 'yes',
                        'Nein' => 'no',
                    ],
                    'help' => "Indirekte Zuschüsse des Schulträgers können die Übernahme von Energie- und Wasserkosten sein, die kostenlose Bereitstellung von Küchengeräten, die Finanzierung von Personal."
                ],
                "bund_subsidies" => [
                    'type' => TextareaType::class,
                    'label' => "Die Kosten für das Mittagessen können beim Vorliegen der entsprechenden Voraussetzungen über das Bildungs- und Teilhabepaket des Bundes übernommen werden. Die Antragstellung erfolgt:",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "other_subsidies" => [
                    'type' => TextareaType::class,
                    'label' => "Sonstige Zuschüsse, die durch individuelle Anträge erfolgen",
                    'required' => false,
                    'max_length' => 1000,
                ],
            ]
        ],
        [
            'label' => 'Anbieter und Vertragsdauer',
            'name' => 'term_of_contract',
            'template' => self::TEMPLATE_ROOT . '/term_of_contract.html.twig',
            'items' => [
                "start" => [
                    'type' => DateType::class,
                    'label' => "Der aktuelle Vertrag für die Erbringung der Leistungen zur  Schulverpflegung beginnt am",
                    'required' => false,
                ],
                "end" => [
                    'type' => DateType::class,
                    'label' => "und endet am",
                    'required' => false,
                    'validation' => [['GreaterThan' => 'start']]
                ],
                "catering_provider" => [
                    'type' => TextareaType::class,
                    'label' => "Caterer/Speisenanbieter",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "catering_provider_mail" => [
                    'type' => TextareaType::class,
                    'label' => "(Mail-)Kontakt",
                    'required' => false,
                    'max_length' => 1000,
                ],
                "catering_provider_phone" => [
                    'type' => TextareaType::class,
                    'label' => "Telefonnummer",
                    'required' => false,
                    'max_length' => 1000,
                ],
            ]
        ]
    ];
}
