<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Mailer\MailerInterface;
use Xmon\NotificationBundle\Channel\EmailChannel;
use Xmon\NotificationBundle\Service\NotificationService;

class XmonNotificationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        // Configure channels
        $this->configureChannels($container, $config['channels'] ?? []);

        // Configure messenger
        $this->configureMessenger($container, $config['messenger'] ?? []);

        // Configure defaults
        $this->configureDefaults($container, $config['defaults'] ?? []);
    }

    private function configureChannels(ContainerBuilder $container, array $channelsConfig): void
    {
        // Email channel
        if (isset($channelsConfig['email']['enabled']) && $channelsConfig['email']['enabled']) {
            if ($this->isMailerAvailable() && $container->hasDefinition(EmailChannel::class)) {
                $definition = $container->getDefinition(EmailChannel::class);
                $definition->setArgument('$from', $channelsConfig['email']['from']);
                $definition->setArgument('$fromName', $channelsConfig['email']['from_name'] ?? '');
            }
        }

        // Telegram channel
        if (isset($channelsConfig['telegram']['enabled']) && $channelsConfig['telegram']['enabled']) {
            // Configuration for TelegramChannel (Phase 2)
            $container->setParameter('xmon_notification.telegram.bot_token', $channelsConfig['telegram']['bot_token']);
            $container->setParameter('xmon_notification.telegram.default_chat_id', $channelsConfig['telegram']['default_chat_id'] ?? null);
        }

        // In-App channel
        if (isset($channelsConfig['in_app']['enabled']) && $channelsConfig['in_app']['enabled']) {
            // Configuration for InAppChannel (Phase 3)
            $container->setParameter('xmon_notification.in_app.storage', $channelsConfig['in_app']['storage'] ?? 'session');
            $container->setParameter('xmon_notification.in_app.max_notifications', $channelsConfig['in_app']['max_notifications'] ?? 50);
        }

        // Store all channel config
        $container->setParameter('xmon_notification.channels', $channelsConfig);
    }

    private function configureMessenger(ContainerBuilder $container, array $messengerConfig): void
    {
        $enabled = $messengerConfig['enabled'] ?? false;
        $transport = $messengerConfig['transport'] ?? 'async';

        $container->setParameter('xmon_notification.messenger.enabled', $enabled);
        $container->setParameter('xmon_notification.messenger.transport', $transport);
    }

    private function configureDefaults(ContainerBuilder $container, array $defaultsConfig): void
    {
        $defaultChannels = $defaultsConfig['channels'] ?? ['email'];
        $defaultPriority = $defaultsConfig['priority'] ?? 'normal';

        $container->setParameter('xmon_notification.defaults.channels', $defaultChannels);
        $container->setParameter('xmon_notification.defaults.priority', $defaultPriority);

        // Configure NotificationService with defaults
        if ($container->hasDefinition(NotificationService::class)) {
            $definition = $container->getDefinition(NotificationService::class);
            $definition->setArgument('$defaultChannels', $defaultChannels);
        }
    }

    private function isMailerAvailable(): bool
    {
        return interface_exists(MailerInterface::class);
    }

    public function getAlias(): string
    {
        return 'xmon_notification';
    }
}
