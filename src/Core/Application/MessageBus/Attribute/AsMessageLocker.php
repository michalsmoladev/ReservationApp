<?php

namespace App\Core\Application\MessageBus\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class AsMessageLocker
{
}