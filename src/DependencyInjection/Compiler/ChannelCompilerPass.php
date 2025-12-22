<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Xmon\NotificationBundle\Channel\ChannelRegistry;

class ChannelCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ChannelRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(ChannelRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('xmon_notification.channel');

        $channels = [];
        foreach ($taggedServices as $id => $tags) {
            $channels[] = new Reference($id);
        }

        $registryDefinition->setArgument('$channels', $channels);
    }
}
