<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Channel;

use Xmon\NotificationBundle\Exception\ChannelNotConfiguredException;

/**
 * Registry for notification channels (Service Locator pattern).
 */
final readonly class ChannelRegistry
{
    /**
     * @param iterable<ChannelInterface> $channels
     */
    public function __construct(
        private iterable $channels,
    ) {
    }

    /**
     * Get a channel by name.
     *
     * @throws ChannelNotConfiguredException
     */
    public function getChannel(string $channelName): ChannelInterface
    {
        foreach ($this->channels as $channel) {
            if ($channel->supports($channelName)) {
                return $channel;
            }
        }

        throw new ChannelNotConfiguredException(\sprintf('Channel "%s" not found or not configured', $channelName));
    }

    /**
     * Check if a channel exists and is configured.
     */
    public function hasChannel(string $channelName): bool
    {
        foreach ($this->channels as $channel) {
            if ($channel->supports($channelName) && $channel->isConfigured()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all available channels.
     *
     * @return array<ChannelInterface>
     */
    public function getAllChannels(): array
    {
        return iterator_to_array($this->channels);
    }
}
