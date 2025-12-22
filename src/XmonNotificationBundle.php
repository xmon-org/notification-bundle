<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Xmon\NotificationBundle\DependencyInjection\Compiler\ChannelCompilerPass;

class XmonNotificationBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ChannelCompilerPass());
    }

    public function getPath(): string
    {
        return realpath(\dirname(__DIR__)) ?: \dirname(__DIR__);
    }
}
