# MyAdmin iContact Mailing List Plugin

iContact mailing list integration plugin for the MyAdmin control panel, providing automated subscriber management and list synchronization via the iContact API. Handles account activation events and mailing list subscriptions, automatically registering contacts and managing list memberships through the iContact REST API.

[![Build Status](https://github.com/detain/myadmin-icontact-mailinglist/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-icontact-mailinglist/actions)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-icontact-mailinglist/version)](https://packagist.org/packages/detain/myadmin-icontact-mailinglist)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-icontact-mailinglist/downloads)](https://packagist.org/packages/detain/myadmin-icontact-mailinglist)
[![License](https://poser.pugx.org/detain/myadmin-icontact-mailinglist/license)](https://packagist.org/packages/detain/myadmin-icontact-mailinglist)

## Features

- Automatic contact creation in iContact on account activation
- Mailing list subscription management via event-driven hooks
- Configurable API credentials through the MyAdmin settings panel
- Support for multiple list subscriptions (comma-separated list IDs)

## Installation

```sh
composer require detain/myadmin-icontact-mailinglist
```

## Configuration

The plugin registers the following settings in the MyAdmin admin panel under **Accounts > iContact**:

| Setting | Description |
|---------|-------------|
| `icontact_enable` | Enable or disable iContact integration |
| `icontact_apiid` | iContact API application ID |
| `icontact_apiusername` | iContact API username |
| `icontact_apipassword` | iContact API password |
| `icontact_clientid` | iContact client (account) ID |
| `icontact_clientfolderid` | iContact client folder ID |
| `icontact_lists` | Comma-separated list IDs for subscriptions |

## Event Hooks

The plugin listens on the following Symfony EventDispatcher events:

| Event | Handler | Description |
|-------|---------|-------------|
| `system.settings` | `getSettings` | Registers plugin configuration fields |
| `account.activated` | `doAccountActivated` | Creates iContact contact on account activation |
| `mailinglist.subscribe` | `doMailinglistSubscribe` | Subscribes an email address to configured lists |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://opensource.org/licenses/LGPL-2.1) license.
