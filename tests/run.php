<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Reservation/ReservationFlowTest.php';

use App\Tests\Reservation\ReservationFlowTest;

$tests = [
    'reservation flow regression suite' => static fn () => (new ReservationFlowTest())->run(),
];

$failures = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        echo sprintf("PASS %s\n", $name);
    } catch (Throwable $throwable) {
        ++$failures;
        fwrite(STDERR, sprintf("FAIL %s: %s\n", $name, $throwable->getMessage()));
    }
}

exit($failures > 0 ? 1 : 0);
