# MyAdmin iContact Mailing List Plugin

Symfony EventDispatcher plugin for MyAdmin that integrates iContact REST API to manage mailing list subscriptions on account activation.

## Stack

- **PHP** >= 5.0 · **Namespace**: `Detain\MyAdminIcontact\` → `src/`
- **Tests**: `Detain\MyAdminIcontact\Tests\` → `tests/`
- **Events**: `GenericEvent` from Symfony EventDispatcher

## Commands

```bash
composer install
vendor/bin/phpunit tests/ -v
vendor/bin/phpunit --coverage-clover coverage.xml --whitelist src/
```

## Architecture

**Entry**: `src/Plugin.php` · class `Detain\MyAdminIcontact\Plugin`

**CI/CD & IDE**: `.github/` contains workflows for automated testing and deployment pipelines · `.idea/` contains IDE project configuration including `inspectionProfiles`, `deployment.xml`, and `encodings.xml`

**Hook registration**: `Plugin::getHooks()` returns event → `[Plugin::class, 'method']` map:
- `system.settings` → `getSettings()` — registers 7 settings via `$settings->add_*_setting()`
- `account.activated` → `doAccountActivated()` — calls `doSetup($accountId)`
- `mailinglist.subscribe` → `doMailinglistSubscribe()` — calls `doEmailSetup($email)`

Example hook registration in `src/Plugin.php`:

```php
public static function getHooks(): array
{
    return [
        'system.settings'       => [__CLASS__, 'getSettings'],
        'account.activated'     => [__CLASS__, 'doAccountActivated'],
        'mailinglist.subscribe' => [__CLASS__, 'doMailinglistSubscribe'],
    ];
}
```

**API flow** (`doEmailSetup()`): build contact array → `json_encode` → `getcurlpage()` with iContact headers → parse `contactId` → subscribe to each list in `ICONTACT_LISTS`

Example iContact subscription call in `src/Plugin.php`:

```php
$url = 'https://app.icontact.com/icp/a/' . ICONTACT_CLIENTID
     . '/c/' . ICONTACT_CLIENTFOLDERID . '/contacts/';
$response = getcurlpage($url, $json, $options);
myadmin_log('accounts', 'info', 'Response: ' . $response, __LINE__, __FILE__);
$response = @json_decode($response);
if (isset($response->contacts[0]->contactId)) {
    // subscribe to each list in ICONTACT_LISTS
}
```

**Constants used**: `ICONTACT_ENABLE` · `ICONTACT_APIID` · `ICONTACT_APIUSERNAME` · `ICONTACT_APIPASSWORD` · `ICONTACT_CLIENTID` · `ICONTACT_CLIENTFOLDERID` · `ICONTACT_LISTS`

**iContact endpoints**:
- `POST https://app.icontact.com/icp/a/{clientId}/c/{folderId}/contacts/`
- `POST https://app.icontact.com/icp/a/{clientId}/c/{folderId}/subscriptions/`

## Conventions

- All hook handlers: `public static function`, accept `GenericEvent $event`, check `ICONTACT_ENABLE == 1` before acting
- Logging: `myadmin_log('accounts', 'info', $message, __LINE__, __FILE__)`
- Settings: use `add_dropdown_setting` for enable/disable, `add_password_setting` for secrets, `add_text_setting` for IDs
- Static properties: `$name`, `$description`, `$help`, `$type = 'plugin'` — all `public static string`
- Tests use `ReflectionClass` to verify method signatures, static visibility, and parameter types
- `phpunit.xml.dist` at repo root configures test bootstrap

## Adding a New Event Hook

1. Add entry to `getHooks()` return array using dot-notation event name
2. Add `public static function doEventName(GenericEvent $event)` method
3. Guard with `defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1`
4. Add corresponding test in `tests/PluginTest.php` with signature assertions

## Quality

- `.scrutinizer.yml`: tabs for indentation, camelCase params/properties, `argument_type_checks`, `duplication` enabled
- `.codeclimate.yml`: `phpmd` engine, cyclomatic complexity threshold 100, `tests/` excluded from ratings
- `.bettercodehub.yml`: php language target

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
