<?php

declare(strict_types=1);

namespace GSteel\GoogleTimezone;

use DateTimeInterface;

interface Client
{
    /**
     * Fetch timezone information for the given coordinates
     * The optional language parameter can be used to localise the name of the found timezone.
     *
     * @link https://developers.google.com/maps/faq#languagesupport
     *
     * @param DateTimeInterface     $referenceDate This date is used to determine whether DST is in effect at the
     *                                             given time.
     * @param non-empty-string|null $language      Optional language code for localisation
     */
    public function fetch(
        Coordinates $coordinates,
        DateTimeInterface $referenceDate,
        string|null $language = null,
    ): Result;
}
