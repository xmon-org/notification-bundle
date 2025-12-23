<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Tests;

use PHPUnit\Framework\TestCase;
use Xmon\NotificationBundle\XmonNotificationBundle;

/**
 * Basic test to verify the bundle loads correctly.
 */
class XmonNotificationBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new XmonNotificationBundle();

        $this->assertInstanceOf(XmonNotificationBundle::class, $bundle);
    }

    public function testBundleHasCorrectName(): void
    {
        $bundle = new XmonNotificationBundle();

        $this->assertSame('XmonNotificationBundle', $bundle->getName());
    }
}
