<?php

declare(strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Support;

use Datetime;
use DateTimeZone;
use DateInterval;
use Exception;
use InvalidArgumentException;
use RuntimeException;

final class Chronos
{
    /**
     * Initial DateTime.
     *
     * @var DateTime|null
     */
    protected DateTime|null $date;

    /**
     * Default dateTime format.
     *
     * @var string
     */
    protected string $dateTimeFormat = "Y-m-d H:i:s";

    /**
     * Default date format.
     *
     * @var string
     */
    protected string $dateFormat = "Y-m-d";

    /**
     * Default datetime format.
     *
     * @var string
     */
    protected string $timeFormat = "H:i:s";

    /**
     * Default timezone.
     *
     * @var string
     */
    protected static string $timezone = "Europe/Budapest";

    /**
     * Second date to compare.
     *
     * @var DateInterval|null
     */
    protected DateInterval|null $travel = null;

    /**
     * Construct Chronos.
     *
     * @param Chronos|Datetime|int|string|null $date
     * @throws Exception
     */
    public function __construct(Chronos|DateTime|int|string $date = null)
    {
        // If it's a string date or null create a new datetime.
        if (is_string($date) || is_null($date)) {
            $this->date = new DateTime($date ?? "");
        }

        // If date is a timestamp convert it.
        else if (is_int($date)) {
            $this->date = (new DateTime)->setTimestamp($date);
         }

         // If it's a DateTime or Chronos
        else {
            $this->date = $date instanceof Chronos
            ? $date->getDateObject()
            : $date;
        }
    }

    /**
     * Initialize Chronos object statically.
     *
     * @param Chronos|Datetime|int|string|null $date
     * @return Chronos
     * @throws Exception
     */
    public static function date(Chronos|DateTime|int|string $date = null): Chronos
    {
        return new self($date);
    }

    /**
     * Set the default datetime format.
     *
     * @param string $format
     * @return Chronos
     */
    public function setDateTimeFormat(string $format): Chronos
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    /**
     * Set the default date format.
     *
     * @param string $format
     * @return Chronos
     */
    public function setDateFormat(string $format): Chronos
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * Set the default time format.
     *
     * @param string $format
     * @return Chronos
     */
    public function setTimeFormat(string $format): Chronos
    {
        $this->timeFormat = $format;

        return $this;
    }

    /**
     * Set the default timezone.
     *
     * @param string $timezone
     * @return Chronos
     * @throws InvalidArgumentException
     */
    public function setTimezone(string $timezone): Chronos
    {
        try {
            $zone = new DateTimeZone($timezone);

        } catch(Exception) {
            throw new InvalidArgumentException(
                sprintf("The requested timezone [%s] is not supported", $timezone)
            );
        }

        Chronos::$timezone = $timezone;
        $this->date->setTimezone($zone);

        return $this;
    }

    /**
     * Set the default timezone statically.
     *
     * @param string $timezone
     * @return void
     */
    public static function setChronosTimezone(string $timezone): void
    {
        try {
            $zone = new DateTimeZone($timezone);

        } catch(Exception) {
            throw new InvalidArgumentException(
                sprintf("The requested timezone [%s] is not supported", $timezone)
            );
        }

        Chronos::$timezone = $timezone;
    }

    /**
     * Return the DateTime object.
     *
     * @return DateTime
     */
    public function getDateObject(): DateTime
    {
        return $this->date;
    }

    /**
     * Get TimeStamp of DateTime.
     *
     * @return int|null
     */
    public function stamp(): ?int
    {
        return $this->date->getTimestamp();
    }

    /**
     * Get DateTime for the set date.
     *
     * @return string|null
     */
    public function getDateTime(): ?string
    {
        return $this->date->format($this->dateTimeFormat);
    }

    /**
     * Get Date for the set date.
     *
     * @return string|null
     */
    public function getDate(): ?string
    {
        return $this->date->format($this->dateFormat);
    }

    /**
     * Get Time for the set date.
     *
     * @return string|null
     */
    public function getTime(): ?string
    {
        return $this->date->format($this->timeFormat);
    }

    /**
     * Get Hour for the set date.
     *
     * @return string|null
     */
    public function getHour(): ?string
    {
        return $this->date->format("G");
    }

    /**
     * Get Minutes for the set date.
     *
     * @return string|null
     */
    public function getMinute(): ?string
    {
        return $this->date->format("i");
    }

    /**
     * Get Seconds for the set date.
     *
     * @return string|null
     */
    public function getSeconds(): ?string
    {
        return $this->date->format("s");
    }

    /**
     * Get Day name for the set date.
     *
     * @return string|null
     */
    public function getDayName(): ?string
    {
        return $this->date->format("l");
    }

    /**
     * Get Day name in short for the set date.
     *
     * @return string|null
     */
    public function getDayShortName(): ?string
    {
        return $this->date->format("D");
    }

    /**
     * Get Day number for the set date.
     *
     * @return string|null
     */
    public function getDayNumber(): ?string
    {
        return $this->date->format("d");
    }

    /**
     * Get Week number for the set date.
     *
     * @return string|null
     */
    public function getWeekNumber(): ?string
    {
        return $this->date->format("W");
    }

    /**
     * Get Month name for the set date.
     *
     * @return string|null
     */
    public function getMonthName(): ?string
    {
        return $this->date->format("F");
    }

    /**
     * Get Month name in short for the set date.
     *
     * @return string|null
     */
    public function getShortMonthName(): ?string
    {
        return $this->date->format("M");
    }

    /**
     * Get Month number for the set date.
     *
     * @return string|null
     */
    public function getMonthNumber(): ?string
    {
        return $this->date->format("m");
    }

    /**
     * Get Year for the set date.
     *
     * @return string|null
     */
    public function getYear(): ?string
    {
        return $this->date->format("Y");
    }

    /**
     * Add Days to currently set date.
     *
     * @param int $days
     * @return Chronos
     * @throws Exception
     */
    public function addDays(int $days): Chronos
    {
        $this->date->add(new DateInterval("P" . $days . "D"));

        return $this;
    }

    /**
     * Add Minutes to currently set date.
     *
     * @param int $minutes
     * @return Chronos
     * @throws Exception
     */
    public function addMinutes(int $minutes): Chronos
    {
        $this->date->add(new DateInterval("PT" . $minutes . "M"));

        return $this;
    }

    /**
     * Add Seconds to currently set date.
     *
     * @param int $seconds
     * @return Chronos
     * @throws Exception
     */
    public function addSeconds(int $seconds): Chronos
    {
        $this->date->add(new DateInterval("PT" . $seconds . "S"));

        return $this;
    }

    /**
     * Subtract Days to currently set date.
     *
     * @param int $days
     * @return Chronos
     * @throws Exception
     */
    public function subDays(int $days): Chronos
    {
        $this->date->sub(new DateInterval("P" . $days . "D"));

        return $this;
    }

    /**
     * Subtract Minutes to currently set date.
     *
     * @param int $minutes
     * @return Chronos
     * @throws Exception
     */
    public function subMinutes(int $minutes): Chronos
    {
        $this->date->sub(new DateInterval("PT" . $minutes . "M"));

        return $this;
    }

    /**
     * Subtract Seconds to currently set date.
     *
     * @param int $seconds
     * @return Chronos
     * @throws Exception
     */
    public function subSeconds(int $seconds): Chronos
    {
        $this->date->sub(new DateInterval("PT" . $seconds . "S"));

        return $this;
    }

    /**
     * Set an endpoint to the initial date to compare.
     * Ex. Chronos::date()->travel('2021-01-03 17:13:00')->daysPassed();
     *
     * @param Chronos|DateTime|string|int|null $date
     * @return Chronos
     * @throws Exception
     */
    public function travel(Chronos|DateTime|string|int|null $date): Chronos
    {
        // If it's already a datetime save it.
        if ($date instanceof DateTime) {
            $travel = $date;
        }

        // If it's a string date or null create a new datetime.
        elseif (is_string($date) || is_null($date)) {
            $travel = new DateTime($date ?? "");
        }

        // If date is a timestamp convert it.
        elseif(is_int($date)) {
            $travel = (new DateTime())
                ->setTimestamp($date);
        }

        // If its Chronos or DateTime
        else {
            $travel = $date->getDateObject();
        }

        $travel->setTimezone(new DateTimeZone(Chronos::$timezone));
        $this->travel = $travel->diff($this->date);

        return $this;
    }

    /**
     * Get days passed between 2 date endpoints.
     * This will return 0 if travel date is in the future.
     *
     * @return int
     * @throws RuntimeException
     */
    public function daysPassed(): int
    {
        $this->ensureTravelDateIsSet();

        $time = $this->travel->y*365 + $this->travel->m*31 + $this->travel->d;

        return $this->travel->invert ? 0 : $time;
    }

    /**
     * Get days difference between 2 date endpoints.
     * This will return negative days if travel is in the past.
     *
     * @return int
     * @throws RuntimeException
     */
    public function differenceInDays(): int
    {
        $this->ensureTravelDateIsSet();

        $days = $this->travel->y*365 + $this->travel->m* 31 + $this->travel->d;

        return $this->travel->invert ? $days : -$days;
    }

    /**
     * Get hours passed between 2 date endpoints.
     *
     * @return int
     * @throws RuntimeException
     */
    public function hoursPassed(): int
    {
        $this->ensureTravelDateIsSet();

        $time = $this->daysPassed()*24 + $this->travel->h;

        return $this->travel->invert ? 0 : $time;
    }

    /**
     * Get hours difference between 2 date endpoints.
     * This will return negative days if travel is in the past.
     *
     * @return int
     * @throws RuntimeException
     */
    public function differenceInHours(): int
    {
        $this->ensureTravelDateIsSet();

        $days = $this->differenceInDays()*24;

        return $this->travel->invert ? $days+$this->travel->h : $days-$this->travel->h;
    }

    /**
     * Get minutes passed between 2 date endpoints.
     *
     * @return int
     * @throws RuntimeException
     */
    public function minutesPassed(): int
    {
        $this->ensureTravelDateIsSet();

        $time = $this->hoursPassed()*60 + $this->travel->i;

        return $this->travel->invert ? 0 : $time;
    }

    /**
     * Get minutes difference between 2 date endpoints.
     * This will return negative days if travel is in the past.
     *
     * @return int
     * @throws RuntimeException
     */
    public function differenceInMinutes(): int
    {
        $this->ensureTravelDateIsSet();

        $days = $this->differenceInHours()*60;

        return $this->travel->invert ? $days+$this->travel->i : $days-$this->travel->i;
    }

    /**
     * Get seconds passed between 2 date endpoints.
     *
     * @return int
     * @throws RuntimeException
     */
    public function secondsPassed(): int
    {
        $this->ensureTravelDateIsSet();

        $time = $this->minutesPassed()*60 + $this->travel->s;

        return $this->travel->invert ? 0 : $time;
    }

    /**
     * Get seconds difference between 2 date endpoints.
     * This will return negative days if travel is in the past.
     *
     * @return int
     * @throws RuntimeException
     */
    public function differenceInSeconds(): int
    {
        $this->ensureTravelDateIsSet();

        $days = $this->differenceInMinutes()*60;

        return $this->travel->invert ? $days+$this->travel->s : $days-$this->travel->s;
    }

    /**
     * Check if date difference is in the future.
     *
     * @return bool
     */
    public function isInFuture(): bool
    {
        return $this->secondsPassed() === 0;
    }

    /**
     * Check if date difference is in the past.
     *
     * @return bool
     */
    public function isInPast(): bool
    {
        return $this->secondsPassed() > 0;
    }

    /**
     * Check if we have a travel date before continuing.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function ensureTravelDateIsSet(): void
    {
        if (is_null($this->travel)) {
            throw new RuntimeException(
                "Travel date object is empty ( You must set end date with travel() before calling daysPassed()"
            );
        }
    }
}
