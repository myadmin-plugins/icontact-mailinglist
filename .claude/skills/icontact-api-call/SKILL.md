---
name: icontact-api-call
description: Makes an iContact REST API POST call using `getcurlpage()` with JSON body and iContact auth headers. Follows the exact pattern in `Plugin::doEmailSetup()` in `src/Plugin.php`: build payload array → `json_encode` → set `CURLOPT_HTTPHEADER` with API-AppId/Username/Password → call endpoint → parse JSON response. Use when user says 'call icontact api', 'create contact', 'subscribe to list', or extends iContact API integration. Do NOT use for event hook wiring or settings registration.
---
# iContact API Call

## Critical

- Always guard with `defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1` before any API call
- Never access `ICONTACT_*` constants without `defined()` check — they may not be set
- Use `@json_decode()` on the response — iContact can return malformed JSON on errors
- Always log both the call and the raw response with `myadmin_log('accounts', 'info', ..., __LINE__, __FILE__)`
- Never build HTTP headers with PDO or direct DB access — this plugin uses `getcurlpage()` exclusively

## Instructions

1. **Guard and build the payload array.** Check constants are defined, then construct an associative array for the contact or subscription body.
   ```php
   if (!defined('ICONTACT_ENABLE') || ICONTACT_ENABLE != 1) {
       return;
   }
   $contact = [
       'email'  => $email,
       'status' => 'normal',
   ];
   $contacts[] = $contact;
   $json = json_encode($contacts);
   ```
   Verify `$json` is not `false` before proceeding.

2. **Build the shared `$options` array with iContact auth headers.** Reuse this array for both the contacts and subscriptions endpoints.
   ```php
   $options = [
       CURLOPT_HTTPHEADER => [
           'Accept: application/json',
           'Content-Type: application/json',
           'API-Version: 2.2',
           'API-AppId: '     . ICONTACT_APIID,
           'API-Username: '  . ICONTACT_APIUSERNAME,
           'API-Password: '  . ICONTACT_APIPASSWORD,
       ],
       CURLOPT_POST           => 1,
       CURLOPT_SSL_VERIFYPEER => false,
   ];
   ```
   Verify all six header values are present.

3. **POST to the contacts endpoint and parse `contactId`.** Log the raw response before decoding.
   ```php
   $url = 'https://app.icontact.com/icp/a/' . ICONTACT_CLIENTID
        . '/c/' . ICONTACT_CLIENTFOLDERID . '/contacts/';
   myadmin_log('accounts', 'info', "icontact POST contacts($email) Called", __LINE__, __FILE__);
   $response = getcurlpage($url, $json, $options);
   myadmin_log('accounts', 'info', 'Response: ' . $response, __LINE__, __FILE__);
   $response = @json_decode($response);
   ```
   Verify `$response->contacts[0]->contactId` exists before proceeding to subscriptions.

4. **POST to the subscriptions endpoint for each list in `ICONTACT_LISTS`.** Parse the CSV list first.
   ```php
   if (isset($response->contacts[0]->contactId)) {
       $contactid = $response->contacts[0]->contactId;
       $listsCsv  = explode(',', ICONTACT_LISTS);
       foreach ($listsCsv as $list) {
           $listid = (int) trim($list);
           $subJson = json_encode([[
               'contactId' => $contactid,
               'listId'    => $listid,
               'status'    => 'normal',
           ]]);
           $subUrl = 'https://app.icontact.com/icp/a/' . ICONTACT_CLIENTID
                   . '/c/' . ICONTACT_CLIENTFOLDERID . '/subscriptions/';
           $lresponse = getcurlpage($subUrl, $subJson, $options);
           myadmin_log('accounts', 'info', 'Response: ' . $lresponse, __LINE__, __FILE__);
       }
   }
   ```

5. **Run tests** to verify the method signature is intact:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```

## Examples

**User says:** "Add a method to subscribe a custom email with a business name to iContact."

**Actions taken:**
1. Add `public static function doCustomSubscribe($email, $business)` in `src/Plugin.php`
2. Guard with `defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1`
3. Build `$contact = ['email' => $email, 'status' => 'normal', 'business' => $business]`
4. Wrap in array, `json_encode`, build `$options` with all six headers
5. POST to contacts URL, log raw response, `@json_decode`
6. On success, loop `ICONTACT_LISTS` CSV and POST each subscription

**Result:** New static method following the same shape as `doEmailSetup()` in `src/Plugin.php`.

## Common Issues

- **`Undefined constant 'ICONTACT_APIID'`** — missing `defined()` guard. Wrap every constant access with `defined('ICONTACT_ENABLE') && ICONTACT_ENABLE == 1` at method entry.
- **`getcurlpage()` returns empty string** — `CURLOPT_SSL_VERIFYPEER => false` must be set; the iContact endpoint rejects connections that omit it in some environments.
- **`$response->contacts` is null / `@json_decode` returns null** — iContact returns a non-JSON error body (e.g., `401 Unauthorized`). Check `ICONTACT_APIID`, `ICONTACT_APIUSERNAME`, and `ICONTACT_APIPASSWORD` constants match the iContact app credentials. Check the raw logged response string for the HTTP error message.
- **Subscriptions POST silently does nothing** — `ICONTACT_LISTS` is empty or whitespace. Verify `trim($list)` yields a non-zero integer; `(int) ''` is `0`, which is an invalid list ID.
- **Tests fail with `Parameter 0 ... must be of type GenericEvent`** — new public methods that are event handlers must accept exactly one `GenericEvent $event` parameter; internal helpers like `doEmailSetup` accept plain scalars.
