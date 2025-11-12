<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Analytics\FactStaffingMetrics;
use PHPUnit\Framework\TestCase;

class StaffingMetricsCalculationServiceTest extends TestCase
{
    /**
     * Test le calcul du taux de staffing et du TACE.
     */
    public function testCalculateMetrics(): void
    {
        // Arrange
        $fact = new FactStaffingMetrics();
        $fact->setAvailableDays('20.00'); // 20 jours disponibles
        $fact->setWorkedDays('20.00'); // 20 jours travaillés
        $fact->setStaffedDays('17.00'); // 17 jours staffés
        $fact->setVacationDays('0.00');

        // Act
        $fact->calculateMetrics();

        // Assert
        // Taux de staffing = (17 / 20) * 100 = 85%
        $this->assertEquals('85.00', $fact->getStaffingRate());

        // TACE = (17 / 20) * 100 = 85%
        $this->assertEquals('85.00', $fact->getTace());
    }

    /**
     * Test le calcul avec des jours disponibles à zéro.
     */
    public function testCalculateMetricsWithZeroAvailableDays(): void
    {
        // Arrange
        $fact = new FactStaffingMetrics();
        $fact->setAvailableDays('0.00');
        $fact->setWorkedDays('0.00');
        $fact->setStaffedDays('0.00');
        $fact->setVacationDays('0.00');

        // Act
        $fact->calculateMetrics();

        // Assert
        $this->assertEquals('0.00', $fact->getStaffingRate());
        $this->assertEquals('0.00', $fact->getTace());
    }

    /**
     * Test le calcul du TACE avec différentes valeurs.
     */
    public function testTaceCalculation(): void
    {
        // Arrange
        $fact = new FactStaffingMetrics();
        $fact->setAvailableDays('22.00'); // 22 jours disponibles
        $fact->setWorkedDays('20.00'); // 20 jours travaillés (2 jours de congés)
        $fact->setStaffedDays('18.00'); // 18 jours staffés
        $fact->setVacationDays('2.00');

        // Act
        $fact->calculateMetrics();

        // Assert
        // TACE = (18 / 20) * 100 = 90%
        $this->assertEquals('90.00', $fact->getTace());

        // Staffing Rate = (18 / 22) * 100 = 81.818... arrondi à 81.81%
        $this->assertEquals('81.81', $fact->getStaffingRate());
    }

    /**
     * Test le calcul avec un taux de staffing élevé.
     */
    public function testHighStaffingRate(): void
    {
        // Arrange
        $fact = new FactStaffingMetrics();
        $fact->setAvailableDays('20.00');
        $fact->setWorkedDays('20.00');
        $fact->setStaffedDays('19.50'); // Presque tous les jours staffés

        // Act
        $fact->calculateMetrics();

        // Assert
        // Staffing Rate = (19.5 / 20) * 100 = 97.50%
        $this->assertEquals('97.50', $fact->getStaffingRate());

        // TACE = (19.5 / 20) * 100 = 97.50%
        $this->assertEquals('97.50', $fact->getTace());
    }
}
