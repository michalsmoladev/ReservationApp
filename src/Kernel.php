<?php

namespace App;

use App\Core\Application\MessageBus\Attribute\AsMessageLocker;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->registerLockers($container);
        $this->registerValidators($container);
    }

    public function registerLockers(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerAttributeForAutoconfiguration(
            attributeClass: AsMessageLocker::class,
            configurator: static function (
                ChildDefinition $childDefinition,
                AsMessageLocker $attribute,
            ): void {
                $childDefinition->addTag('message.locker', get_object_vars($attribute));
            }
        );
    }

    public function registerValidators(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerAttributeForAutoconfiguration(
            attributeClass: AsMessageValidator::class,
            configurator: static function (
                ChildDefinition $childDefinition,
                AsMessageValidator $attribute,
            ): void {
                $childDefinition->addTag('message.validator', get_object_vars($attribute));
            }
        );
    }
}
