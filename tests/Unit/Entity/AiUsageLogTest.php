<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AiUsageLog;
use App\Entity\Company;
use PHPUnit\Framework\TestCase;

final class AiUsageLogTest extends TestCase
{
    public function testConstructInitializesCompanyAndOccurredAt(): void
    {
        $company = new Company();
        $log = new AiUsageLog($company);

        static::assertSame($company, $log->getCompany());
        static::assertNotNull($log->occurredAt);
    }

    public function testDefaults(): void
    {
        $log = new AiUsageLog(new Company());

        static::assertSame('anthropic', $log->provider);
        static::assertSame('', $log->model);
        static::assertSame(0, $log->promptTokens);
        static::assertSame(0, $log->completionTokens);
        static::assertSame('0', $log->costUsd);
    }

    public function testFieldAssignment(): void
    {
        $log = new AiUsageLog(new Company());
        $log->provider = 'openai';
        $log->model = 'gpt-4o';
        $log->promptTokens = 1500;
        $log->completionTokens = 250;
        $log->costUsd = '0.012345';

        static::assertSame('openai', $log->provider);
        static::assertSame('gpt-4o', $log->model);
        static::assertSame(1500, $log->promptTokens);
        static::assertSame(250, $log->completionTokens);
        static::assertSame('0.012345', $log->costUsd);
    }

    public function testCompanySetter(): void
    {
        $companyA = new Company();
        $companyB = new Company();
        $log = new AiUsageLog($companyA);

        static::assertSame($companyA, $log->getCompany());

        $log->setCompany($companyB);
        static::assertSame($companyB, $log->getCompany());
    }
}
