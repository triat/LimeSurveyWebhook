# AGENTS.md - LimeSurveyWebhook Project Guide

## 📋 Project Information

### Description
**LimeSurveyWebhook** is a LimeSurvey plugin that automatically sends a POST request (webhook) containing response data after each survey completion.

### Metadata
| Item | Value |
|------|-------|
| **Name** | LimeSurveyWebhook |
| **Version** | 2.2.0 |
| **Type** | LimeSurvey Plugin |
| **License** | GPL v3 |
| **Compatibility** | LimeSurvey 6+ |
| **Authors** | Stefan Verweij (original), IrishWolf, Alex Righetto, Tom Riat |

### Project Structure
```
LimeSurveyWebhook/
├── LimeSurveyWebhook.php   # Main plugin class
├── config.xml              # Plugin configuration and metadata
├── composer.json           # Dependency management (PHPUnit)
├── phpunit.xml             # PHPUnit configuration
├── LICENSE                 # GPL v3 License
├── README.md               # User documentation
├── AGENTS.md               # This file - Development guide
└── tests/                  # Unit tests
    ├── bootstrap.php       # Test bootstrap with mocks
    └── LimeSurveyWebhookTest.php  # Plugin unit tests
```

### Main Features
- **JSON Webhook**: Automatic data sending after survey completion
- **Multi-survey**: Support for multiple survey IDs (comma-separated)
- **Authentication**: Configurable API token
- **Debug Mode**: Display transmitted data for debugging
- **Participant Data**: Retrieval of participant's first name, last name, and email
- **Formatted Responses**: Sending both raw AND readable responses (with labels)

### Technical Architecture
- **Framework**: Yii (integrated into LimeSurvey)
- **Language**: PHP 7.4+
- **Storage**: DbStorage (LimeSurvey database)
- **Communication**: cURL for HTTP POST requests
- **Format**: JSON

### Triggered Event
The plugin subscribes to LimeSurvey's `afterSurveyComplete` event.

### JSON Payload Sent
```json
{
    "api_token": "string",
    "survey": "integer",
    "event": "afterSurveyComplete",
    "respondId": "integer",
    "response": {},
    "response_pretty": {},
    "submitDate": "datetime",
    "token": "string|null",
    "participant": {
        "firstname": "string",
        "lastname": "string",
        "email": "string"
    }
}
```

---

## 🎯 PHP Best Practices

### 1. PSR Standards

#### PSR-1: Basic Coding Standard
- Files MUST use only `<?php` or `<?=` tags
- Files MUST use UTF-8 encoding without BOM
- A file SHOULD declare symbols OR cause side effects, but not both
- Class names MUST be in `StudlyCaps` (PascalCase)
- Class constants MUST be in UPPERCASE with underscores

```php
// ✅ Correct
class LimeSurveyWebhook extends PluginBase
{
    const VERSION = '2.2.0';
    protected $storage = 'DbStorage';
}

// ❌ Incorrect
class limesurvey_webhook extends PluginBase
{
    const version = '2.2.0';
}
```

#### PSR-4: Autoloading
- Follow namespace structure for autoloading
- One namespace per file

#### PSR-12: Extended Coding Style
- **Indentation**: 4 spaces (no tabs)
- **Line length**: Maximum 120 characters (recommended: 80)
- **Braces**: Same line for control structures, new line for classes/methods

```php
// ✅ Correct
class LimeSurveyWebhook extends PluginBase
{
    public function init()
    {
        if ($condition) {
            // code
        }
    }
}
```

### 2. Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `LimeSurveyWebhook` |
| Methods | camelCase | `afterSurveyComplete()` |
| Properties | camelCase | `$surveyId` |
| Constants | UPPER_SNAKE | `SURVEY_COMPLETED` |
| Variables | camelCase | `$hookSurveyId` |

### 3. Strict Typing

```php
<?php
declare(strict_types=1);

class LimeSurveyWebhook extends PluginBase
{
    protected int $surveyId;
    
    public function getSurveyId(): int
    {
        return $this->surveyId;
    }
    
    private function httpPost(string $url, string $jsonPayload): string|false
    {
        // ...
    }
}
```

### 4. PHPDoc Documentation

```php
/**
 * Sends survey data to the configured webhook.
 *
 * @param string $comment Description of the triggering event
 * @return void
 * @throws \Exception If the webhook URL is not configured
 */
private function callWebhook(string $comment): void
{
    // ...
}
```

### 5. Error Handling

```php
// ✅ Correct - Explicit error handling
$output = curl_exec($ch);
if ($output === false) {
    $this->log("cURL error: " . curl_error($ch));
}

// ✅ Use exceptions for critical errors
if (empty($url)) {
    throw new InvalidArgumentException('Webhook URL is required');
}
```

### 6. Security

#### Input Validation
```php
// ✅ Always validate and sanitize inputs
$surveyId = (int) $event->get('surveyId');
$token = htmlspecialchars($response['token'] ?? '', ENT_QUOTES, 'UTF-8');
```

#### Prepared SQL Queries
```php
// ✅ Correct - Prepared query
$query = "SELECT firstname FROM {{tokens_$surveyId}} WHERE token = :token LIMIT 1";
$participant = Yii::app()->db->createCommand($query)
    ->bindParam(":token", $token, PDO::PARAM_STR)
    ->queryRow();

// ❌ Incorrect - SQL injection possible
$query = "SELECT * FROM users WHERE token = '$token'";
```

#### SSL Verification
```php
// ✅ Always verify SSL certificates in production
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
```

### 7. Performance

```php
// ✅ Avoid repetitive calls
$surveyId = $event->get('surveyId'); // Only once

// ✅ Use early returns
if (!in_array($surveyId, $hookSurveyIdArray)) {
    return;
}

// ✅ Release resources
curl_close($ch);
```

---

## 🔧 General Code Best Practices

### 1. SOLID Principles

#### Single Responsibility
Each class/method should have only one reason to change.

```php
// ✅ Correct - Separate methods
private function callWebhook($comment) { /* ... */ }
private function httpPost($url, $payload) { /* ... */ }
private function debug($url, $parameters, ...) { /* ... */ }
```

#### Open/Closed
Code should be open for extension but closed for modification.

#### Liskov Substitution
Derived classes must be substitutable for their base classes.

#### Interface Segregation
Prefer multiple small interfaces over one large interface.

#### Dependency Inversion
Depend on abstractions, not concrete implementations.

### 2. DRY (Don't Repeat Yourself)

```php
// ✅ Correct - Factor out repetitive code
private function getSetting(string $key)
{
    return $this->get($key, null, null, $this->settings[$key]);
}

// Usage
$url = $this->getSetting('sUrl');
$auth = $this->getSetting('sAuthToken');
```

### 3. KISS (Keep It Simple, Stupid)

Favor simplicity and readability over complexity.

```php
// ✅ Simple and readable
$hookSurveyIdArray = is_array($hookSurveyId) 
    ? array_map('trim', $hookSurveyId)
    : explode(',', preg_replace('/\s+/', '', $hookSurveyId));
```

### 4. YAGNI (You Aren't Gonna Need It)

Don't implement features "just in case". Only code what's needed now.

### 5. Comments and Documentation

```php
// ✅ Useful comments - explain the "why"
// Fallback for undefined dates (old LimeSurvey format)
if (empty($submitDate) || $submitDate == '1980-01-01 00:00:00') {
    $submitDate = date('Y-m-d H:i:s');
}

// ❌ Useless comments - repeat the "what"
// Increment i by 1
$i++;
```

### 6. Version Management

- Use **Semantic Versioning** (MAJOR.MINOR.PATCH)
  - MAJOR: Incompatible changes
  - MINOR: Backward-compatible new features
  - PATCH: Backward-compatible bug fixes

### 7. Testing

This project uses **PHPUnit** for unit testing.

#### Installation

```bash
composer install
```

#### Running Tests

```bash
# Run all tests
composer test

# Run tests with verbose output
./vendor/bin/phpunit --verbose

# Run a specific test file
./vendor/bin/phpunit tests/LimeSurveyWebhookTest.php

# Run tests with code coverage report
composer test-coverage
```

#### Test Structure

Tests are located in the `tests/` directory:
- `bootstrap.php` - Sets up mock classes for LimeSurvey dependencies (PluginBase, Yii, etc.)
- `LimeSurveyWebhookTest.php` - Unit tests for the plugin

#### Writing Tests

```php
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
```

#### Test Guidelines

- Write unit tests for all public methods
- Test edge cases (null, empty, extreme values)
- Use descriptive test method names (`testMethodNameWithCondition`)
- Add `@covers` annotations to link tests to methods
- Use debug mode to verify transmitted data in integration tests

### 8. Logging

```php
// ✅ Log important information
$this->log($comment . " | JSON Payload: " . $payload . " | Response: " . $hookSent);

// Recommended log levels:
// - ERROR: Critical errors
// - WARNING: Abnormal situations
// - INFO: General information
// - DEBUG: Debugging details
```

### 9. Configuration

- Externalize configurations (no hardcoded values)
- Provide sensible default values
- Document each configuration option

```php
protected $settings = array(
    'sUrl' => array(
        'type' => 'string',
        'label' => 'The default URL to send the webhook to:',
        'help' => 'To test get one from https://webhook.site'
    ),
    // ...
);
```

### 10. Code Review Checklist

Before each commit, verify:
- [ ] Code follows PSR standards
- [ ] User inputs are validated
- [ ] SQL queries are prepared
- [ ] Errors are handled correctly
- [ ] Code is documented (PHPDoc)
- [ ] No sensitive data in plain text
- [ ] Resources are released (curl_close, etc.)
- [ ] Debug mode is disabled by default
- [ ] Unit tests pass (`composer test`)
- [ ] New features have corresponding tests

---

## 📚 Resources

- [PHP-FIG PSR Standards](https://www.php-fig.org/psr/)
- [LimeSurvey Plugin Documentation](https://manual.limesurvey.org/Plugins_-_advanced)
- [Yii 1.1 Documentation](https://www.yiiframework.com/doc/guide/1.1/en)
- [PHP The Right Way](https://phptherightway.com/)
