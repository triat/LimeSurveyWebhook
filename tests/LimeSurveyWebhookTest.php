<?php

namespace LimeSurveyWebhook\Tests;

use PHPUnit\Framework\TestCase;
use LimeSurveyWebhook;

/**
 * Unit tests for LimeSurveyWebhook plugin.
 *
 * @covers \LimeSurveyWebhook
 */
class LimeSurveyWebhookTest extends TestCase
{
    /**
     * @var LimeSurveyWebhook
     */
    private $plugin;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->plugin = new LimeSurveyWebhook(null, null);
    }

    /**
     * Test parseSurveyIds with comma-separated string.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     */
    public function testParseSurveyIdsWithCommaSeparatedString(): void
    {
        $result = $this->plugin->parseSurveyIds('123,456,789');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['123', '456', '789'], $result);
    }

    /**
     * Test parseSurveyIds with whitespace in string.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     */
    public function testParseSurveyIdsWithWhitespace(): void
    {
        $result = $this->plugin->parseSurveyIds('123, 456 , 789');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['123', '456', '789'], $result);
    }

    /**
     * Test parseSurveyIds with array input.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     */
    public function testParseSurveyIdsWithArray(): void
    {
        $result = $this->plugin->parseSurveyIds(['123', ' 456 ', '789']);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['123', '456', '789'], $result);
    }

    /**
     * Test parseSurveyIds with single ID.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     */
    public function testParseSurveyIdsWithSingleId(): void
    {
        $result = $this->plugin->parseSurveyIds('123456');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(['123456'], $result);
    }

    /**
     * Test parseSurveyIds with empty string.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     */
    public function testParseSurveyIdsWithEmptyString(): void
    {
        $result = $this->plugin->parseSurveyIds('');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([''], $result);
    }

    /**
     * Test isSurveyEnabled returns true for enabled survey.
     *
     * @covers \LimeSurveyWebhook::isSurveyEnabled
     */
    public function testIsSurveyEnabledReturnsTrueForEnabledSurvey(): void
    {
        $enabledSurveys = ['123', '456', '789'];

        $this->assertTrue($this->plugin->isSurveyEnabled('456', $enabledSurveys));
        $this->assertTrue($this->plugin->isSurveyEnabled(456, $enabledSurveys));
    }

    /**
     * Test isSurveyEnabled returns false for disabled survey.
     *
     * @covers \LimeSurveyWebhook::isSurveyEnabled
     */
    public function testIsSurveyEnabledReturnsFalseForDisabledSurvey(): void
    {
        $enabledSurveys = ['123', '456', '789'];

        $this->assertFalse($this->plugin->isSurveyEnabled('999', $enabledSurveys));
        $this->assertFalse($this->plugin->isSurveyEnabled(000, $enabledSurveys));
    }

    /**
     * Test isSurveyEnabled with empty array.
     *
     * @covers \LimeSurveyWebhook::isSurveyEnabled
     */
    public function testIsSurveyEnabledWithEmptyArray(): void
    {
        $this->assertFalse($this->plugin->isSurveyEnabled('123', []));
    }

    /**
     * Test normalizeSubmitDate with valid date.
     *
     * @covers \LimeSurveyWebhook::normalizeSubmitDate
     */
    public function testNormalizeSubmitDateWithValidDate(): void
    {
        $validDate = '2024-12-04 15:30:00';
        $result = $this->plugin->normalizeSubmitDate($validDate);

        $this->assertEquals($validDate, $result);
    }

    /**
     * Test normalizeSubmitDate with null value.
     *
     * @covers \LimeSurveyWebhook::normalizeSubmitDate
     */
    public function testNormalizeSubmitDateWithNull(): void
    {
        $result = $this->plugin->normalizeSubmitDate(null);

        // Should return current date/time
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * Test normalizeSubmitDate with empty string.
     *
     * @covers \LimeSurveyWebhook::normalizeSubmitDate
     */
    public function testNormalizeSubmitDateWithEmptyString(): void
    {
        $result = $this->plugin->normalizeSubmitDate('');

        // Should return current date/time
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * Test normalizeSubmitDate with legacy LimeSurvey default date.
     *
     * @covers \LimeSurveyWebhook::normalizeSubmitDate
     */
    public function testNormalizeSubmitDateWithLegacyDefaultDate(): void
    {
        $legacyDate = '1980-01-01 00:00:00';
        $result = $this->plugin->normalizeSubmitDate($legacyDate);

        // Should return current date/time, not the legacy date
        $this->assertNotEquals($legacyDate, $result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * Test buildPayload returns correct structure.
     *
     * @covers \LimeSurveyWebhook::buildPayload
     */
    public function testBuildPayloadReturnsCorrectStructure(): void
    {
        $result = $this->plugin->buildPayload(
            'afterSurveyComplete',
            123456,
            789,
            ['question1' => 'answer1'],
            ['Question 1' => 'Answer 1'],
            '2024-12-04 15:30:00',
            'abc123token',
            ['firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@example.com'],
            'api_secret_token'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('api_token', $result);
        $this->assertArrayHasKey('survey', $result);
        $this->assertArrayHasKey('event', $result);
        $this->assertArrayHasKey('respondId', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('response_pretty', $result);
        $this->assertArrayHasKey('submitDate', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('participant', $result);
    }

    /**
     * Test buildPayload contains correct values.
     *
     * @covers \LimeSurveyWebhook::buildPayload
     */
    public function testBuildPayloadContainsCorrectValues(): void
    {
        $response = ['question1' => 'answer1'];
        $responsePretty = ['Question 1' => 'Answer 1'];
        $participant = ['firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@example.com'];

        $result = $this->plugin->buildPayload(
            'afterSurveyComplete',
            123456,
            789,
            $response,
            $responsePretty,
            '2024-12-04 15:30:00',
            'abc123token',
            $participant,
            'api_secret_token'
        );

        $this->assertEquals('api_secret_token', $result['api_token']);
        $this->assertEquals(123456, $result['survey']);
        $this->assertEquals('afterSurveyComplete', $result['event']);
        $this->assertEquals(789, $result['respondId']);
        $this->assertEquals($response, $result['response']);
        $this->assertEquals($responsePretty, $result['response_pretty']);
        $this->assertEquals('2024-12-04 15:30:00', $result['submitDate']);
        $this->assertEquals('abc123token', $result['token']);
        $this->assertEquals($participant, $result['participant']);
    }

    /**
     * Test buildPayload handles null values.
     *
     * @covers \LimeSurveyWebhook::buildPayload
     */
    public function testBuildPayloadHandlesNullValues(): void
    {
        $result = $this->plugin->buildPayload(
            'afterSurveyComplete',
            123456,
            789,
            [],
            null,
            '2024-12-04 15:30:00',
            null,
            null,
            null
        );

        $this->assertNull($result['api_token']);
        $this->assertNull($result['response_pretty']);
        $this->assertNull($result['token']);
        $this->assertNull($result['participant']);
    }

    /**
     * Test buildPayload can be JSON encoded.
     *
     * @covers \LimeSurveyWebhook::buildPayload
     */
    public function testBuildPayloadCanBeJsonEncoded(): void
    {
        $result = $this->plugin->buildPayload(
            'afterSurveyComplete',
            123456,
            789,
            ['question1' => 'answer1'],
            ['Question 1' => 'Answer 1'],
            '2024-12-04 15:30:00',
            'abc123token',
            ['firstname' => 'John', 'lastname' => 'Doe'],
            'api_token'
        );

        $json = json_encode($result);

        $this->assertNotFalse($json);
        $this->assertJson($json);

        // Verify it can be decoded back
        $decoded = json_decode($json, true);
        $this->assertEquals($result, $decoded);
    }

    /**
     * Test integration: parseSurveyIds and isSurveyEnabled work together.
     *
     * @covers \LimeSurveyWebhook::parseSurveyIds
     * @covers \LimeSurveyWebhook::isSurveyEnabled
     */
    public function testIntegrationParseSurveyIdsAndIsSurveyEnabled(): void
    {
        $configuredIds = '123, 456, 789';
        $parsedIds = $this->plugin->parseSurveyIds($configuredIds);

        $this->assertTrue($this->plugin->isSurveyEnabled('456', $parsedIds));
        $this->assertFalse($this->plugin->isSurveyEnabled('999', $parsedIds));
    }
}
