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

        $this->assertSame($company, $log->getCompany());
        $this->assertNotNull($log->occurredAt);
    }

    public function testDefaults(): void
    {
        $log = new AiUsageLog(new Company());

        $this->assertSame('anthropic', $log->provider);
        $this->assertSame('', $log->model);
        $this->assertSame(0, $log->promptTokens);
        $this->assertSame(0, $log->completionTokens);
        $this->assertSame('0', $log->costUsd);
    }

    public function testFieldAssignment(): void
    {
        $log = new AiUsageLog(new Company());
        $log->provider = 'openai';
        $log->model = 'gpt-4o';
        $log->promptTokens = 1500;
        $log->completionTokens = 250;
        $log->costUsd = '0.012345';

        $this->assertSame('openai', $log->provider);
        $this->assertSame('gpt-4o', $log->model);
        $this->assertSame(1500, $log->promptTokens);
        $this->assertSame(250, $log->completionTokens);
        $this->assertSame('0.012345', $log->costUsd);
    }

    public function testCompanySetter(): void
    {
        $companyA = new Company();
        $companyB = new Company();
        $log = new AiUsageLog($companyA);

        $this->assertSame($companyA, $log->getCompany());

        $log->setCompany($companyB);
        $this->assertSame($companyB, $log->getCompany());
    }
}
