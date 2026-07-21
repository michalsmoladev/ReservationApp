<?php

declare(strict_types=1);

namespace App\Company\Application\Exception;

use App\Core\Application\Exception\NamedException;

class CompanyNotFoundException extends NamedException
{
    public function getErrorMessage(): string
    {
        return self::COMPANY_NOT_FOUND_MESSAGE;
    }

    public function getErrorCode(): int
    {
        return self::COMPANY_NOT_FOUND_CODE;
    }
}
