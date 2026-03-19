<?php

declare(strict_types=1);

namespace Detain\MyAdminIcontact\Tests;

use Detain\MyAdminIcontact\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Test suite for the Plugin class.
 *
 * @covers \Detain\MyAdminIcontact\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the class exists in the expected namespace.
     */
    public function testClassExistsInCorrectNamespace(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
        $this->assertSame('Detain\MyAdminIcontact\Plugin', $this->reflection->getName());
    }

    /**
     * Test that the $name static property is defined and holds the expected value.
     */
    public function testNamePropertyExists(): void
    {
        $this->assertTrue($this->reflection->hasProperty('name'));
        $property = $this->reflection->getProperty('name');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('Icontact Plugin', Plugin::$name);
    }

    /**
     * Test that the $description static property is defined and holds the expected value.
     */
    public function testDescriptionPropertyExists(): void
    {
        $this->assertTrue($this->reflection->hasProperty('description'));
        $property = $this->reflection->getProperty('description');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('Allows handling of Icontact based Mailing List Subscriptions', Plugin::$description);
    }

    /**
     * Test that the $help static property is defined and is an empty string.
     */
    public function testHelpPropertyExists(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $property = $this->reflection->getProperty('help');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that the $type static property is defined and set to 'plugin'.
     */
    public function testTypePropertyExists(): void
    {
        $this->assertTrue($this->reflection->hasProperty('type'));
        $property = $this->reflection->getProperty('type');
        $this->assertTrue($property->isPublic());
        $this->assertTrue($property->isStatic());
        $this->assertSame('plugin', Plugin::$type);
    }

    /**
     * Test that the constructor takes no parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains the expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('system.settings', $hooks);
        $this->assertArrayHasKey('account.activated', $hooks);
        $this->assertArrayHasKey('mailinglist.subscribe', $hooks);
    }

    /**
     * Test that getHooks does not contain the commented-out ui.menu hook.
     */
    public function testGetHooksDoesNotContainUiMenu(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayNotHasKey('ui.menu', $hooks);
    }

    /**
     * Test that getHooks returns exactly 3 hooks.
     */
    public function testGetHooksReturnsThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Test that each hook callback references the Plugin class.
     */
    public function testGetHooksCallbacksReferencePluginClass(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsArray($callback, "Callback for '$eventName' should be an array");
            $this->assertCount(2, $callback, "Callback for '$eventName' should have two elements");
            $this->assertSame(Plugin::class, $callback[0], "First element of callback for '$eventName' should be the Plugin class");
            $this->assertIsString($callback[1], "Second element of callback for '$eventName' should be a string method name");
        }
    }

    /**
     * Test that each hook callback method actually exists on the Plugin class.
     */
    public function testGetHooksCallbackMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertTrue(
                method_exists(Plugin::class, $callback[1]),
                "Method '{$callback[1]}' referenced by hook '$eventName' does not exist on Plugin class"
            );
        }
    }

    /**
     * Test that the system.settings hook maps to the getSettings method.
     */
    public function testSystemSettingsHookMapsToGetSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['system.settings']);
    }

    /**
     * Test that the account.activated hook maps to the doAccountActivated method.
     */
    public function testAccountActivatedHookMapsToDoAccountActivated(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'doAccountActivated'], $hooks['account.activated']);
    }

    /**
     * Test that the mailinglist.subscribe hook maps to the doMailinglistSubscribe method.
     */
    public function testMailinglistSubscribeHookMapsToDoMailinglistSubscribe(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'doMailinglistSubscribe'], $hooks['mailinglist.subscribe']);
    }

    /**
     * Test that all hook callback methods are static.
     */
    public function testHookCallbackMethodsAreStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $method = $this->reflection->getMethod($callback[1]);
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$callback[1]}' referenced by hook '$eventName' should be static"
            );
        }
    }

    /**
     * Test that all hook callback methods are public.
     */
    public function testHookCallbackMethodsArePublic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $method = $this->reflection->getMethod($callback[1]);
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$callback[1]}' referenced by hook '$eventName' should be public"
            );
        }
    }

    /**
     * Test that getHooks is a static method.
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getHooks is a public method.
     */
    public function testGetHooksIsPublic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that getHooks takes no parameters.
     */
    public function testGetHooksHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that doAccountActivated method exists and accepts a GenericEvent parameter.
     */
    public function testDoAccountActivatedSignature(): void
    {
        $method = $this->reflection->getMethod('doAccountActivated');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that doMailinglistSubscribe method exists and accepts a GenericEvent parameter.
     */
    public function testDoMailinglistSubscribeSignature(): void
    {
        $method = $this->reflection->getMethod('doMailinglistSubscribe');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that getSettings method exists and accepts a GenericEvent parameter.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test that doSetup method exists and is static.
     */
    public function testDoSetupMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doSetup');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('accountId', $params[0]->getName());
    }

    /**
     * Test that doEmailSetup method exists, is static, and has correct parameter signature.
     */
    public function testDoEmailSetupMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doEmailSetup');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('email', $params[0]->getName());
        $this->assertFalse($params[0]->isOptional());
        $this->assertSame('params', $params[1]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertFalse($params[1]->getDefaultValue());
    }

    /**
     * Test that the Plugin class has exactly the expected number of public methods.
     */
    public function testPluginClassMethodCount(): void
    {
        $methods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $expectedMethods = [
            '__construct',
            'getHooks',
            'doAccountActivated',
            'doMailinglistSubscribe',
            'getSettings',
            'doSetup',
            'doEmailSetup',
        ];
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $methodNames,
                "Expected public method '$expectedMethod' not found on Plugin class"
            );
        }
    }

    /**
     * Test that the class has exactly 4 static properties.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProperties = $this->reflection->getStaticProperties();
        $this->assertCount(4, $staticProperties);
    }

    /**
     * Test that all static properties have string values.
     */
    public function testStaticPropertiesAreStrings(): void
    {
        $this->assertIsString(Plugin::$name);
        $this->assertIsString(Plugin::$description);
        $this->assertIsString(Plugin::$help);
        $this->assertIsString(Plugin::$type);
    }

    /**
     * Test that getHooks returns callable-compatible arrays.
     */
    public function testGetHooksReturnsCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertTrue(
                is_callable($callback),
                "Hook callback for '$eventName' should be callable"
            );
        }
    }

    /**
     * Test that the Plugin class does not extend any other class.
     */
    public function testPluginClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that the Plugin class does not implement any interfaces.
     */
    public function testPluginClassImplementsNoInterfaces(): void
    {
        $this->assertCount(0, $this->reflection->getInterfaces());
    }

    /**
     * Test that the Plugin class is not abstract.
     */
    public function testPluginClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the Plugin class is not final.
     */
    public function testPluginClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test that doAccountActivated does nothing when ICONTACT_ENABLE is not defined.
     *
     * Uses an anonymous subject class to avoid mocking vendor classes.
     */
    public function testDoAccountActivatedSkipsWhenConstantNotDefined(): void
    {
        if (defined('ICONTACT_ENABLE')) {
            $this->markTestSkipped('ICONTACT_ENABLE is already defined, cannot test undefined behavior');
        }

        $subject = new class {
            public function getId(): int
            {
                return 1;
            }
        };

        $event = new GenericEvent($subject);

        // Should not throw any exceptions when ICONTACT_ENABLE is not defined
        Plugin::doAccountActivated($event);
        $this->assertTrue(true, 'doAccountActivated completed without error when ICONTACT_ENABLE is not defined');
    }

    /**
     * Test that doMailinglistSubscribe does nothing when ICONTACT_ENABLE is not defined.
     */
    public function testDoMailinglistSubscribeSkipsWhenConstantNotDefined(): void
    {
        if (defined('ICONTACT_ENABLE')) {
            $this->markTestSkipped('ICONTACT_ENABLE is already defined, cannot test undefined behavior');
        }

        $event = new GenericEvent('test@example.com');

        // Should not throw any exceptions when ICONTACT_ENABLE is not defined
        Plugin::doMailinglistSubscribe($event);
        $this->assertTrue(true, 'doMailinglistSubscribe completed without error when ICONTACT_ENABLE is not defined');
    }

    /**
     * Test that the hook values follow the [ClassName, methodName] convention.
     */
    public function testHookValuesFollowConvention(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]/',
                $callback[1],
                "Method name '{$callback[1]}' for hook '$eventName' should start with a lowercase letter"
            );
        }
    }

    /**
     * Test that event hook names follow the dot-separated naming convention.
     */
    public function testHookEventNamesFollowDotNotation(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $eventName) {
            $this->assertStringContainsString(
                '.',
                $eventName,
                "Event name '$eventName' should use dot notation"
            );
        }
    }
}
