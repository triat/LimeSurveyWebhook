<?php

/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment with mock classes for LimeSurvey dependencies.
 */

// Mock LimeSurvey PluginBase class
if (!class_exists('PluginBase')) {
    abstract class PluginBase
    {
        protected $storage;
        protected static $description;
        protected static $name;
        protected $settings = [];
        protected $event;
        protected $pluginManager;

        public function get(string $key, $survey = null, $language = null, $default = null)
        {
            return $default;
        }

        public function subscribe(string $event): void
        {
            // Mock implementation
        }

        public function getEvent()
        {
            return $this->event;
        }

        public function setEvent($event): void
        {
            $this->event = $event;
        }

        public function log(string $message): void
        {
            // Mock implementation - could store for testing
        }
    }
}

// Mock Yii class
if (!class_exists('Yii')) {
    class Yii
    {
        private static $app;

        public static function app()
        {
            if (self::$app === null) {
                self::$app = new class {
                    public $db;

                    public function __construct()
                    {
                        $this->db = new class {
                            public function createCommand($query)
                            {
                                return new class {
                                    public function bindParam($param, &$value, $type)
                                    {
                                        return $this;
                                    }

                                    public function queryRow()
                                    {
                                        return null;
                                    }
                                };
                            }
                        };
                    }
                };
            }
            return self::$app;
        }

        public static function import(string $alias): void
        {
            // Mock implementation
        }
    }
}

// Mock PDO constant if not defined
if (!defined('PDO::PARAM_STR')) {
    define('PDO_PARAM_STR', 2);
    class PDO
    {
        const PARAM_STR = 2;
    }
}

// Define APPPATH if not defined
if (!defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/mocks/');
}

// Create mock export helper
if (!file_exists(__DIR__ . '/mocks/helpers')) {
    @mkdir(__DIR__ . '/mocks/helpers', 0777, true);
}

$exportHelperContent = '<?php
function responseExportData($surveyId, $responseIds, $language, $format, $headerType, $answerType)
{
    return [
        "responses" => [
            [
                "Question 1" => "Answer 1",
                "Question 2" => "Answer 2"
            ]
        ]
    ];
}
';

file_put_contents(__DIR__ . '/mocks/helpers/export_helper.php', $exportHelperContent);

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/LimeSurveyWebhook.php';

