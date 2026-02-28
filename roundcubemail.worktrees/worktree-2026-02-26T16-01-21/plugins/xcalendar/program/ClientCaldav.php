<?php
namespace XCalendar;

use Sabre\DAV\Client;
use Sabre\Xml\Service as XmlService;
use XFramework\Utils;

class ClientCaldav
{
    public static function enabled(): bool
    {
        return (bool)xrc()->config->get("xcalendar_caldav_client_enabled", true);
    }

    /**
     * @throws \Exception
     */
    public static function create($calendarId): array
    {
        if (!self::enabled()) {
            throw new \Exception("CalDAV client functionality disabled. (289112)");
        }

        if (empty($calendarData = CalendarData::load($calendarId))) {
            throw new \Exception();
        }
        $properties = $calendarData->get("properties");

        if ($calendarData->getType() != Calendar::CALDAV ||
            empty($calendarData->getId()) ||
            !self::verifyProperties($properties)
        ) {
            throw new \Exception(xrc()->gettext([
                "name" => "xcalendar.cannot_connect_to_caldav_server",
                "vars" => ["n" => $calendarData->get("name")]]
            ) . " (2819938)");
        }

        return [
            self::createClient(
                $properties['caldav_server_url'],
                $properties['caldav_username'],
                $properties['caldav_password']
            ),
            $properties["caldav_calendar_url"],
            $calendarData,
        ];
    }

    public static function verifyProperties($properties): bool
    {
        return is_array($properties) &&
            !empty($properties['caldav_server_url']) &&
            !empty($properties['caldav_calendar_url']) &&
            !empty($properties['caldav_username']) &&
            !empty($properties['caldav_password']);
    }

    public static function ensureProperties($properties): array
    {
        is_array($properties) || ($properties = []);
        isset($properties['caldav_server_url']) || ($properties['caldav_server_url'] = '');
        isset($properties['caldav_calendar_url']) || ($properties['caldav_calendar_url'] = '');
        isset($properties['caldav_username']) || ($properties['caldav_username'] = '');
        isset($properties['caldav_password']) || ($properties['caldav_password'] = '');

        return $properties;
    }

    /**
     * Makes a simple call to the server to verify the username and password.
     *
     * @param $serverUrl
     * @param $username
     * @param $password
     * @return bool
     */
    public static function checkPassword($serverUrl, $username, $password): bool
    {
        try {
            $result = self::createClient($serverUrl, $username, $password)
                ->propFind("", ['{DAV:}current-user-principal']);

            return !empty($result['{DAV:}current-user-principal'][0]['value']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retrieves events from the calendar server and converts them to a usable array format.
     *
     * @param $calendarId
     * @param string $startTime
     * @param string $endTime
     * @return array
     * @throws \Exception
     */
    public static function getEvents($calendarId, string $startTime, string $endTime): array
    {
        list($client, $calendarUrl, $calendarData) = self::create($calendarId);
        $calendarName = $calendarData->get('name');
        $startTime = date("Ymd\THis\Z", strtotime($startTime));
        $endTime = date("Ymd\THis\Z", strtotime($endTime));

        $response = $client->request(
            "REPORT",
            $calendarUrl,
            "<c:calendar-query xmlns:d='DAV:' xmlns:c='urn:ietf:params:xml:ns:caldav'>
                <d:prop>
                    <d:getetag />
                    <c:calendar-data />
                </d:prop>
                <c:filter>
                    <c:comp-filter name='VCALENDAR'>
                        <c:comp-filter name='VEVENT'>
                            <c:time-range start='$startTime' end='$endTime' />
                        </c:comp-filter>
                    </c:comp-filter>
                </c:filter>                
            </c:calendar-query>",
            ["Depth" => "1", "Content-Type" => "application/xml; charset=utf-8"]
        );

        // create the resulting events array
        $veventArray = [];
        $hrefString = "";

        try {
            $responseItems = self::processResponse($response);
        } catch (\Exception $e) {
            throw new \Exception("Cannot retrieve events for calendar: $calendarName. " .
                $e->getMessage() . " (299384)");
        }

        foreach ($responseItems as $item) {
            if (!empty($item['value']) && ($href = $item['value']->getHref())) {
                $properties = $item['value']->getResponseProperties();
                $vcalendar = $properties[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"] ?? false;
                $etag = $properties[200]["{DAV:}getetag"] ?? "";

                if ($vcalendar) {
                    $veventArray[$href] = ["vcalendar" => $vcalendar, "etag" => $etag];
                } else if ($etag && substr($href, -4) == ".ics") {
                    $hrefString .= "<d:href>$href</d:href>";
                }
            }
        }

        // some calendars, like zoho, don't return vcalendar in the previous step, we need to run another request, passing
        // all the events hrefs to get their vcalendars
        if (!empty($hrefString)) {
            $response = $client->request(
                "REPORT",
                $calendarUrl,
                "<c:calendar-multiget xmlns:d='DAV:' xmlns:c='urn:ietf:params:xml:ns:caldav'>
                    <d:prop>
                        <d:getetag/>
                        <c:calendar-data></c:calendar-data>
                    </d:prop>
                    $hrefString
                </c:calendar-multiget>",
                ["Depth" => "1", "Content-Type" => "application/xml; charset=utf-8"]
            );

            try {
                $responseItems = self::processResponse($response);
            } catch (\Exception $e) {
                throw new \Exception("Cannot retrieve events for calendar: $calendarName. " .
                    $e->getMessage() . " (299385)");
            }

            foreach ($responseItems as $item) {
                if (!empty($item['value']) && ($href = $item['value']->getHref())) {
                    $properties = $item['value']->getResponseProperties();
                    $vcalendar = $properties[200]["{urn:ietf:params:xml:ns:caldav}calendar-data"] ?? false;
                    $etag = $properties[200]["{DAV:}getetag"] ?? "";

                    if ($vcalendar) {
                        $veventArray[$href] = ["vcalendar" => $vcalendar, "etag" => $etag];
                    }
                }
            }
        }

        // convert the vcalendar items to event arrays that we need to display in the frontend
        $event = new Event();
        $result = [];

        // get the current user's emails (login email and identity emails)
        $userEmails = Utils::getUserEmails();
        $categories = Event::getCategories(true);
        $useBorders = xrc()->config->get("xcalendar_event_border", Event::getDefaultSettings()['event_border']);
        $readonly = $calendarData->getProperty("caldav_readonly");

        foreach ($veventArray as $href => $item) {
            foreach ($event->vEventToDataArray((string)$item['vcalendar'], false) as $eventArray) {
                if (!empty($eventArray['title'])) {
                    // add repeated events
                    $eventArray['repeat_rule'] = $eventArray['repeat_rule_orig'];
                    $array = array_merge([$eventArray], $event->getRepeatedEvents($eventArray, $startTime, $endTime));

                    if ($useBorders && array_key_exists($eventArray['category'], $categories)) {
                        $borderColor = $categories[$eventArray['category']];
                    } else {
                        $borderColor = "transparent";
                    }

                    foreach ($array as $val) {
                        $data = [
                            "href" => $href,
                            "id" => $val['uid'],
                            "etag" => $item['etag'],
                            "type" => Calendar::CALDAV,
                            "calendar" => $calendarData->get("name"),
                            "calendar_id" => $calendarId,
                            "title" => $val['title'],
                            "start" => $val['start'],
                            "end" => $val['end'],
                            "allDay" => (bool)(int)$val['all_day'],
                            "description" => $val['description'] ?? "",
                            "link" => $val['url'] ?? "",
                            "location" => $val['location'] ?? "",
                            "backgroundColor" => $calendarData->get("bg_color"),
                            "textColor" => $calendarData->get("tx_color"),
                            "borderColor" => $borderColor,
                            "has_attendees" => !empty($val['attendees']),
                            "attendance" => [0, 0, 0, 0, 0],
                            "vcalendar" => (string)$item['vcalendar'],
                            "canEdit" => !$readonly,
                            "editable" => !$readonly,
                        ];

                        // add attendance information (will be used in preview)
                        foreach ($val['attendees'] as $attendee) {
                            $data['attendance'][$attendee['status'] ?? 0]++;

                            // if current user is an attendee show the yes/no/maybe/more response options
                            if (in_array($attendee['email'], $userEmails)) {
                                $data['show_attendance_response'] = true;
                                $data['attendance_status'] = (int)$attendee['status'];
                            }
                        }

                        $result[] = $data;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public static function removeEvent($calendarId, $href)
    {
        list($client) = self::create($calendarId);
        $response = $client->request("DELETE", $href);
        self::processResponse($response);
    }

    /**
     * @throws \Exception
     */
    public static function saveEvent($calendarId, array $data)
    {
        $rcmail = xrc();

        try {
            list($client, $calendarUrl) = self::create($calendarId);

            $data['repeat_rule'] = empty($data['repeat_rule']) ? "" : EventData::encodeRRule($data['repeat_rule']);

            $vevent = EventData::createVEvent($data);
            $timezoneString = "";

            // if not all day, extract timezones to add to vcalendar text
            if (empty($data['all_day'])) {
                Timezone::extractZonesFromVEvent($vevent, $rcmail->config->get("timezone", "UTC"), $timezoneStart, $timezoneEnd);
                $timezoneString = Timezone::getVTimezone($timezoneStart) .
                ($timezoneStart != $timezoneEnd) ? Timezone::getVTimezone($timezoneEnd) : "";
            }

            // href and etag will be set when editing events; href might be different from uid, that's why we keep track of href throughout
            // the life of the event; etag is needed when editing event; when creating event we use a newly created uid as href -
            // uid is generated when creating EventData()
            $response = $client->request(
                "PUT",
                $data['href'] ?: $calendarUrl . $data['uid'],
                Event::wrapInVCalendar($vevent, $timezoneString),
                [
                    "Content-Type" => "text/calendar; charset=utf-8",
                    "If-Match" => $data['etag'],
                ]
            );


            self::processResponse($response);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception(
                $rcmail->gettext("xcalendar.caldav_client_error_save_event") . ($message ? " ($message)" : "")
            );
        }
    }

    /**
     * Connects to the CalDAV server and returns the list of available calendars. This is a static function that doesn't require
     * $calendarUrl, since at this point in time we don't have it.
     *
     * @param string $serverUrl
     * @param string $username
     * @param string $password
     * @return array
     * @throws \Exception
     */
    public static function findCalendars(string $serverUrl, string $username, string $password): array
    {
        $calendars = [];
        $userId = xrc()->get_user_id();
        $client = self::createClient($serverUrl, $username, $password);

        // get the principal url
        $result = $client->propFind("", ["{DAV:}current-user-principal"]);

        if (!empty($result["{DAV:}current-user-principal"][0]['value'])) {
            // get the calendar url
            $result = $client->propFind(
                $result["{DAV:}current-user-principal"][0]['value'],
                ["{urn:ietf:params:xml:ns:caldav}calendar-home-set"]
            );

            if (!empty($result["{urn:ietf:params:xml:ns:caldav}calendar-home-set"][0]['value'])) {
                // get the available calendars
                $url = $result["{urn:ietf:params:xml:ns:caldav}calendar-home-set"][0]['value'];
                $properties = [
                    "{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set",
                    "{http://calendarserver.org/ns/}getctag",
                    "{http://calendarserver.org/ns/}current-user-privilege-set",
                    "{DAV:}displayname",
                    "{DAV:}acl",
                ];

                try {
                    $result = $client->propFind($url, $properties, 1);
                } catch (\Exception $e) {
                    // if getting 501 Not Implemented, try running without ACL (ACL is used as a backup property for
                    // determining whether the calendar is read-only in isCalendarReadOnly() below). We don't know which
                    // property is not supported, but ACL is the likely candidate (it's not supported on SOGo)
                    if ($e->getCode() == 501) {
                        $result = $client->propFind($url, array_diff($properties, ['{DAV:}acl']), 1);
                    } else {
                        Utils::logError($e->getMessage() . " (839923)");
                        throw $e;
                    }
                }

                // iterate and collect the calendars into an array
                foreach ($result as $calendarUrl => $data) {
                    if (!empty($data["{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set"][0])) {
                        $array = $data["{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set"][0];

                        if (empty($array['name']) ||
                            empty($array['attributes']['name']) ||
                            empty($data["{DAV:}displayname"]) ||
                            $array['name'] != "{urn:ietf:params:xml:ns:caldav}comp" ||
                            $array['attributes']['name'] != "VEVENT"
                        ) {
                            continue;
                        }

                        $uniqueId = md5(self::resolveServerUrl([
                            "caldav_server_url" => $serverUrl,
                            "caldav_calendar_url" => $calendarUrl
                        ]));

                        // check if the calendar is already added and disable the checkbox if it is
                        $disabled = xdb()->row(
                            "xcalendar_calendars",
                            ["user_id" => $userId, "type" => Calendar::CALDAV, "url" => $uniqueId]
                        );

                        $calendars[] = [
                            "id" => "caldav-calendar-$uniqueId",
                            "url" => $calendarUrl,
                            "name" => $data["{DAV:}displayname"],
                            "ctag" => $data["{http://calendarserver.org/ns/}getctag"] ?? false,
                            "checked" => !$disabled,
                            "disabled" => $disabled,
                            "readonly" => self::isCalendarReadOnly($client, $data, $calendarUrl),
                        ];
                    }
                }
            }
        }

        return $calendars;
    }

    /**
     * Checks if the calendar is read-only by trying to create/delete an event.
     *
     * @param $client
     * @param array $data
     * @param string $calendarUrl
     * @return bool
     */
    public static function isCalendarReadOnly($client, array $data, string $calendarUrl): bool
    {
        // check if privilege set exists and if it grants write rights
        if (!empty($data["{DAV:}current-user-privilege-set"])) {
            return !self::findPropertyName($data["{DAV:}current-user-privilege-set"], "{DAV:}write");
        }

        // if not, check if acl exists and grants write rights
        if (!empty($data["{DAV:}acl"])) {
            return !self::findPropertyName($data["{DAV:}acl"], "{DAV:}write");
        }

        // if not, try creating a test event
        $readonly = true;
        $eventUid = "rcp_test_" . Utils::uuid();
        $eventUrl = $calendarUrl . $eventUid . ".ics";

        try {
            // create a test event
            $response = $client->request(
                "PUT",
                $eventUrl,
                "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:$eventUid\r\nSUMMARY:Test\r\n".
                "DTSTART:20200101T000000Z\r\nDTEND:20200101T010000Z\r\nEND:VEVENT\r\nEND:VCALENDAR",
                ["Content-Type" => "text/calendar; charset=utf-8",]
            );
            self::processResponse($response);
            $readonly = false;

            // try deleting the test event by its url, if it fails, we'll try by href (event's url and href might not be the same)
            $response = $client->request("DELETE", $eventUrl);
            self::processResponse($response);
        } catch (\Exception $e) {
            if (!$readonly) {
                // if the event has been created, but deleting by url has failed, retrieve all events, find the test event
                // get its href, and try deleting by href
                foreach ($client->propFind($calendarUrl, ["{DAV:}href"], 1) as $href => $val) {
                    if (strpos($href, $eventUid) !== false) {
                        $client->request("DELETE", $href);
                        break;
                    }
                }
            }
        }

        return $readonly;
    }

    /**
     * Iterates a sabredav property array looking for a value of the name key. Returns true if the name is found.
     *
     * @param array $array
     * @param string $name
     * @return bool
     */
    public static function findPropertyName(array $array, string $name): bool
    {
        foreach ($array as $item) {
            if (!empty($item['name'])) {
                if ($item['name'] == $name) {
                    return true;
                }

                if (!empty($item['value']) && is_array($item['value'])) {
                    if (self::findPropertyName($item['value'], $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function resolveServerUrl(array $properties): string
    {
        if (empty($properties['caldav_server_url']) || empty($properties['caldav_calendar_url'])) {
            return "";
        }

        try {
            return \Sabre\Uri\resolve($properties['caldav_server_url'], $properties['caldav_calendar_url']);
        } catch (\Exception $e) {
            return $properties['caldav_server_url'] . $properties['caldav_calendar_url'];
        }
    }

    /**
     * @throws \Exception
     */
    protected static function processResponse(array $response): array
    {
        // parse the body regardless of whether status reports error or not
        if (!isset($response['body'])) {
            throw new \Exception("Invalid CalDAV server response");
        }

        if (empty($response['body'])) {
            $data = [];
        } else {
            try {
                $xml = new XmlService();
                $xml->elementMap['{DAV:}response'] = "Sabre\DAV\Xml\Element\Response";
                $data = $xml->parse($response['body']);
            } catch (\Exception $e) {
                throw new \Exception("Cannot parse CalDAV server response");
            }

            if (!is_array($data)) {
                $data = [];
            }
        }

        // if error, get the error message: first try "message" and if it doesn't exist, try "exception"
        if (empty($response['statusCode']) || $response['statusCode'] < 200 || $response['statusCode'] > 207) {
            $message = "";

            foreach ($data as $item) {
                if (!empty($item['name']) && !empty($item['value']) && $item['name'] == "{http://sabredav.org/ns}message") {
                    $message = $item['value'];
                }
            }

            if (empty($message)) {
                foreach ($data as $item) {
                    if (!empty($item['name']) && !empty($item['value']) && $item['name'] == "{http://sabredav.org/ns}exception") {
                        $message = $item['value'];
                    }
                }
            }

            if (empty($message)) {
                foreach ($data as $item) {
                    if (!empty($item['value']) && is_array($item['value'])) {
                        foreach ($item['value'] as $val) {
                            if (!empty($val['value']) && is_string($val['value'])) {
                                $message .= $val['value'] . " / ";
                            }
                        }
                    }
                }
            }

            throw new \Exception(trim($message));
        }

        return $data;
    }

    protected static function createClient($serverUrl, $username, $password): Client
    {
        $rcmail = xrc();
        $username = substr(trim((string)$username), 0, 250);
        $password = substr((string)$password, 0, 250);
        $settings = [
            'baseUri' => substr(trim((string)$serverUrl), 0, 250),
            'userName' => $username === '%u' ? $rcmail->get_user_name() : $username,
            'password' => $password === '%p' ? $rcmail->get_user_password() : $password,
        ];

        // if zlib support is available in curl, add encodings to curl header (identity + deflate + gzip)
        $info = curl_version();
        if ($info && $info['features'] & CURL_VERSION_LIBZ) {
            $settings['encoding'] = Client::ENCODING_ALL;
        }

        return new Client($settings);
    }
}