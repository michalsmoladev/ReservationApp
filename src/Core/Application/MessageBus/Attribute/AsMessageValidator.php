<?php

declare(strict_types=1);

namespace App\Core\Application\MessageBus\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsMessageValidator
{
}
