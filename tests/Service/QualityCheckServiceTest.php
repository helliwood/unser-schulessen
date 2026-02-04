<?php

namespace App\Tests\Service;

use App\Entity\QualityCheck\Answer;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Entity\QualityCheck\Result;
use App\Service\QualityCheckService;
use App\Tests\Controller\AbstractTestController;

class QualityCheckServiceTest extends AbstractTestController
{
    private QualityCheckService $qualityCheckService;

    public function setUp(): void
    {
        self::bootKernel();
        $this->qualityCheckService = static::getContainer()->get(QualityCheckService::class);
    }

    public function testGetFlagDefinitions()
    {
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        
        $this->assertIsArray($flagDefinitions);
        $this->assertArrayHasKey('sustainable', $flagDefinitions);
        
        // Test structure of flag definitions
        foreach ($flagDefinitions as $flag => $definition) {
            $this->assertIsString($flag);
            $this->assertIsArray($definition);
            $this->assertArrayHasKey('description', $definition);
            $this->assertArrayHasKey('icon', $definition);
            $this->assertArrayHasKey('color', $definition);
        }
    }

    public function testGetFlagDefinitionsContainsRegionalFlags()
    {
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        
        // Test that regional flags are merged based on APP_STATE_COUNTRY
        $stateCountry = $_ENV['APP_STATE_COUNTRY'] ?? 'bb';
        
        if ($stateCountry === 'by' && class_exists('App\Service\FlagDefinitions\ByFlags')) {
            $this->assertArrayHasKey('guidelineCheck', $flagDefinitions);
        } else {
            // For other states (bb, he, sl, rp) - just verify base flags exist
            $this->assertArrayHasKey('sustainable', $flagDefinitions);
        }
    }

    public function testGetFlagIcon()
    {
        $sustainableIcon = $this->qualityCheckService->getFlagIcon('sustainable');
        $this->assertSame('fas fa-leaf', $sustainableIcon);
        
        $nonExistentIcon = $this->qualityCheckService->getFlagIcon('nonexistent');
        $this->assertNull($nonExistentIcon);
    }

    public function testGetFlagDescription()
    {
        $sustainableDescription = $this->qualityCheckService->getFlagDescription('sustainable');
        $this->assertSame('Nachhaltigkeitskriterium', $sustainableDescription);
        
        $nonExistentDescription = $this->qualityCheckService->getFlagDescription('nonexistent');
        $this->assertNull($nonExistentDescription);
    }

    public function testGetFlagType()
    {
        $sustainableType = $this->qualityCheckService->getFlagType('sustainable');
        $this->assertNull($sustainableType); // sustainable doesn't have a type
        
        $nonExistentType = $this->qualityCheckService->getFlagType('nonexistent');
        $this->assertNull($nonExistentType);
    }

    public function testGetFlagIcons()
    {
        $flagIcons = $this->qualityCheckService->getFlagIcons();
        
        $this->assertIsArray($flagIcons);
        $this->assertArrayHasKey('sustainable', $flagIcons);
        
        $this->assertSame('fas fa-leaf', $flagIcons['sustainable']);
    }

    public function testFlagDefinitionsStructure()
    {
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        
        // Test sustainable flag structure
        $this->assertArrayHasKey('sustainable', $flagDefinitions);
        $sustainable = $flagDefinitions['sustainable'];
        $this->assertSame('Nachhaltigkeitskriterium', $sustainable['description']);
        $this->assertSame('fas fa-leaf', $sustainable['icon']);
        $this->assertSame('#006600', $sustainable['color']);
    }

    public function testMiniCheckAsClassAttribute()
    {
        // Test that miniCheck is treated as a class attribute, not a flag
        $question = new Question();
        
        // Test default value
        $this->assertFalse($question->isMiniCheck());
        
        // Test setting miniCheck
        $question->setMiniCheck(true);
        $this->assertTrue($question->isMiniCheck());
        
        // Test that miniCheck is not in flags
        $this->assertEmpty($question->getFlags());
        
        // Test that miniCheck is not treated as a flag
        $this->assertFalse($question->hasFlag('miniCheck'));
        $this->assertFalse($question->isFlagEqual('miniCheck', true));
        
        // Test that miniCheck is not in flag definitions
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        $this->assertArrayNotHasKey('miniCheck', $flagDefinitions);
        
        // Test that miniCheck is not affected by flag filtering
        $this->assertTrue($question->matchesFlags([])); // No flags = include all
        $this->assertTrue($question->matchesFlags(['sustainable' => true])); // Only sustainable flag
        $this->assertTrue($question->matchesFlags(['curry' => true])); // Only curry flag
    }

    public function testShouldIncludeAnswerWithSustainableFlag()
    {
        // Create test entities
        $category = new Category();
        $question = new Question();
        $question->setCategory($category);
        $question->setSustainable(true);
        
        $answer = new Answer();
        $answer->setQuestion($question);
        $answer->setAnswer(Answer::ANSWER_TRUE);
        
        // Use reflection to test private method
        $reflectionClass = new \ReflectionClass($this->qualityCheckService);
        $method = $reflectionClass->getMethod('shouldIncludeAnswer');
        $method->setAccessible(true);
        
        // Test sustainable filtering
        $this->assertTrue($method->invoke($this->qualityCheckService, $answer, true, []));
        $this->assertTrue($method->invoke($this->qualityCheckService, $answer, false, []));
        
        // Set question as non-sustainable
        $question->setSustainable(false);
        $this->assertFalse($method->invoke($this->qualityCheckService, $answer, true, []));
        $this->assertTrue($method->invoke($this->qualityCheckService, $answer, false, []));
    }

    public function testShouldIncludeAnswerWithCustomFlags()
    {
        // Create test entities
        $category = new Category();
        $question = new Question();
        $question->setCategory($category);
        $question->setFlag('customFlag', 'value');
        
        $answer = new Answer();
        $answer->setQuestion($question);
        $answer->setAnswer(Answer::ANSWER_TRUE);
        
        // Use reflection to test private method
        $reflectionClass = new \ReflectionClass($this->qualityCheckService);
        $method = $reflectionClass->getMethod('shouldIncludeAnswer');
        $method->setAccessible(true);
        
        // Test flag filtering (miniCheck is not a flag anymore)
        $this->assertTrue($method->invoke($this->qualityCheckService, $answer, false, ['customFlag' => 'value']));
        $this->assertFalse($method->invoke($this->qualityCheckService, $answer, false, ['customFlag' => 'different']));
    }

    public function testCalculateAnswer()
    {
        // This test requires actual database data
        // For now, we'll test the method exists and returns expected types
        $result = $this->qualityCheckService->calculateAnswer(1, Answer::ANSWER_TRUE);
        $this->assertIsString($result);
    }

    public function testConstantFlagDefinitions()
    {
        // Test that the constant FLAG_DEFINITIONS contains expected flags
        $constants = (new \ReflectionClass($this->qualityCheckService))->getConstants();
        $this->assertArrayHasKey('FLAG_DEFINITIONS', $constants);
        
        $flagDefinitions = $constants['FLAG_DEFINITIONS'];
        $this->assertArrayHasKey('sustainable', $flagDefinitions);
        // miniCheck is no longer a flag, it's a separate class attribute
    }

    public function testFlagDefinitionsImmutability()
    {
        // Test that calling getFlagDefinitions multiple times returns consistent results
        $definitions1 = $this->qualityCheckService->getFlagDefinitions();
        $definitions2 = $this->qualityCheckService->getFlagDefinitions();
        
        $this->assertSame($definitions1, $definitions2);
    }

    public function testRegionalFlagOverride()
    {
        // Test that regional flags are properly merged and don't override core flags
        $flagDefinitions = $this->qualityCheckService->getFlagDefinitions();
        
        // Core flags should always be present
        $this->assertArrayHasKey('sustainable', $flagDefinitions);
        // miniCheck is no longer a flag, it's a separate class attribute
        
        // Regional flags should be added if they exist
        if (array_key_exists('guidelineCheck', $flagDefinitions)) {
            $this->assertIsArray($flagDefinitions['guidelineCheck']);
            $this->assertArrayHasKey('description', $flagDefinitions['guidelineCheck']);
            $this->assertArrayHasKey('icon', $flagDefinitions['guidelineCheck']);
            $this->assertArrayHasKey('color', $flagDefinitions['guidelineCheck']);
        }
    }
} 