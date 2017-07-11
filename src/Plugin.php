<?php

namespace Detain\MyAdminIcontact;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Icontact Plugin';
	public static $description = 'Allows handling of Icontact emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '//my.interserver.net/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Icontact');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Icontact', '/../vendor/detain/myadmin-icontact-mailinglist/src/Icontact.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-icontact-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-icontact-mailinglist/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-icontact-mailinglist/src/abuse.inc.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apiid', 'API ID', 'API ID', (defined('ICONTACT_APIID') ? ICONTACT_APIID : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apiusername', 'API Username', 'API Username', (defined('ICONTACT_APIUSERNAME') ? ICONTACT_APIUSERNAME : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apipassword', 'API Password', 'API Password', (defined('ICONTACT_APIPASSWORD') ? ICONTACT_APIPASSWORD : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_clientid', 'API Client ID', 'API Client ID', (defined('ICONTACT_CLIENTID') ? ICONTACT_CLIENTID : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_clientfolderid', 'API Client Folder ID', 'API Client Folder ID', (defined('ICONTACT_CLIENTFOLDERID') ? ICONTACT_CLIENTFOLDERID : ''));
	}

}
