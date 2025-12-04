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
     * Test parseUrls with multi-line string.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsWithMultiLineString(): void
    {
        $input = "https://webhook1.example.com\nhttps://webhook2.example.com\nhttps://webhook3.example.com";
        $result = $this->plugin->parseUrls($input);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals([
            'https://webhook1.example.com',
            'https://webhook2.example.com',
            'https://webhook3.example.com'
        ], $result);
    }

    /**
     * Test parseUrls with Windows-style line endings.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsWithWindowsLineEndings(): void
    {
        $input = "https://webhook1.example.com\r\nhttps://webhook2.example.com";
        $result = $this->plugin->parseUrls($input);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test parseUrls with empty lines.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsFiltersEmptyLines(): void
    {
        $input = "https://webhook1.example.com\n\n\nhttps://webhook2.example.com\n";
        $result = $this->plugin->parseUrls($input);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test parseUrls with whitespace.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsTrimsWhitespace(): void
    {
        $input = "  https://webhook1.example.com  \n  https://webhook2.example.com  ";
        $result = $this->plugin->parseUrls($input);

        $this->assertEquals([
            'https://webhook1.example.com',
            'https://webhook2.example.com'
        ], $result);
    }

    /**
     * Test parseUrls with single URL.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsWithSingleUrl(): void
    {
        $result = $this->plugin->parseUrls('https://webhook.example.com');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(['https://webhook.example.com'], $result);
    }

    /**
     * Test parseUrls with empty string.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsWithEmptyString(): void
    {
        $result = $this->plugin->parseUrls('');

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
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

        $decoded = json_decode($json, true);
        $this->assertEquals($result, $decoded);
    }

    /**
     * Test isWebhookEnabled returns false by default.
     *
     * @covers \LimeSurveyWebhook::isWebhookEnabled
     */
    public function testIsWebhookEnabledReturnsFalseByDefault(): void
    {
        $result = $this->plugin->isWebhookEnabled(123456);

        $this->assertFalse($result);
    }

    /**
     * Test getWebhookUrls returns empty array when no URL configured.
     *
     * @covers \LimeSurveyWebhook::getWebhookUrls
     */
    public function testGetWebhookUrlsReturnsEmptyArrayWhenNoUrlConfigured(): void
    {
        $result = $this->plugin->getWebhookUrls(123456);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getAuthToken returns null when no token configured.
     *
     * @covers \LimeSurveyWebhook::getAuthToken
     */
    public function testGetAuthTokenReturnsNullWhenNoTokenConfigured(): void
    {
        $result = $this->plugin->getAuthToken(123456);

        $this->assertNull($result);
    }

    /**
     * Test integration: parseUrls handles real-world input.
     *
     * @covers \LimeSurveyWebhook::parseUrls
     */
    public function testParseUrlsIntegrationWithRealWorldInput(): void
    {
        $input = "https://api.example.com/webhook\r\nhttps://backup.example.com/webhook\n\nhttps://third.example.com/webhook  ";
        $result = $this->plugin->parseUrls($input);

        $this->assertCount(3, $result);
        $this->assertEquals('https://api.example.com/webhook', $result[0]);
        $this->assertEquals('https://backup.example.com/webhook', $result[1]);
        $this->assertEquals('https://third.example.com/webhook', $result[2]);
    }
}
