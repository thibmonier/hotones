<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\OrderLine;
use App\Entity\OrderSection;
use App\Entity\Profile;
use PHPUnit\Framework\TestCase;

class OrderLineTest extends TestCase
{
    private function createServiceLine(
        string $days,
        string $tjm,
        ?string $purchase = null,
        ?string $defaultProfileRate = '500',
    ): OrderLine {
        $profile = new Profile()
            ->setName('Dev')
            ->setDefaultDailyRate($defaultProfileRate);

        $section = new OrderSection()->setTitle('Section 1');

        $line = new OrderLine()
            ->setSection($section)
            ->setDescription('Service line')
            ->setType('service')
            ->setProfile($profile)
            ->setDailyRate($tjm)
            ->setDays($days);

        if ($purchase !== null) {
            $line->setAttachedPurchaseAmount($purchase);
        }

        return $line;
    }

    public function testServiceLineAmounts(): void
    {
        $line = $this->createServiceLine('5', '1000');
        $this->assertSame('5000.00', $line->getServiceAmount());
        $this->assertSame('5000.00', $line->getTotalAmount());
    }

    public function testServiceLineWithAttachedPurchase(): void
    {
        $line = $this->createServiceLine('5', '1000', purchase: '300.00');
        $this->assertSame('5000.00', $line->getServiceAmount());
        $this->assertSame('5300.00', $line->getTotalAmount());
    }

    public function testEstimatedCostAndMargins(): void
    {
        $line = $this->createServiceLine('4', '900', defaultProfileRate: '600');
        // Estimated cost = days * (defaultProfileRate * 0.7) = 4 * 420 = 1680
        $this->assertSame('1680.00', $line->getEstimatedCost());
        $this->assertSame('3600.00', $line->getServiceAmount());
        $this->assertSame('1920.00', $line->getGrossMargin());
        $this->assertSame('53.33', $line->getMarginRate());
    }

    public function testPurchaseLine(): void
    {
        $section = new OrderSection()->setTitle('Section 1');
        $line    = new OrderLine()
            ->setSection($section)
            ->setDescription('Purchase')
            ->setType('purchase')
            ->setDirectAmount('250.00');

        $this->assertSame('250.00', $line->getTotalAmount());
        $this->assertSame('0', $line->getServiceAmount());
        $this->assertSame('0', $line->getEstimatedCost());
        $this->assertSame('0', $line->getGrossMargin());
    }
}
