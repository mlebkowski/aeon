<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays;

use Aeon\Calendar\Exception\HolidayException;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Interval;
use Aeon\Calendar\Gregorian\TimePeriod;
use Aeon\Calendar\Holidays;
use Aeon\Calendar\Holidays\Holiday as AeonHoliday;
use Yasumi\Exception\ProviderNotFoundException;
use Yasumi\Holiday;
use Yasumi\Provider\AbstractProvider;
use Yasumi\Yasumi;

/**
 * @psalm-immutable
 */
final class YasumiHolidays implements Holidays
{
    /**
     * @phpstan-ignore-next-line
     *
     * @var array<int, AbstractProvider>
     */
    private array $yasumi;

    private string $providerClass;

    public function __construct(string $countryCode)
    {
        $this->yasumi = [];
        $this->providerClass = Providers::fromCountryCode($countryCode);
    }

    public function isHoliday(Day $day) : bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->yasumi($day->year()->number())->isHoliday($day->toDateTimeImmutable());
    }

    /**
     * @throws HolidayException
     * @throws \Aeon\Calendar\Exception\InvalidArgumentException
     * @throws \Yasumi\Exception\MissingTranslationException
     *
     * @return AeonHoliday[]
     */
    public function in(TimePeriod $period) : array
    {
        $holidays = [];

        foreach ($period->start()->year()->until($period->end()->year(), Interval::closed()) as $year) {
            foreach ($this->yasumi($year->number())->getHolidays() as $yasumiHoliday) {
                /** @psalm-suppress ImpureMethodCall */
                $holiday = new AeonHoliday(
                    Day::fromDateTime($yasumiHoliday),
                    new HolidayName(
                        new HolidayLocaleName('us', $yasumiHoliday->getName())
                    )
                );

                if ($holiday->day()->isAfterOrEqual($period->start()->day()) && $holiday->day()->isBeforeOrEqual($period->end()->day())) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }

    public function holidaysAt(Day $day) : array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return \array_values(
            \array_map(
                function (Holiday $holiday) : AeonHoliday {
                    /** @psalm-suppress ImpureMethodCall */
                    return new AeonHoliday(
                        Day::fromDateTime($holiday),
                        new HolidayName(
                            new HolidayLocaleName('us', $holiday->getName())
                        )
                    );
                },
                \array_filter(
                    $this->yasumi($day->year()->number())->getHolidays(),
                    function (Holiday $holiday) use ($day) : bool {
                        return Day::fromDateTime($holiday)->isEqual($day);
                    }
                )
            )
        );
    }

    /**
     * @param int $year
     *
     * @throws HolidayException
     *
     * @return AbstractProvider
     * @phpstan-ignore-next-line
     */
    private function yasumi(int $year) : AbstractProvider
    {
        if (\array_key_exists($year, $this->yasumi)) {
            return $this->yasumi[$year];
        }

        try {
            /**
             * @psalm-suppress InaccessibleProperty
             * @psalm-suppress ImpureMethodCall
             */
            $this->yasumi[$year] = Yasumi::create($this->providerClass, $year, 'en_US');

            return $this->yasumi[$year];
        } catch (ProviderNotFoundException | \ReflectionException $providerNotFoundException) {
            throw new HolidayException('Yasumi provider ' . $this->providerClass . ' does not exists', 0, $providerNotFoundException);
        }
    }
}
