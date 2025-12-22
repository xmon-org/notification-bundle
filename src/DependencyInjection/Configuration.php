<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('xmon_notification');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                // Channel configuration
                ->arrayNode('channels')
                    ->addDefaultsIfNotSet()
                    ->children()
                        // Email channel
                        ->arrayNode('email')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('from')->isRequired()->end()
                                ->scalarNode('from_name')->defaultValue('')->end()
                            ->end()
                        ->end()
                        // Telegram channel
                        ->arrayNode('telegram')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('bot_token')->isRequired()->end()
                                ->scalarNode('default_chat_id')->defaultNull()->end()
                            ->end()
                        ->end()
                        // In-App channel
                        ->arrayNode('in_app')
                            ->canBeEnabled()
                            ->children()
                                ->enumNode('storage')
                                    ->values(['session', 'doctrine'])
                                    ->defaultValue('session')
                                ->end()
                                ->integerNode('max_notifications')
                                    ->defaultValue(50)
                                    ->min(1)
                                    ->max(500)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                // Messenger configuration
                ->arrayNode('messenger')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('transport')->defaultValue('async')->end()
                    ->end()
                ->end()
                // Defaults
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('channels')
                            ->scalarPrototype()->end()
                            ->defaultValue(['email'])
                        ->end()
                        ->enumNode('priority')
                            ->values(['low', 'normal', 'high', 'urgent'])
                            ->defaultValue('normal')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
