<?php

declare(strict_types=1);

/*
 * This file is part of the Aeon time management framework for PHP.
 *
 * (c) Norbert Orzechowicz <contact@norbert.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aeon\Calendar\Gregorian\Holidays;

use Aeon\Calendar\Exception\HolidayException;
use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 */
final class HolidayName
{
    /**
     * @var array<HolidayLocaleName>
     */
    private array $localeHolidayNames;

    public function __construct(HolidayLocaleName ...$localeHolidayNames)
    {
        Assert::greaterThan(\count($localeHolidayNames), 0, "Holiday should have name in at least one locale.");
        $this->localeHolidayNames = $localeHolidayNames;
    }

    public function name(?string $locale = null) : string
    {
        if ($locale === null) {
            return \current($this->localeHolidayNames)->name();
        }

        $localeNames = (array) \array_filter(
            $this->localeHolidayNames,
            fn (HolidayLocaleName $localeHolidayName) : bool => $localeHolidayName->in($locale)
        );

        if (!\count($localeNames)) {
            throw new HolidayException(\sprintf("Holiday \"%s\" does not have name in %s locale", $this->name(), $locale));
        }

        return \current($localeNames)->name();
    }

    /**
     * @return array<int, string>
     */
    public function locales() : array
    {
        return \array_values(\array_map(
            function (HolidayLocaleName $holidayLocaleName) : string {
                return $holidayLocaleName->locale();
            },
            $this->localeHolidayNames
        ));
    }
}
