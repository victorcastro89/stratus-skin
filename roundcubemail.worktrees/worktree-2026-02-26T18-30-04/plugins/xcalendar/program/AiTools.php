<?php
namespace XCalendar;

/**
 * Roundcube Plus Calendar plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the file LICENSE for details.
 */

class AiTools
{
    public function get(array $context): array
    {
        return $context['page'] == 'xcalendar' ?
            array_merge($this->allPages(), $this->calendarPage()) :
            $this->allPages();
    }

    protected function allPages(): array
    {
        return [
            ['type' => 'function', 'function' => [
                'name' => 'get_calendars',
                'description' => 'Get calendars. Never display calendar IDs.',
                'parameters' => ['type' => 'object', 'properties' => (object)[]],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'get_calendar_events',
                'description' => 'Get vevents. Max 3 month span.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'start' => ['type' => 'string', 'description' => 'YYYY-MM-DD HH:MM:SS'],
                        'end'   => ['type' => 'string', 'description' => 'YYYY-MM-DD HH:MM:SS'],
                    ],
                    'required' => ['start', 'end'],
                ],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'create_calendar_event',
                'description' => 'If no start time given, ask in plain text first. '.
                    'If no end time given, assume 1 hour. '.
                    'If unsure if event already exists, run get_calendar_events()',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'vcalendar' => [
                            'type' => 'string',
                            'description' => 'iCalendar (RFC 5545) VCALENDAR text with a VEVENT. Always include timezone.',
                        ],
                        'calendar_id' => [
                            'type' => 'integer',
                            'description' => 'Omit to add event to default calendar. Use get_calendars() to get id.'
                        ],
                    ],
                    'required' => ['vcalendar'],
                ],
            ]],
            ['type' => 'function', 'function' => [
                'name' => 'edit_calendar_event',
                'description' => 'Edit and save calendar event. Use get_calendar_events() to find VCALENDAR to edit.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'vcalendar' => [
                            'type' => 'string',
                            'description' => 'Modified iCalendar (RFC 5545) VCALENDAR text with a VEVENT',
                        ],
                    ],
                    'required' => ['vcalendar'],
                ],
            ]],
        ];
    }

    protected function calendarPage(): array
    {
        return [
            ['type' => 'function', 'function' => [
                'name' => 'enable_calendar',
                'description' => 'Enable/disable calendars',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Always use calendar id from get_calendars()'],
                        'enable' => ['type' => 'boolean', 'description' => 'True to enable, false to disable'],
                    ],
                    'required' => ['id', 'enable'],
                ],
            ]],
        ];
    }
}
