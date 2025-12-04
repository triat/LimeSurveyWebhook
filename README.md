# LimeSurveyWebhook

A LimeSurvey plugin that sends a JSON webhook after each survey completion.

## Features

- Sends JSON POST request with survey response data
- Supports multiple survey IDs (comma-separated)
- Includes participant data (name, email) from token table
- Provides both raw and human-readable formatted responses
- Debug mode for troubleshooting

## Installation

1. Download or clone this repository
2. Copy the folder to your LimeSurvey plugins directory: `upload/plugins/`
3. Activate the plugin in **Configuration → Plugins**

## Configuration

| Setting | Description |
|---------|-------------|
| **Webhook URL** | URL to send the POST request to |
| **Survey IDs** | Comma-separated survey IDs to monitor |
| **API Token** | Authentication token sent with requests |
| **Debug Mode** | Shows transmitted data after completion |

## JSON Payload

```json
{
    "api_token": "string",
    "survey": 123456,
    "event": "afterSurveyComplete",
    "respondId": 42,
    "response": { },
    "response_pretty": { },
    "submitDate": "2024-12-04 15:30:00",
    "token": "abc123",
    "participant": {
        "firstname": "John",
        "lastname": "Doe",
        "email": "john@example.com"
    }
}
```

## Development

```bash
composer install   # Install dependencies
composer test      # Run tests
```

## License

GPL-3.0 - See [LICENSE](LICENSE) for details.

## Credits

Originally created by Stefan Verweij ([Evently](https://evently.nl)), with contributions from IrishWolf and Alex Righetto.
