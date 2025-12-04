<?php

/**
 * LimeSurveyWebhook Plugin
 *
 * Sends a JSON POST request (webhook) after each survey completion.
 * Includes both raw responses and human-readable formatted responses.
 *
 * @package    LimeSurveyWebhook
 * @version    2.2.0
 * @author     Stefan Verweij <stefan@evently.nl> (original)
 * @author     IrishWolf
 * @author     Alex Righetto
 * @author     Tom Riat
 * @copyright  2016 Evently
 * @license    GPL-3.0-or-later
 * @link       https://github.com/evently-nl/zesthook
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
    protected static $description = 'Webhook for LimeSurvey (JSON payload with pretty answers)';

    /**
     * @var string Plugin name
     */
    protected static $name = 'LimeSurveyWebhook';

    /**
     * @var int|null Current survey ID
     */
    protected $surveyId;

    /**
     * @var array Plugin settings configuration
     */
    protected $settings = [
        'sUrl' => [
            'type' => 'string',
            'label' => 'The default URL to send the webhook to:',
            'help' => 'To test get one from https://webhook.site'
        ],
        'sId' => [
            'type' => 'string',
            'default' => '000000',
            'label' => 'The ID of the surveys:',
            'help' => 'You can set multiple surveys separated by ","'
        ],
        'sAuthToken' => [
            'type' => 'string',
            'label' => 'API Authentication Token',
            'help' => 'Token sent in plain text (not encoded)'
        ],
        'sBug' => [
            'type' => 'select',
            'options' => [
                0 => 'No',
                1 => 'Yes'
            ],
            'default' => 0,
            'label' => 'Enable Debug Mode',
            'help' => 'Enable debug mode to see what data is transmitted.'
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
    }

    /**
     * Handle the afterSurveyComplete event.
     *
     * Checks if the completed survey is in the configured list
     * and triggers the webhook if it matches.
     *
     * @return void
     */
    public function afterSurveyComplete(): void
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        $hookSurveyId = $this->get('sId', null, null, $this->settings['sId']);

        $hookSurveyIdArray = $this->parseSurveyIds($hookSurveyId);

        if ($this->isSurveyEnabled($surveyId, $hookSurveyIdArray)) {
            $this->callWebhook('afterSurveyComplete');
        }
    }

    /**
     * Parse survey IDs from various formats into an array.
     *
     * Handles both array input and comma-separated string input.
     * Trims whitespace from each ID.
     *
     * @param array|string $surveyIds Survey IDs as array or comma-separated string
     * @return array Array of survey IDs
     */
    public function parseSurveyIds($surveyIds): array
    {
        if (is_array($surveyIds)) {
            return array_map('trim', $surveyIds);
        }

        return explode(',', preg_replace('/\s+/', '', (string) $surveyIds));
    }

    /**
     * Check if a survey ID is in the list of enabled surveys.
     *
     * @param int|string $surveyId The survey ID to check
     * @param array $enabledSurveyIds Array of enabled survey IDs
     * @return bool True if the survey is enabled
     */
    public function isSurveyEnabled($surveyId, array $enabledSurveyIds): bool
    {
        return in_array($surveyId, $enabledSurveyIds);
    }

    /**
     * Normalize and validate a submit date.
     *
     * Returns the current date/time if the provided date is empty or invalid.
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
     * Build and send the webhook payload.
     *
     * Collects survey response data, participant information,
     * and sends it to the configured webhook URL.
     *
     * @param string $eventName Name of the triggering event
     * @return void
     */
    private function callWebhook(string $eventName): void
    {
        $timeStart = microtime(true);
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        $responseId = $event->get('responseId');

        // Get raw response data
        $response = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);
        $submitDate = $this->normalizeSubmitDate($response['submitdate'] ?? null);

        // Get participant token
        $token = $response['token'] ?? null;

        // Get participant data if token exists
        $participant = $this->getParticipantData($surveyId, $token);

        $url = $this->get('sUrl', null, null, $this->settings['sUrl']);
        $auth = $this->get('sAuthToken', null, null, $this->settings['sAuthToken']);

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
            'full',     // Full question text as headers
            'label'     // Human-readable answer labels
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
        $hookResponse = $this->httpPost($url, $payload);

        $this->log($eventName . ' | JSON Payload: ' . $payload . ' | Response: ' . $hookResponse);
        $this->displayDebugInfo($url, $parameters, $hookResponse, $timeStart, $eventName);
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
     * @param string $url The webhook URL
     * @param array $parameters The payload parameters
     * @param string|false $hookResponse The webhook response
     * @param float $timeStart The start time for execution measurement
     * @param string $eventName The name of the triggering event
     * @return void
     */
    private function displayDebugInfo(
        string $url,
        array $parameters,
        $hookResponse,
        float $timeStart,
        string $eventName
    ): void {
        if ($this->get('sBug', null, null, $this->settings['sBug']) == 1) {
            $this->log($eventName);

            $executionTime = microtime(true) - $timeStart;

            $html = '<pre><br><br>---------------- DEBUG ----------------<br><br>';
            $html .= 'Payload:<br>' . htmlspecialchars(json_encode($parameters, JSON_PRETTY_PRINT));
            $html .= '<br><br>-----------------------------<br><br>';
            $html .= 'Hook URL: ' . htmlspecialchars($url) . '<br>';
            $html .= 'Response: ' . htmlspecialchars((string) $hookResponse) . '<br>';
            $html .= 'Execution time: ' . $executionTime . 's';
            $html .= '</pre>';

            $event = $this->getEvent();
            $event->getContent($this)->addContent($html);
        }
    }
}
