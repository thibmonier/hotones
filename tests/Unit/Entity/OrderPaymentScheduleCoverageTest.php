<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\OrderPaymentSchedule;
use App\Entity\OrderSection;
use App\Entity\Profile;
use DateTime;
use PHPUnit\Framework\TestCase;

class OrderPaymentScheduleCoverageTest extends TestCase
{
    private function makeOrderWithTotal(string $total): Order
    {
        // Build an order whose total from sections equals $total using a single line
        $order = new Order();
        $order->setOrderNumber('DTEST-001');

        $section = new OrderSection()
            ->setOrder($order)
            ->setTitle('S1');

        $profile = new Profile()
            ->setName('Dev')
            ->setDefaultDailyRate('600');
        $line = new OrderLine()
            ->setSection($section)
            ->setDescription('L1')
            ->setType('service')
            ->setProfile($profile)
            ->setDailyRate('1000')
            ->setDays(bcdiv($total, '1000', 2)); // so line total = total

        // Wire: Order -> Section -> Line
        $order->addSection($section);
        $section->addLine($line);

        return $order;
    }

    public function testCoverageIsValidAt100Percent(): void
    {
        $order = $this->makeOrderWithTotal('5000.00');

        $s1 = new OrderPaymentSchedule()
            ->setOrder($order)
            ->setBillingDate(new DateTime('2025-01-15'))
            ->setAmountType(OrderPaymentSchedule::TYPE_PERCENT)
            ->setPercent('60');
        $s2 = new OrderPaymentSchedule()
            ->setOrder($order)
            ->setBillingDate(new DateTime('2025-02-15'))
            ->setAmountType(OrderPaymentSchedule::TYPE_PERCENT)
            ->setPercent('40');

        $order->addPaymentSchedule($s1);
        $order->addPaymentSchedule($s2);

        [$ok, $scheduled] = $order->validatePaymentScheduleCoverage();
        $this->assertTrue($ok);
        $this->assertSame('5000.00', $scheduled);
    }

    public function testCoverageIsInvalidWhenNot100Percent(): void
    {
        $order = $this->makeOrderWithTotal('4000.00');

        $s1 = new OrderPaymentSchedule()
            ->setOrder($order)
            ->setBillingDate(new DateTime('2025-01-15'))
            ->setAmountType(OrderPaymentSchedule::TYPE_PERCENT)
            ->setPercent('50'); // 2000

        $order->addPaymentSchedule($s1);

        [$ok, $scheduled] = $order->validatePaymentScheduleCoverage();
        $this->assertFalse($ok);
        $this->assertSame('2000.00', $scheduled);
    }

    public function testCoverageWithFixedAmounts(): void
    {
        $order = $this->makeOrderWithTotal('3000.00');

        $s1 = new OrderPaymentSchedule()
            ->setOrder($order)
            ->setBillingDate(new DateTime('2025-01-10'))
            ->setAmountType(OrderPaymentSchedule::TYPE_FIXED)
            ->setFixedAmount('1000.00');
        $s2 = new OrderPaymentSchedule()
            ->setOrder($order)
            ->setBillingDate(new DateTime('2025-02-10'))
            ->setAmountType(OrderPaymentSchedule::TYPE_PERCENT)
            ->setPercent('66.6667'); // ~2000.00

        $order->addPaymentSchedule($s1);
        $order->addPaymentSchedule($s2);

        [$ok, $scheduled] = $order->validatePaymentScheduleCoverage();
        $this->assertTrue($ok);
        $this->assertSame('3000.00', $scheduled);
    }
}
