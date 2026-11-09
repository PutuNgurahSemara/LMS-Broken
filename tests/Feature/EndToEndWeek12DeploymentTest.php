<?php

namespace Tests\Feature;

use Tests\TestCase;

class EndToEndWeek12DeploymentTest extends TestCase
{
    /**
     * Verifikasi end-to-end seluruh konfigurasi deployment dan keamanan produksi Week 12
     */
    public function test_complete_end_to_end_week12_deployment_lifecycle(): void
    {
        // 1. Audit .env.example
        $envExample = file_get_contents(base_path('.env.example'));
        $this->assertStringNotContainsString('P@ssw0rdProdLMS2026!', $envExample);
        $this->assertStringNotContainsString('10.10.4.52', $envExample);
        $this->assertStringNotContainsString('AKIAIOSFODNN7PRODKEY', $envExample);
        $this->assertMatchesRegularExpression('/^APP_DEBUG=false/m', $envExample);

        // 2. Audit .gitignore
        $gitignore = file_get_contents(base_path('.gitignore'));
        $this->assertStringContainsString(".env\n", $gitignore);
        $this->assertStringContainsString(".env.backup\n", $gitignore);
        $this->assertStringContainsString(".env.production\n", $gitignore);
        $this->assertStringContainsString("/vendor\n", $gitignore);

        // 3. Audit nginx.conf
        $nginx = file_get_contents(base_path('nginx.conf'));
        $this->assertStringContainsString('root /var/www/kampuslms/public;', $nginx);
        $this->assertStringNotContainsString('root /var/www/kampuslms;', $nginx);
        $this->assertStringContainsString('add_header X-Frame-Options "SAMEORIGIN";', $nginx);
        $this->assertStringContainsString('add_header X-Content-Type-Options "nosniff";', $nginx);
        $this->assertStringContainsString('location ~ /\.(?!well-known).* {', $nginx);
        $this->assertStringContainsString('deny all;', $nginx);

        // 4. Audit supervisor.conf
        $supervisor = file_get_contents(base_path('supervisor.conf'));
        $this->assertStringContainsString('[program:kampuslms-worker]', $supervisor);
        $this->assertStringContainsString('command=php /var/www/kampuslms/artisan queue:work --sleep=3 --tries=3 --max-time=3600', $supervisor);
        $this->assertStringContainsString('autostart=true', $supervisor);
        $this->assertStringContainsString('autorestart=true', $supervisor);
        $this->assertStringContainsString('user=www-data', $supervisor);
        $this->assertStringContainsString('numprocs=2', $supervisor);
        $this->assertStringContainsString('stdout_logfile=/var/www/kampuslms/storage/logs/worker.log', $supervisor);

        // 5. Audit deploy.sh
        $deploy = file_get_contents(base_path('deploy.sh'));
        $this->assertMatchesRegularExpression('/^#!\/bin\/bash\s+set -e/m', $deploy);
        $this->assertStringContainsString('php artisan down', $deploy);
        $this->assertStringContainsString('git pull origin main', $deploy);
        $this->assertStringContainsString('composer install --no-dev', $deploy);
        $this->assertStringContainsString('php artisan migrate --force', $deploy);
        $this->assertStringNotContainsString('php artisan db:seed', $deploy);
        $this->assertStringContainsString('php artisan config:cache', $deploy);
        $this->assertStringContainsString('php artisan route:cache', $deploy);
        $this->assertStringContainsString('php artisan view:cache', $deploy);
        $this->assertStringContainsString('php artisan queue:restart', $deploy);
        $this->assertStringContainsString('php artisan up', $deploy);

        // Pastikan urutan deploy benar: queue:restart sebelum php artisan up
        $queueRestartPos = strpos($deploy, 'php artisan queue:restart');
        $artisanUpPos = strpos($deploy, 'php artisan up');
        $this->assertLessThan($artisanUpPos, $queueRestartPos, 'queue:restart harus dipanggil sebelum aplikasi keluar dari maintenance mode');
    }
}
