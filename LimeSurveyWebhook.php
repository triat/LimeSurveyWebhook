<?php

/**
 * LimeSurveyWebhook Plugin
 *
 * Sends a JSON POST request (webhook) after each survey completion.
 * Supports per-survey webhook configuration with multiple URLs.
 *
 * @package    LimeSurveyWebhook
 * @version    3.0.0
 * @author     Stefan Verweij <stefan@evently.nl> (original)
 * @author     IrishWolf
 * @author     Alex Righetto
 * @author     Tom Riat
 * @copyright  2016 Evently
 * @license    GPL-3.0-or-later
 * @see        https://manual.limesurvey.org/Plugins
 */

class LimeSurveyWebhook extends PluginBase
{
    /**
     * @var string Storage type for plugin settings
     */
    protected $storage = 'DbStorage';

    /**
     * @var string Plugin description
     */
    protected static $description = 'Webhook for LimeSurvey - Configure webhooks per survey';

    /**
     * @var string Plugin name
     */
    protected static $name = 'LimeSurveyWebhook';

    /**
     * @var array Global plugin settings configuration
     */
    protected $settings = [
        'sDefaultUrl' => [
            'type' => 'string',
            'label' => 'Default Webhook URL',
            'help' => 'Default URL used when no survey-specific URL is configured. Test with https://webhook.site',
            'default' => ''
        ],
        'sDefaultAuthToken' => [
            'type' => 'string',
            'label' => 'Default API Authentication Token',
            'help' => 'Default token used when no survey-specific token is configured',
            'default' => ''
        ],
        'bDebug' => [
            'type' => 'select',
            'options' => [
                0 => 'No',
                1 => 'Yes'
            ],
            'default' => 0,
            'label' => 'Enable Debug Mode',
            'help' => 'Display transmitted data after survey completion (for all surveys)'
        ]
    ];

    /**
     * Initialize the plugin and subscribe to events.
     *
     * @return void
     */
    public function init(): void
    {
        $this->subscribe('afterSurveyComplete');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }

    /**
     * Add webhook settings to survey settings page.
     *
     * @return void
     */
    public function beforeSurveySettings(): void
    {
        $event = $this->getEvent();
        $surveyId = $event->get('survey');

        $event->set("surveysettings.{$this->id}", [
            'name' => get_class($this),
            'settings' => [
                'bEnabled' => [
                    'type' => 'boolean',
                    'label' => 'Enable webhook for this survey',
                    'current' => $this->get('bEnabled', 'Survey', $surveyId, false),
                    'help' => 'When enabled, a webhook will be triggered after each survey completion'
                ],
                'sWebhookUrls' => [
                    'type' => 'text',
                    'label' => 'Webhook URL(s)',
                    'current' => $this->get('sWebhookUrls', 'Survey', $surveyId, ''),
                    'help' => 'Enter one URL per line to send to multiple webhooks. Leave empty to use default URL.'
                ],
                'sAuthToken' => [
                    'type' => 'string',
                    'label' => 'API Authentication Token',
                    'current' => $this->get('sAuthToken', 'Survey', $surveyId, ''),
                    'help' => 'Leave empty to use default token from plugin settings'
                ]
            ]
        ]);
    }

    /**
     * Save survey-specific webhook settings.
     *
     * @return void
     */
    public function newSurveySettings(): void
    {
        $event = $this->getEvent();
        $surveyId = $event->get('survey');

        foreach ($event->get('settings') as $name => $value) {
            $this->set($name, $value, 'Survey', $surveyId);
        }
    }

    /**
     * Handle the afterSurveyComplete event.
     *
     * Checks if webhook is enabled for this survey and triggers it.
     *
     * @return void
     */
    public function afterSurveyComplete(): void
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        // Check if webhook is enabled for this survey
        if (!$this->isWebhookEnabled($surveyId)) {
            return;
        }

        $this->callWebhook($surveyId, 'afterSurveyComplete');
    }

    /**
     * Check if webhook is enabled for a specific survey.
     *
     * @param int|string $surveyId The survey ID
     * @return bool True if webhook is enabled
     */
    public function isWebhookEnabled($surveyId): bool
    {
        return (bool) $this->get('bEnabled', 'Survey', $surveyId, false);
    }

    /**
     * Get webhook URLs for a specific survey.
     *
     * Returns survey-specific URLs or falls back to default URL.
     *
     * @param int|string $surveyId The survey ID
     * @return array Array of webhook URLs
     */
    public function getWebhookUrls($surveyId): array
    {
        $surveyUrls = $this->get('sWebhookUrls', 'Survey', $surveyId, '');

        if (!empty($surveyUrls)) {
            return $this->parseUrls($surveyUrls);
        }

        // Fallback to default URL
        $defaultUrl = $this->get('sDefaultUrl', null, null, '');
        if (!empty($defaultUrl)) {
            return [$defaultUrl];
        }

        return [];
    }

    /**
     * Parse URLs from a multi-line string.
     *
     * @param string $urlString URLs separated by newlines
     * @return array Array of trimmed, non-empty URLs (re-indexed)
     */
    public function parseUrls(string $urlString): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $urlString);
        $urls = array_map('trim', $lines);
        return array_values(array_filter($urls, fn($url) => !empty($url)));
    }

    /**
     * Get authentication token for a specific survey.
     *
     * Returns survey-specific token or falls back to default.
     *
     * @param int|string $surveyId The survey ID
     * @return string|null The authentication token
     */
    public function getAuthToken($surveyId): ?string
    {
        $surveyToken = $this->get('sAuthToken', 'Survey', $surveyId, '');

        if (!empty($surveyToken)) {
            return $surveyToken;
        }

        // Fallback to default token
        $defaultToken = $this->get('sDefaultAuthToken', null, null, '');
        return !empty($defaultToken) ? $defaultToken : null;
    }

    /**
     * Normalize and validate a submit date.
     *
     * @param string|null $submitDate The submit date to normalize
     * @return string Normalized date in Y-m-d H:i:s format
     */
    public function normalizeSubmitDate(?string $submitDate): string
    {
        if (empty($submitDate) || $submitDate === '1980-01-01 00:00:00') {
            return date('Y-m-d H:i:s');
        }

        return $submitDate;
    }

    /**
     * Build the webhook payload array.
     *
     * @param string $eventName Name of the triggering event
     * @param int $surveyId The survey ID
     * @param int $responseId The response ID
     * @param array $response The raw response data
     * @param array|null $responsePretty The formatted response data
     * @param string $submitDate The submission date
     * @param string|null $token The participant token
     * @param array|null $participant The participant data
     * @param string|null $authToken The API authentication token
     * @return array The webhook payload
     */
    public function buildPayload(
        string $eventName,
        int $surveyId,
        int $responseId,
        array $response,
        ?array $responsePretty,
        string $submitDate,
        ?string $token,
        ?array $participant,
        ?string $authToken
    ): array {
        return [
            'api_token' => $authToken,
            'survey' => $surveyId,
            'event' => $eventName,
            'respondId' => $responseId,
            'response' => $response,
            'response_pretty' => $responsePretty,
            'submitDate' => $submitDate,
            'token' => $token,
            'participant' => $participant
        ];
    }

    /**
     * Build and send the webhook payload to all configured URLs.
     *
     * @param int|string $surveyId The survey ID
     * @param string $eventName Name of the triggering event
     * @return void
     */
    private function callWebhook($surveyId, string $eventName): void
    {
        $timeStart = microtime(true);
        $event = $this->getEvent();
        $responseId = $event->get('responseId');

        // Get webhook URLs for this survey
        $urls = $this->getWebhookUrls($surveyId);
        if (empty($urls)) {
            $this->log("No webhook URL configured for survey {$surveyId}");
            return;
        }

        // Get raw response data
        $response = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);
        $submitDate = $this->normalizeSubmitDate($response['submitdate'] ?? null);

        // Get participant token
        $token = $response['token'] ?? null;

        // Get participant data if token exists
        $participant = $this->getParticipantData($surveyId, $token);

        // Get auth token for this survey
        $auth = $this->getAuthToken($surveyId);

        // Import export helper for pretty responses
        Yii::import('application.helpers.export_helper');
        require_once APPPATH . 'helpers/export_helper.php';

        $language = $response['startlanguage'] ?? 'en';

        // Get human-readable responses with full question labels
        $responsePretty = responseExportData(
            $surveyId,
            [$responseId],
            $language,
            'json',
            'full',
            'label'
        );

        $parameters = $this->buildPayload(
            $eventName,
            (int) $surveyId,
            (int) $responseId,
            $response,
            $responsePretty,
            $submitDate,
            $token,
            $participant,
            $auth
        );

        $payload = json_encode($parameters);

        // Send to all configured URLs
        $responses = [];
        foreach ($urls as $url) {
            $hookResponse = $this->httpPost($url, $payload);
            $responses[$url] = $hookResponse;
            $this->log("{$eventName} | URL: {$url} | Response: " . ($hookResponse ?: 'FAILED'));
        }

        $this->displayDebugInfo($urls, $parameters, $responses, $timeStart, $eventName);
    }

    /**
     * Get participant data from the tokens table.
     *
     * @param int|string $surveyId The survey ID
     * @param string|null $token The participant token
     * @return array|null Participant data or null if not found
     */
    private function getParticipantData($surveyId, ?string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $query = "SELECT firstname, lastname, email FROM {{tokens_$surveyId}} WHERE token = :token LIMIT 1";
        $result = Yii::app()->db->createCommand($query)
            ->bindParam(':token', $token, PDO::PARAM_STR)
            ->queryRow();

        return $result ?: null;
    }

    /**
     * Send an HTTP POST request with JSON payload.
     *
     * @param string $url The webhook URL to send data to
     * @param string $jsonPayload The JSON-encoded payload
     * @return string|false Response body on success, false on failure
     */
    private function httpPost(string $url, string $jsonPayload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $output = curl_exec($ch);
        if ($output === false) {
            $this->log('cURL error: ' . curl_error($ch));
        }
        curl_close($ch);

        return $output;
    }

    /**
     * Display debug information if debug mode is enabled.
     *
     * @param array $urls The webhook URLs
     * @param array $parameters The payload parameters
     * @param array $responses The webhook responses keyed by URL
     * @param float $timeStart The start time for execution measurement
     * @param string $eventName The name of the triggering event
     * @return void
     */
    private function displayDebugInfo(
        array $urls,
        array $parameters,
        array $responses,
        float $timeStart,
        string $eventName
    ): void {
        if ($this->get('bDebug', null, null, 0) != 1) {
            return;
        }

        $this->log($eventName);
        $executionTime = microtime(true) - $timeStart;

        $html = '<pre><br><br>---------------- WEBHOOK DEBUG ----------------<br><br>';
        $html .= 'Event: ' . htmlspecialchars($eventName) . '<br><br>';
        $html .= 'Payload:<br>' . htmlspecialchars(json_encode($parameters, JSON_PRETTY_PRINT));
        $html .= '<br><br>-----------------------------<br><br>';
        $html .= 'Webhook URLs (' . count($urls) . '):<br>';

        foreach ($responses as $url => $response) {
            $html .= '<br>• ' . htmlspecialchars($url) . '<br>';
            $html .= '  Response: ' . htmlspecialchars((string) ($response ?: 'FAILED')) . '<br>';
        }

        $html .= '<br>-----------------------------<br>';
        $html .= 'Execution time: ' . round($executionTime, 4) . 's';
        $html .= '</pre>';

        $event = $this->getEvent();
        $event->getContent($this)->addContent($html);
    }
}
