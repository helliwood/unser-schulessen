<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-06-06
 * Time: 14:05
 */

namespace App\Service;

use App\Entity\MasterData;
use App\Entity\MasterDataEntry;
use App\Entity\SchoolYear;
use App\Entity\User;
use App\Repository\MasterDataEntryRepository;
use App\Repository\MasterDataRepository;
use App\Repository\SchoolYearRepository;
use App\Service\MasterDataQuestions\BbQuestions;
use App\Validator\Constraints\OutdatedSurveyConstraint;
use App\Validator\Constraints\PotentialCurrentConstraint;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class MasterDataService
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * MasterDataService constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(Security $security, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * @return array[]|string[]
     */
    public static function getConfig(): array
    {
        $country   = \ucfirst(\strtolower($_ENV['APP_STATE_COUNTRY'])); // "Aa", "Bb" etc.
        $className = "App\\Service\\MasterDataQuestions\\{$country}Questions";
        if (\class_exists($className)) {
            return $className::$config;
        }

        // Default
        return BbQuestions::$config;
    }

    /**
     * @return int
     */
    public function getMaxSteps(): int
    {
        return \count(self::getConfig());
    }

    /**
     * @param int $step
     * @return string[]|null
     */
    public function getConfigByStep(int $step): ?array
    {
        return self::getConfig()[$step - 1] ?? null;
    }

    /**
     * @param int $step
     * @return string[]|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function getDataByStep(int $step): ?array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);
        /** @var MasterDataRepository $mdr */
        $mdr = $this->entityManager->getRepository(MasterData::class);

        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $result = [];
        $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'schoolYear' => $schoolYear]);
        if (! $masterData) {
            $schoolYear = $syr->findPrevious();
            $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'schoolYear' => $schoolYear]);
        }

        if ($masterData) {
            $config = $this->getConfigByStep($step);
            foreach ($masterData->getEntries($config['name']) as $data) {
                $result[$data->getKey()] = $data->getValue();
            }
        }

        return $result;
    }

    /**
     * @param int $step
     * @return string
     */
    public function getTemplateByStep(int $step): string
    {
        $config = $this->getConfigByStep($step);
        return $config['template'] ?? 'master_data/index/edit.html.twig';
    }

    /**
     * @param int $step
     * @param bool $disable_csrf
     * @param bool $readonly
     * @return FormInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getForm(int $step, bool $disable_csrf = false, bool $readonly = false): FormInterface
    {
        $config = $this->getConfigByStep($step);
        if (\is_null($config)) {
            throw new \Exception("Step not found!");
        }
        $form = $this->formFactory->createNamedBuilder($config['name'], FormType::class, null, [
            'attr' => ['readonly' => $readonly],
            'csrf_protection' => ! $disable_csrf
        ]);

        foreach ($config['items'] as $name => $item) {
            $options = ['label' => $item['label'], 'required' => $item['required']];
            if ($readonly) {
                $options['disabled'] = true;
            }
            if ($item['type'] === ChoiceType::class) {
                $options['choices'] = $item['choices'];
                if (isset($item['choiceType'])) {
                    if ($item['choiceType'] === 'checkbox') {
                        $options['multiple'] = true;
                        $options['expanded'] = true;
                    }
                } else {
                    $options['multiple'] = $item['multiple'] ?? false;
                    $options['expanded'] = $item['expanded'] ?? true;
                }
                $options['placeholder'] = '-- Bitte wählen --';
            }
            $options['help'] = '';
            if ($item['required']) {
                $options['constraints'] = [new NotBlank()];
                $options['help'] = 'Dieses Feld sollte nicht leer sein!';
            }
            if (isset($item['placeholder'])) {
                $options['placeholder'] = $item['placeholder'];
            }
            if (isset($item['attr'])) {
                $options['attr'] = $item['attr'];
            }
            if (isset($item['help'])) {
                $options['help'] .= ' ' . $item['help'];
            }
            if (isset($item['max_length'])) {
                $options['constraints'] = \array_merge(
                    [new Length(['max' => $item['max_length']])],
                    $options['constraints'] ?? []
                );
            }
            if (isset($item['range'])) {
                $options['constraints'] = \array_merge(
                    [new Range($item['range'])],
                    $options['constraints'] ?? []
                );
            }
            if ($item['type'] === TimeType::class) {
                $options['input'] = 'string';
                $options['with_seconds'] = false;
            }
            if ($item['type'] === DateType::class) {
                $options['input'] = 'string';
            }

            if (isset($item['validation'])) {
                foreach ($item['validation'] as $validation) {
                    foreach ($validation as $expression => $dependence) {
                        if (\method_exists($this, 'valid' . $expression)) {
                            $options['constraints'] = $this->{'valid' . $expression}($validation, $config, $options);
                        } elseif ($expression === 'LessThanOpeningHours') {
                            $options['constraints'] = $this->validLessThanOpeningHours();
                        } elseif ($expression === 'LessThanToday') {
                            $options['constraints'] = new OutdatedSurveyConstraint();
                        } elseif ($expression === 'PotentialCurrent') {
                            $options['constraints'] = new PotentialCurrentConstraint();
                        } else {
                            \dump('Die Methode $this->valid' . $expression . '() existiert nicht! Sie sollte mit ' . $dependence . 'verglichen werden.');
                            \dd($item);
                        }
                    }
                }
            }

            $form->add($name, $item['type'], $options);

            if (isset($item['transformer']) && $item['transformer'] === 'datetime') {
                //$form->get($name)->addModelTransformer(new ReversedTransformer(new DateTimeToStringTransformer()));
            }
            if ($item['type'] === DateType::class && isset($item['now'])) {
                $form->get($name)->setData((new \DateTime())->format('Y-m-d'));
            }
        }
        $form->setData($this->getDataByStep($step));
        return $form->getForm();
    }

//    $this->{'valid'. $expression}()


//    /**
//     * @throws \Doctrine\ORM\NonUniqueResultException
//     */
//    public function validTimeTillLastSurvey()
//    {
//        foreach ($this->getData()[5]['items'] as $field) {
//            if (is_int(strpos($field['question'], 'Die letzte Schülerbefragung war'))) {
//                $input = new \DateTime($field['value']);
//                $now = new \DateTime();
//
//                if ($input->format('d.m.Y') < $now->format('d.m.Y')) {
//                    return \array_merge(
//                        [new LessThan($now)],
//                        $options['constraints'] ?? []
//                    );
//                }
//            }
//        }
//    }

    /**
     * @param int $step
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function validLessThanOpeningHours(): array
    {
        $opening = $closing = 0;
        foreach ($this->getData()[0]['items'] as $field) {
            if (\is_int(\strpos($field['question'], 'opening_hours_from'))) {
                $von = \explode(':', $field['value']);
                $opening = $von[0] * 60 + $von[1];
            } elseif (\is_int(\strpos($field['question'], 'opening_hours_to'))) {
                $bis = \explode(':', $field['value']);
                $closing = $bis[0] * 60 + $bis[1];
            }
        }
        $diff = $opening - $closing;

        return \array_merge(
            [new GreaterThanOrEqual(-1 * $diff)],
            $options['constraints'] ?? []
        );
    }

    /**
     * @param     $validation
     * @param     $config
     * @param int $step
     * @param     $options
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function validGreaterThanSum($validation, $config, int $step, $options): array
    {
        $summe = 0;
        foreach ($this->getData()[$step]['items'] as $field) {
            if (\is_int(\strpos($field['question'], 'Essensteilnehmer Jahrgang')) && \is_int($field['value'])) {
                $summe += $field['value'];
            }
        }
        return \array_merge(
            [new GreaterThanOrEqual($summe)],
            $options['constraints'] ?? []
        );
    }

    /**
     * @param $validation
     * @param $config
     * @param $options
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function validLessThanOrEqual($validation, $config, $options): array
    {
        $expression = \key($validation);
        $dependence = $validation[$expression];
        if (\array_key_exists($dependence, $config['items'])) {
            return \array_merge(
                [
                    new LessThanOrEqual(
                        ['propertyPath' => 'parent.all[' . $dependence . '].data']
                    )
                ],
                $options['constraints'] ?? []
            );
        }
    }

    /**
     * @param $validation
     * @param $config
     * @param $options
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function validGreaterThanOrEqual($validation, $config, $options): array
    {
        $expression = \key($validation);
        $dependence = $validation[$expression];
        if (\array_key_exists($dependence, $config['items'])) {
            return \array_merge(
                [
                    new GreaterThanOrEqual(
                        ['propertyPath' => 'parent.all[' . $dependence . '].data']
                    )
                ],
                $options['constraints'] ?? []
            );
        }
    }

    /**
     * @param $validation
     * @param $config
     * @param $options
     * @return array
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function validGreaterThan($validation, $config, $options): array
    {
        $expression = \key($validation);
        $dependence = $validation[$expression];
        if (\array_key_exists($dependence, $config['items'])) {
            return \array_merge(
                [
                    new GreaterThan(
                        ['propertyPath' => 'parent.all[' . $dependence . '].data'],
                        $options['constraints'] ?? []
                    )
                ]
            );
        }
    }

    /**
     * @param int $step
     * @param string[] $formData
     * @throws \Exception
     */
    public function save(int $step, array $formData): void
    {
        $config = $this->getConfigByStep($step);
        if (\is_null($config)) {
            throw new \Exception("Step not found!");
        }
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);
        /** @var MasterDataRepository $mdr */
        $mdr = $this->entityManager->getRepository(MasterData::class);
        /** @var MasterDataEntryRepository $mdr */
        $mder = $this->entityManager->getRepository(MasterDataEntry::class);
        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'schoolYear' => $schoolYear]);
        if (! $masterData) {
            $masterData = new MasterData();
            $masterData->setSchool($user->getCurrentSchool());
            $masterData->setSchoolYear($schoolYear);
            $this->entityManager->persist($masterData);
            $this->entityManager->flush();
        }
        foreach (\array_keys($config['items']) as $key) {
            // false sollte gespeichert werden, deswegen zu !empty noch is_bool check
            $masterDataEntry = $mder->findByMasterDataAndStepAndKey($masterData, $config['name'], $key);
            if (\is_null($masterDataEntry)) {
                $masterDataEntry = new MasterDataEntry();
                $masterDataEntry->setMasterData($masterData);
                $masterDataEntry->setStep($config['name']);
                $masterDataEntry->setKey($key);
            }
            if (isset($formData[$key])) {
                $masterDataEntry->setValue($formData[$key]);
            } else {
                $masterDataEntry->setValue(null);
            }
            $this->entityManager->persist($masterDataEntry);
        }
        $this->entityManager->flush();
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function hasFinalisedMasterData(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);
        /** @var MasterDataRepository $mdr */
        $mdr = $this->entityManager->getRepository(MasterData::class);
        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'finalised' => true]);

        return ! \is_null($masterData);
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function hasUpdatedMasterData(): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);
        /** @var MasterDataRepository $mdr */
        $mdr = $this->entityManager->getRepository(MasterData::class);
        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'finalised' => true, 'schoolYear' => $schoolYear]);

        return ! \is_null($masterData);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function finalise(): void
    {
        for ($step = 1; $step < $this->getMaxSteps(); $step++) {
            if (\count($this->getDataByStep($step)) <= 0) {
                throw new \Exception('Schritt ' . $step . ' nicht vollständig ausgefüllt!');
            }
        }
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);
        /** @var MasterDataRepository $mdr */
        $mdr = $this->entityManager->getRepository(MasterData::class);
        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $masterData = $mdr->findOneBy(['school' => $user->getCurrentSchool(), 'schoolYear' => $schoolYear]);
        if (! $masterData) {
            throw new \Exception('MasterData not found!');
        }
        $masterData->setFinalised(true);
        $masterData->setFinalisedAt(new \DateTime());
        $masterData->setFinalisedBy($user);
        $this->entityManager->flush();
    }

    /**
     * @return string[]
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCategories(): array
    {
        $categories = [];
        foreach (self::getConfig() as $step => $config) {
            $valid = \count($this->getDataByStep($step + 1)) > 0;
            $categories[] = ['label' => $config['label'], 'valid' => $valid];
        }
        return $categories;
    }

    /**
     * @param string $name
     * @return string[]|null
     */
    public function getConfigByName(string $name): ?array
    {
        foreach (self::getConfig() as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param bool $getBlank
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingTraversableReturnTypeHintSpecification
     */
    public function getData(bool $getBlank = false): array
    {
        /** @var SchoolYearRepository $syr */
        $syr = $this->entityManager->getRepository(SchoolYear::class);

        $schoolYear = $syr->findCurrent();
        if (! $schoolYear) {
            $this->addMissingSchoolYear();
        }
        $result = [];
        foreach (self::getConfig() as $step => $config) {
            $data = $this->getDataByStep($step + 1);
            $items = [];
            foreach ($config['items'] as $key => $options) {
                if (! $getBlank && isset($data[$key]) && ! empty($data[$key])) {
                    $value = $data[$key];
                    switch ($options['type']) {
                        case ChoiceType::class:
                            //\var_dump($config['items'][$key]['label'], $val, \array_search($val, $config['items'][$key]['choices']));
                            if (\is_array($value)) {
                                $valueResult = [];
                                foreach ($value as $item) {
                                    $valueResult[] = \array_search($item, $config['items'][$key]['choices']);
                                }
                                $value = \implode(', ', $valueResult);
                            } else {
                                $value = \array_search($value, $config['items'][$key]['choices']);
                            }
                            break;
                        case TimeType::class:
                            $value = (new \DateTime($value))->format('H:i');
                            break;
                        case DateType::class:
                            $value = (new \DateTime($value))->format('d.m.Y');
                            break;
                        case CheckboxType::class:
                            $value = $value ? 'Ja' : 'Nein';
                            break;
                        case MoneyType::class:
                            $value = \number_format($value, 2, ',', '.') . ' €';
                            break;
                    }
                    $items[$key] = ['question' => $config['items'][$key]['label'], 'value' => $value];
                } else {
                    $items[$key] = ['question' => $config['items'][$key]['label']];
                }
            }
            $result[$step] = ['name' => $config['label'], 'items' => $items];
        }
        return $result;
    }

    public function addMissingSchoolYear(): SchoolYear
    {
        $now = new \DateTime();
        $currentMonth = (int)$now->format('m');
        $currentYear = (int)$now->format('Y');

        // Wenn das aktuelle Datum vor dem 01.09. liegt, verwende das vorherige Schuljahr
        if ($currentMonth < 9) {
            $year = $currentYear - 1;
            $nextYear = $currentYear;
        } else {
            $year = $currentYear;
            $nextYear = $currentYear + 1;
        }

        $schoolYear = (new SchoolYear())
            ->setYear($year)
            ->setLabel("$year/$nextYear")
            ->setPeriodBegin(new \DateTime("$year-09-01 00:00:00"))
            ->setPeriodEnd(new \DateTime("$nextYear-08-31 23:59:59"));

        $this->entityManager->persist($schoolYear);
        $this->entityManager->flush();
        return $schoolYear;
    }
}
