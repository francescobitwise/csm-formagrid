<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Course;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class CourseScheduleService
{
    public const TIMEZONE = 'Europe/Rome';

    /** @var array<int, string> */
    public const WEEKDAY_LABELS = [
        1 => 'Lun',
        2 => 'Mar',
        3 => 'Mer',
        4 => 'Gio',
        5 => 'Ven',
        6 => 'Sab',
        7 => 'Dom',
    ];

    public function isOpenFor(Course $course, User $user, ?CarbonInterface $at = null): bool
    {
        if ($user->isStaffMember()) {
            return true;
        }

        if (! (bool) ($course->schedule_enabled ?? false)) {
            return true;
        }

        $now = $this->nowInScheduleTz($at);

        if ($this->isWithinDaySchedule($course, $now)) {
            return true;
        }

        if (
            (bool) ($course->night_schedule_enabled ?? false)
            && (bool) ($user->night_hours_override ?? false)
            && $this->isWithinNightSchedule($course, $now)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     weekdays: list<int>,
     *     weekdays_label: string,
     *     opens_at: ?string,
     *     closes_at: ?string,
     *     day_range_label: ?string,
     *     night_enabled: bool,
     *     night_opens_at: ?string,
     *     night_closes_at: ?string,
     *     night_range_label: ?string,
     *     summary_label: string
     * }
     */
    public function scheduleSummaryFor(Course $course): array
    {
        $weekdays = $this->normalizedWeekdays($course->schedule_weekdays);
        $opens = $this->formatTime($course->schedule_opens_at);
        $closes = $this->formatTime($course->schedule_closes_at);
        $nightOpens = $this->formatTime($course->night_opens_at);
        $nightCloses = $this->formatTime($course->night_closes_at);
        $enabled = (bool) ($course->schedule_enabled ?? false);
        $nightEnabled = (bool) ($course->night_schedule_enabled ?? false);

        $weekdaysLabel = $weekdays === []
            ? ''
            : implode(', ', array_map(
                fn (int $d) => self::WEEKDAY_LABELS[$d] ?? (string) $d,
                $weekdays
            ));

        $dayRangeLabel = ($opens !== null && $closes !== null)
            ? "{$opens}–{$closes}"
            : null;

        $nightRangeLabel = ($nightOpens !== null && $nightCloses !== null)
            ? "{$nightOpens}–{$nightCloses}"
            : null;

        $parts = [];
        if ($enabled && $weekdaysLabel !== '' && $dayRangeLabel !== null) {
            $parts[] = "{$weekdaysLabel} {$dayRangeLabel}";
        }
        if ($enabled && $nightEnabled && $nightRangeLabel !== null) {
            $parts[] = "Notturno (override): {$nightRangeLabel}";
        }

        return [
            'enabled' => $enabled,
            'weekdays' => $weekdays,
            'weekdays_label' => $weekdaysLabel,
            'opens_at' => $opens,
            'closes_at' => $closes,
            'day_range_label' => $dayRangeLabel,
            'night_enabled' => $nightEnabled,
            'night_opens_at' => $nightOpens,
            'night_closes_at' => $nightCloses,
            'night_range_label' => $nightRangeLabel,
            'summary_label' => $parts === [] ? '' : implode(' · ', $parts),
        ];
    }

    public function closedMessage(Course $course): string
    {
        $summary = $this->scheduleSummaryFor($course);
        if ($summary['summary_label'] !== '') {
            return 'Corso chiuso. Orari di accesso: '.$summary['summary_label'].'.';
        }

        return 'Corso chiuso in questo momento.';
    }

    private function isWithinDaySchedule(Course $course, Carbon $now): bool
    {
        $weekdays = $this->normalizedWeekdays($course->schedule_weekdays);
        if ($weekdays === []) {
            return false;
        }

        $isoDay = (int) $now->isoWeekday();
        if (! in_array($isoDay, $weekdays, true)) {
            return false;
        }

        return $this->isTimeInRange(
            $now,
            $this->normalizeTimeString($course->schedule_opens_at),
            $this->normalizeTimeString($course->schedule_closes_at),
        );
    }

    private function isWithinNightSchedule(Course $course, Carbon $now): bool
    {
        return $this->isTimeInRange(
            $now,
            $this->normalizeTimeString($course->night_opens_at),
            $this->normalizeTimeString($course->night_closes_at),
        );
    }

    private function isTimeInRange(Carbon $now, ?string $opens, ?string $closes): bool
    {
        if ($opens === null || $closes === null) {
            return false;
        }

        $current = $now->format('H:i:s');

        if ($closes <= $opens) {
            // Overnight: e.g. 22:00–06:00
            return $current >= $opens || $current < $closes;
        }

        return $current >= $opens && $current < $closes;
    }

    private function nowInScheduleTz(?CarbonInterface $at = null): Carbon
    {
        if ($at === null) {
            return Carbon::now(self::TIMEZONE);
        }

        return Carbon::instance($at)->timezone(self::TIMEZONE);
    }

    /**
     * @param  mixed  $raw
     * @return list<int>
     */
    private function normalizedWeekdays(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $days = [];
        foreach ($raw as $day) {
            $n = (int) $day;
            if ($n >= 1 && $n <= 7) {
                $days[] = $n;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }

    private function normalizeTimeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('H:i:s');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        // MySQL time / form "HH:MM" or "HH:MM:SS"
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        }

        try {
            return Carbon::parse($str, self::TIMEZONE)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatTime(mixed $value): ?string
    {
        $normalized = $this->normalizeTimeString($value);
        if ($normalized === null) {
            return null;
        }

        return substr($normalized, 0, 5);
    }
}
