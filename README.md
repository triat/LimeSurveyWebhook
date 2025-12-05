# LimeSurvey - Webhook

A LimeSurvey plugin that sends JSON webhooks after survey completion, with per-survey configuration.

## Features

- **Per-survey configuration** - Enable/disable webhooks individually for each survey
- **Multiple webhooks** - Send to different URLs and multiple URLs per survey
- **Fallback defaults** - Global default URL and token when survey-specific not set
- **Participant data** - Includes name and email from token table
- **Formatted responses** - Both raw and human-readable responses
- **Debug mode** - Shows transmitted data for troubleshooting

## Installation

1. Download the `.zip` file from the [latest release](../../releases/latest)
2. In LimeSurvey, go to **Configuration → Plugins**
3. Click **Upload & install**
4. Select the downloaded `.zip` file
5. Click **Install**

For alternative installation methods, see the [LimeSurvey Plugin Manager documentation](https://www.limesurvey.org/manual/Plugin_manager#How_can_I_install_a_third-party_plugins?).

## Configuration

### Global Settings (Plugin Configuration)

| Setting | Description |
|---------|-------------|
| **Default Webhook URL** | Fallback URL when no survey-specific URL is set |
| **Default Auth Token** | Fallback token when no survey-specific token is set |
| **Debug Mode** | Display webhook data after completion |

### Per-Survey Settings (Survey → Settings → Simple plugins → LimeSurveyWebhook)

| Setting | Description |
|---------|-------------|
| **Enable webhook** | Activate webhook for this survey |
| **Webhook URL(s)** | One URL per line (supports multiple) |
| **Auth Token** | Survey-specific token (optional) |

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
make install    # Install dependencies
make test       # Run tests
make lint       # Check code style
```

## License

GPL-3.0 - See [LICENSE](LICENSE)

## Credits

Originally by Stefan Verweij ([Evently](https://evently.nl)), with contributions from IrishWolf, Alex Righetto, and Tom Riat.
