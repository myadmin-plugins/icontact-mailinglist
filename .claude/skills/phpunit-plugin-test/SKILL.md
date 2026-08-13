---
name: phpunit-plugin-test
description: Writes PHPUnit 9 tests for `src/Plugin.php` following the ReflectionClass-based pattern in `tests/PluginTest.php`. Covers method existence, static/public visibility, parameter names and types, return types, and early-exit behavior when ICONTACT_ENABLE is undefined. Use when user says 'write test', 'add test coverage', 'test the plugin', or adds a new method to Plugin. Do NOT use for integration or functional tests that invoke live iContact API calls. NOTE: for a plugin's contract/behavioral tests (tests/ContractTest.php, the shared harness, composer myadmin:scaffold-tests) use the plugin-contract-tests skill instead — this skill's reflection-only guidance predates that harness.
---
<!-- myadmin-contract-harness-notice -->
> ### ⚠️ Read this before the rest of the file
>
> This package is on the **shared plugin contract harness**. Parts of the guidance below
> predate it and are now wrong in one specific way:
>
> **Any instruction here that a plugin's `getHooks()` / `getSettings()` / `getActivate()` /
> `getDeactivate()` / `getQueue()` must not be *called* — that only its existence, visibility
> or parameter count may be checked through `ReflectionClass` — no longer applies.** That rule
> existed because those methods reference bare constants (`PRORATE_BILLING` and friends) that
> only a live MyAdmin request defines, so calling them from a test used to fatal. The harness
> defines them first. It then executes the handlers for real, in a process of its own.
>
> A reflection-only assertion passes whether or not the thing works: `getActivate()` can exist,
> be public, be static, take one argument, and still fatal the moment it runs. Three real
> production bugs in this fleet were sitting behind assertions of exactly that shape.
>
> **Use the `plugin-contract-tests` skill** for anything touching `tests/ContractTest.php`,
> the contract inspectors, or `composer myadmin:scaffold-tests`.
>
> **Everything else in this file is still accurate and still applies** — this package's own
> classes, its API wrappers, its fixtures, its bootstrap, and the reasons certain classes must
> not be constructed. Nothing below has been removed.

# phpunit-plugin-test

## Critical

- **Never instantiate `GenericEvent` with a mock** — use `new GenericEvent($realSubject)` with an anonymous class or a plain value (string, int).
- **Never call methods that require runtime globals** (`$GLOBALS['tf']`, `getcurlpage`, `myadmin_log`) in tests — only test method signatures and guard behavior.
- **Always guard early-exit tests** with `if (defined('ICONTACT_ENABLE')) { $this->markTestSkipped(...); }` before the assertion.
- Test file lives at `tests/PluginTest.php` · namespace `Detain\MyAdminIcontact\Tests` · class `PluginTest extends TestCase`.

## Instructions

1. **Set up the test class skeleton.** Open `tests/PluginTest.php`. The file must start with:
   ```php
   <?php
   declare(strict_types=1);
   namespace Detain\MyAdminIcontact\Tests;
   use Detain\MyAdminIcontact\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   /** @covers \Detain\MyAdminIcontact\Plugin */
   class PluginTest extends TestCase {
       private ReflectionClass $reflection;
       protected function setUp(): void {
           $this->reflection = new ReflectionClass(Plugin::class);
       }
   }
   ```
   Verify `composer install` has been run before executing tests.

2. **Test static properties.** For each of `$name`, `$description`, `$help`, `$type`, add a test method:
   ```php
   public function testNamePropertyExists(): void {
       $this->assertTrue($this->reflection->hasProperty('name'));
       $property = $this->reflection->getProperty('name');
       $this->assertTrue($property->isPublic());
       $this->assertTrue($property->isStatic());
       $this->assertSame('Icontact Plugin', Plugin::$name);
   }
   ```
   Use the exact string values from `src/Plugin.php` lines 14–17. Verify `testStaticPropertyCount` asserts `assertCount(4, $this->reflection->getStaticProperties())`.

3. **Test `getHooks()` structure.** Assert the method is static + public + has zero parameters. Assert the return value is an array with exactly 3 keys using dot-notation event names (`system.settings`, `account.activated`, `mailinglist.subscribe`). Assert each value is `[Plugin::class, 'methodName']` and is `is_callable()`. Pattern:
   ```php
   $hooks = Plugin::getHooks();
   $this->assertCount(3, $hooks);
   $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
   ```

4. **Test event-handler method signatures.** For `doAccountActivated`, `doMailinglistSubscribe`, and `getSettings`, use `ReflectionMethod` to assert: `isStatic()`, `isPublic()`, exactly 1 parameter named `event`, typed `Symfony\Component\EventDispatcher\GenericEvent`. Pattern:
   ```php
   $method = $this->reflection->getMethod('doAccountActivated');
   $this->assertTrue($method->isStatic());
   $params = $method->getParameters();
   $this->assertCount(1, $params);
   $this->assertSame('event', $params[0]->getName());
   $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   ```

5. **Test `doSetup` and `doEmailSetup` signatures.** `doSetup` has 1 param named `accountId`. `doEmailSetup` has 2 params: `email` (required) and `params` (optional, default `false`):
   ```php
   $this->assertSame('params', $params[1]->getName());
   $this->assertTrue($params[1]->isOptional());
   $this->assertFalse($params[1]->getDefaultValue());
   ```

6. **Test early-exit guard behavior.** For each event handler, test that calling it without `ICONTACT_ENABLE` defined does NOT throw:
   ```php
   public function testDoAccountActivatedSkipsWhenConstantNotDefined(): void {
       if (defined('ICONTACT_ENABLE')) {
           $this->markTestSkipped('ICONTACT_ENABLE is already defined');
       }
       $subject = new class { public function getId(): int { return 1; } };
       Plugin::doAccountActivated(new GenericEvent($subject));
       $this->assertTrue(true);
   }
   ```
   For `doMailinglistSubscribe`, pass `new GenericEvent('test@example.com')`.

7. **Run tests.** Execute:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```
   All tests must pass before adding more. For a new method added to `src/Plugin.php`, add tests for steps 4–6 above for that method.

## Examples

**User says:** "I added a `doNewsletterOptIn(GenericEvent $event)` method to Plugin.php — write the tests."

**Actions taken:**
1. Add signature test using `ReflectionClass`: assert static, public, 1 param named `event` typed `GenericEvent`.
2. Add `testGetHooksContains` test asserting `newsletter.optin` key maps to `[Plugin::class, 'doNewsletterOptIn']` and update `testGetHooksReturnsThreeHooks` → `assertCount(4, $hooks)`.
3. Add early-exit test: guard with `markTestSkipped` if `ICONTACT_ENABLE` defined, call `Plugin::doNewsletterOptIn(new GenericEvent('x'))`, assert `assertTrue(true)`.
4. Run `vendor/bin/phpunit tests/ -v`.

**Result:** 3 new passing tests; zero live API calls made.

## Common Issues

- **`Class 'Detain\MyAdminIcontact\Plugin' not found`**: Run `composer install` first. The bootstrap must load the Composer autoloader.
- **`Cannot redeclare constant ICONTACT_ENABLE`**: Test environment defines it in a prior test. Add `if (defined('ICONTACT_ENABLE')) { $this->markTestSkipped(...); }` at the top of every early-exit test.
- **`ReflectionException: Method doFoo does not exist`**: The method name in `getHooks()` doesn't match the actual method. Verify spelling in both `getHooks()` return array and the method declaration in `src/Plugin.php`.
- **`assertSame failed: expected 'event', got 'e'`**: The parameter was declared as `GenericEvent $e` instead of `GenericEvent $event`. Fix the parameter name in `src/Plugin.php` to match convention.
- **`TypeError: getType() returned null`**: The parameter has no type hint. Add `GenericEvent` type hint to the method signature in `src/Plugin.php`.
