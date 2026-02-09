<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EnvInfoTest extends KernelTestCase
{
    public function testEnvNameOnly(): void
    {
        self::bootKernel();
        $this->assertSame('test', self::$kernel->getEnvironment());
    }
}
