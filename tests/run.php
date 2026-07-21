<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Company/CompanyManagementTest.php';
require_once __DIR__ . '/Reservation/ReservationFlowTest.php';
require_once __DIR__ . '/User/UserAccessAndActivationTest.php';

use App\Tests\Company\CompanyManagementTest;
use App\Tests\Reservation\ReservationFlowTest;
use App\Tests\User\UserAccessAndActivationTest;

$tests = [
    'company management regression suite' => static fn () => (new CompanyManagementTest())->run(),
    'reservation flow regression suite' => static fn () => (new ReservationFlowTest())->run(),
    'user access and activation regression suite' => static fn () => (new UserAccessAndActivationTest())->run(),
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
