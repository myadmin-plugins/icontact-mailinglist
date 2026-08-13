---
name: plugin-event-handler
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php in the myadmin-icontact-mailinglist plugin. Registers the event in getHooks(), creates the static handler method with GenericEvent $event signature, and guards with ICONTACT_ENABLE check. Use when user says 'add event hook', 'new handler', 'listen for event', or needs to wire a new dot-notation event to Plugin. Do NOT use for modifying iContact API calls or doEmailSetup/doSetup logic. For a plugin's contract or behavioral tests (tests/ContractTest.php, the shared harness, composer myadmin:scaffold-tests) use the plugin-contract-tests skill instead — this skill's reflection-only guidance predates that harness.
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

# plugin-event-handler

## Critical

- ALL hook handler methods MUST be `public static` — tests assert both `isPublic()` and `isStatic()` via `ReflectionClass`
- ALL handler methods MUST accept exactly one parameter named `$event` typed `GenericEvent` — tests assert parameter name and type
- EVERY handler that acts on iContact MUST guard with `if (defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1)` — no action without this check
- Event names MUST use dot-notation (e.g. `account.activated`, `mailinglist.subscribe`) — tests assert `assertStringContainsString('.')`
- Method names MUST start with a lowercase letter — the test `testHookValuesFollowConvention` asserts this
- After adding a hook, update `tests/PluginTest.php` — the test `testGetHooksReturnsThreeHooks` hard-counts hooks and WILL fail otherwise

## Instructions

1. **Register the event in `getHooks()`** (`src/Plugin.php`). Add one entry to the returned array:
   ```php
   'noun.verb' => [__CLASS__, 'doNounVerb'],
   ```
   Use `__CLASS__` (not the string `'Plugin'`). Verify the event name contains a `.` and the method name starts lowercase.

2. **Add the handler method** directly after the last existing handler in `src/Plugin.php`. Follow this exact shape:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function doNounVerb(GenericEvent $event)
   {
       $subject = $event->getSubject();
       if (defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1) {
           myadmin_log('accounts', 'info', 'doNounVerb called', __LINE__, __FILE__);
           // handler logic here
       }
   }
   ```
   Verify: method is `public static`, parameter is `GenericEvent $event`, guard is present.

3. **Add tests in `tests/PluginTest.php`**. Add two test methods:

   a. Assert the hook key exists and maps to the correct callback:
   ```php
   public function testNounVerbHookMapsToDoNounVerb(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertSame([Plugin::class, 'doNounVerb'], $hooks['noun.verb']);
   }
   ```

   b. Assert the method signature:
   ```php
   public function testDoNounVerbSignature(): void
   {
       $method = $this->reflection->getMethod('doNounVerb');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $type = $params[0]->getType();
       $this->assertNotNull($type);
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```

4. **Update the hook count assertion** in `testGetHooksReturnsThreeHooks()`. Change the `assertCount` value to match the new total number of active hooks.

5. **Run tests** to confirm all assertions pass:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```

## Examples

**User says:** "Add a hook for `account.cancelled` that logs when an account is cancelled"

**Step 1** — add to `getHooks()` in `src/Plugin.php`:
```php
'account.cancelled' => [__CLASS__, 'doAccountCancelled'],
```

**Step 2** — add method in `src/Plugin.php`:
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function doAccountCancelled(GenericEvent $event)
{
    $account = $event->getSubject();
    if (defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1) {
        myadmin_log('accounts', 'info', 'doAccountCancelled('.$account->getId().') Called', __LINE__, __FILE__);
    }
}
```

**Step 3** — add to `tests/PluginTest.php` and bump `assertCount(3, ...)` → `assertCount(4, ...)`.

**Result:** `vendor/bin/phpunit tests/ -v` passes with all new assertions green.

## Common Issues

- **`testGetHooksReturnsThreeHooks` fails with "Failed asserting that 4 matches expected 3"**: Update the `assertCount` in that test to the new hook total.
- **`testHookCallbackMethodsAreStatic` fails**: Method was declared without `static`. Change `public function` → `public static function`.
- **`testDoNounVerbSignature` fails on parameter type**: The `GenericEvent` import is missing. Confirm `use Symfony\Component\EventDispatcher\GenericEvent;` is present at the top of `src/Plugin.php`.
- **`testHookEventNamesFollowDotNotation` fails**: Event name has no `.` (e.g. `accountcancelled`). Rename to dot-notation (e.g. `account.cancelled`).
- **`testHookValuesFollowConvention` fails**: Method name starts with uppercase. Rename `DoNounVerb` → `doNounVerb`.
