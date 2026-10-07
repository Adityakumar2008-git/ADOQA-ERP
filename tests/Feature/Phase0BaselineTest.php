<?php

namespace Tests\Feature;

use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase0BaselineTest extends TestCase
{
    /**
     * Verify PHP runtime version satisfies the >= 8.3 specification.
     */
    public function test_php_version_is_supported(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.3.0', '>='),
            'PHP version must be >= 8.3.0. Current: '.PHP_VERSION
        );
    }

    /**
     * Verify Laravel framework is on version 13.x.
     */
    public function test_laravel_framework_version_is_13(): void
    {
        $version = app()->version();
        $this->assertStringStartsWith('13.', $version, 'Laravel must be version 13.x. Current: '.$version);
    }

    /**
     * Verify all required extensions for commercial ERP and PostgreSQL are loaded.
     */
    public function test_required_php_extensions_are_loaded(): void
    {
        $requiredExtensions = [
            'pdo',
            'pdo_pgsql',
            'pgsql',
            'bcmath',
            'mbstring',
            'intl',
            'json',
            'openssl',
        ];

        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "Required PHP extension '{$extension}' is not loaded."
            );
        }
    }

    /**
     * Verify PostgreSQL connection configuration exists in database config.
     */
    public function test_postgresql_connection_is_configured(): void
    {
        $pgsqlConfig = Config::get('database.connections.pgsql');

        $this->assertIsArray($pgsqlConfig, 'PostgreSQL connection configuration must exist.');
        $this->assertEquals('pgsql', $pgsqlConfig['driver']);
        $this->assertEquals('5432', (string) $pgsqlConfig['port']);
    }

    /**
     * Verify live PostgreSQL database connection and query execution.
     */
    public function test_live_postgresql_connectivity(): void
    {
        Config::set('database.connections.pgsql.database', 'adoqa_erp');
        DB::purge('pgsql');

        $pdo = DB::connection('pgsql')->getPdo();
        $this->assertNotNull($pdo, 'PostgreSQL PDO instance must not be null.');
        $result = DB::connection('pgsql')->select('SELECT version()');
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('PostgreSQL', $result[0]->version);
    }

    /**
     * Verify fixed-precision decimal arithmetic is functional for commercial financial safety.
     */
    public function test_decimal_fixed_precision_arithmetic(): void
    {
        // 0.1 + 0.2 floating point anomaly must never occur in ERP calculations
        $a = '1000.15';
        $b = '2000.25';
        $sum = bcadd($a, $b, 2);

        $this->assertSame('3000.40', $sum);

        // Brick math test
        $bigA = BigDecimal::of('1234567.89');
        $bigB = BigDecimal::of('9876543.21');
        $this->assertSame('11111111.10', (string) $bigA->plus($bigB));
    }

    /**
     * Verify web application boots and returns 200 OK.
     */
    public function test_application_boots_and_serves_landing_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
