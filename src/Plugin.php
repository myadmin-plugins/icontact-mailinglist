<?php

namespace Detain\MyAdminIcontact;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminIcontact
 */
class Plugin {

	public static $name = 'Icontact Plugin';
	public static $description = 'Allows handling of Icontact emails and honeypots';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			'account.activated' => [__CLASS__, 'doAccountActivated'],
			'mailinglist.subscribe' => [__CLASS__, 'doMailinglistSubscribe'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doAccountActivated(GenericEvent $event) {
		$account = $event->getSubject();
		if (defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1) {
			self::doSetup($account->getId());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function doMailinglistSubscribe(GenericEvent $event) {
		$email = $event->getSubject();
		if (defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1) {
			self::doEmailSetup($email);
		}
	}
	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('Accounts', 'iContact', 'icontact_enable', 'Enable iContact', 'Enable/Disable iContact Mailing on Account Signup', (defined('ICONTACT_ENABLE') ? ICONTACT_ENABLE : '0'), ['0', '1'], ['No', 'Yes']);
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apiid', 'API ID', 'API ID', (defined('ICONTACT_APIID') ? ICONTACT_APIID : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apiusername', 'API Username', 'API Username', (defined('ICONTACT_APIUSERNAME') ? ICONTACT_APIUSERNAME : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_apipassword', 'API Password', 'API Password', (defined('ICONTACT_APIPASSWORD') ? ICONTACT_APIPASSWORD : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_clientid', 'API Client ID', 'API Client ID', (defined('ICONTACT_CLIENTID') ? ICONTACT_CLIENTID : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_clientfolderid', 'API Client Folder ID', 'API Client Folder ID', (defined('ICONTACT_CLIENTFOLDERID') ? ICONTACT_CLIENTFOLDERID : ''));
		$settings->add_text_setting('Accounts', 'iContact', 'icontact_lists', 'Lists/Folders', 'Lists to subscribe to (comma seperated ie 100,103)', (defined('ICONTACT_LISTS') ? ICONTACT_LISTS : ''));
	}

	/**
	 * @param int $custid
	 */
	public static function doSetup($accountId) {
		myadmin_log('accounts', 'info', "icontact_setup($accountId) Called", __LINE__, __FILE__);
		$module = get_module_name('default');
		$data = $GLOBALS['tf']->accounts->read($accountId);
		$email = $data['account_lid'];
		list($first, $last) = explode(' ', $data['name']);
		$contact = [
			'firstName' => $first,
			//			'lastName' =>  $last,
			//			'street' => $data['address'],
			//			'city' => $data['city'],
			//			'state' => mb_substr($data['state'], 0, 10),
			//			'postalCode' => $data['zip'],
			//			'phone' => $data['phone'],
		];
		if (isset($data['company']))
			$contact['business'] = $data['company'];
		self::doEmailSetup($email, $contact);
	}

	/**
	 * @param string $lid
	 * @param false|array $parrams
	 */
	public static function doEmailSetup($email, $params = false) {
		myadmin_log('accounts', 'info', "icontact_setup($email) Called", __LINE__, __FILE__);
		$contact = [
			'email' => $email,
			'status' => 'normal'
		];
		if ($params !== false)
			$contact = array_merge($contact, $params);
		$contacts[] = $contact;
		$json = json_encode($contacts);
		$options = [
			//            CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Content-Type: application/json',
				'API-Version: 2.2',
				'API-AppId: '.ICONTACT_APIID,
				'API-Username: '.ICONTACT_APIUSERNAME,
				'API-Password: '.ICONTACT_APIPASSWORD
			],
			CURLOPT_POST => 1,
			CURLOPT_SSL_VERIFYPEER => false
		];
		$response = getcurlpage('https://app.icontact.com/icp/a/'.ICONTACT_CLIENTID.'/c/'.ICONTACT_CLIENTFOLDERID.'/contacts/', $json, $options);
		myadmin_log('accounts', 'info', 'Response: '.$response, __LINE__, __FILE__);
		$response = @json_decode($response);
		if (isset($response->contacts[0]->contactId)) {
			$contactid = $response->contacts[0]->contactId;
			$lists = [];
			$listsCsv = explode(',', ICONTACT_LISTS);
			foreach ($listsCsv as $list)
				$lists[] = (int)trim($list);
			foreach ($lists as $listid) {
				$json = json_encode(
					[
						[
						'contactId' => $contactid,
						'listId' => $listid,
						'status' => 'normal'
						]
					]
				);
				$lresponse = getcurlpage('https://app.icontact.com/icp/a/'.ICONTACT_CLIENTID.'/c/'.ICONTACT_CLIENTFOLDERID.'/subscriptions/', $json, $options);
				myadmin_log('accounts', 'info', 'Response: '.$lresponse, __LINE__, __FILE__);
			}
		}
	}
}