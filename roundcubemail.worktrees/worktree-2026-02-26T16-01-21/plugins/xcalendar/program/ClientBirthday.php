<?php
namespace XCalendar;

use Sabre\VObject\Reader;

/**
 * There can be only one birthday calendar per user.
 */
class ClientBirthday
{
    public static function enabled(): bool
    {
        return !empty(xrc()->config->get("xcalendar_birthday_calendar_enabled", true));
    }

    public static function getId(): ?string
    {
        if (!self::enabled()) {
            return null;
        }

        return xdb()->value("id", "xcalendar_calendars", ["user_id" => xrc()->get_user_id(), "type" => Calendar::BIRTHDAY]);
    }

    public static function getEvents(string $startTime, string $endTime): array
    {
        $rcmail = xrc();

        if (!self::enabled()) {
            return [];
        }

        if (!($calendarData = CalendarData::load(self::getId()))) {
            return [];
        }

        try {
            $startDateTime = new \DateTime($startTime);
            $endDateTime = new \DateTime($endTime);
            $startYear = $startDateTime->format("Y");
        } catch (\Exception $e) {
            return [];
        }

        $rcubeContacts = new \rcube_contacts($rcmail->db, $rcmail->get_user_id());
        $rcubeContacts->set_pagesize(9999);
        $contacts = $rcubeContacts->list_records(['name', 'birthday'], 0, true);
        $events = [];

        while ($contacts->count && ($contact = $contacts->next())) {
            if (empty($contact['birthday'][0])) {
                continue;
            }

            try {
                $datetime = new \DateTime($contact['birthday'][0]);
            } catch (\Exception $e) {
                continue;
            }

            $datetime->setDate($startYear, $datetime->format('m'), $datetime->format('d'));
            $date = $datetime->format("Y-m-d");

            if ($datetime < $startDateTime || $datetime > $endDateTime) {
                continue;
            }

            // find the first email
            $email = false;
            foreach ($contact as $key => $val) {
                if (strpos($key, "email") === 0) {
                    if (is_array($val) && !empty($val[0])) {
                        $email = $val[0];
                        break;
                    }
                }
            }

            $events[] = [
                "id" => 0,
                "type" => Calendar::BIRTHDAY,
                "calendar" => $calendarData->get("name"),
                "calendar_id" => $calendarData->getId(),
                "title" => $rcmail->gettext(["name" => "xcalendar.birthday_event_title", "vars" => ["n" => $contact['name']]]),
                "start" => $date,
                "end" => $date,
                "allDay" => true,
                "email" => $email,
                "backgroundColor" => $calendarData->get("bg_color"),
                "textColor" => $calendarData->get("tx_color"),
            ];
        }

        return $events;
    }

}