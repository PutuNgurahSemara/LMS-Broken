<?php

namespace Tests\Feature;

use Tests\TestCase;

class Week12Test extends TestCase
{
    /**
     * Error 1: .env.example tidak boleh memuat kredensial produksi sungguhan
     */
    public function test_env_example_does_not_contain_real_credentials(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertStringNotContainsString('P@ssw0rdProdLMS2026!', $envExample, 'Kredensial database produksi bocor di .env.example');
        $this->assertStringNotContainsString('10.10.4.52', $envExample, 'IP server database produksi bocor di .env.example');
        $this->assertStringNotContainsString('AKIAIOSFODNN7PRODKEY', $envExample, 'AWS Access Key produksi bocor di .env.example');
        $this->assertStringNotContainsString('wJalrXUtnFEMI/K7MDENG/bPxRfiCYPRODSECRET', $envExample, 'AWS Secret Key produksi bocor di .env.example');
        $this->assertStringNotContainsString('kampuslms-production-storage', $envExample, 'AWS Bucket produksi bocor di .env.example');
    }

    /**
     * Error 2: deploy.sh harus menggunakan set -e dan menyertakan queue:restart
     */
    public function test_deploy_script_has_set_e_and_queue_restart(): void
    {
        $this->assertFileExists(base_path('deploy.sh'), 'File deploy.sh harus ada');
        $deployScript = file_get_contents(base_path('deploy.sh'));

        $this->assertMatchesRegularExpression('/^set -e/m', $deployScript, 'deploy.sh wajib menyertakan set -e');
        $this->assertStringContainsString('php artisan queue:restart', $deployScript, 'deploy.sh wajib menyertakan queue:restart');
    }

    /**
     * Error 3: Konfigurasi Nginx document root harus mengarah ke direktori public/
     */
    public function test_nginx_root_points_to_public(): void
    {
        $this->assertFileExists(base_path('nginx.conf'), 'File nginx.conf harus ada');
        $nginxConf = file_get_contents(base_path('nginx.conf'));

        $this->assertMatchesRegularExpression('/root\s+\/var\/www\/kampuslms\/public;/', $nginxConf, 'Nginx root harus mengarah ke /var/www/kampuslms/public');
        $this->assertDoesNotMatchRegularExpression('/root\s+\/var\/www\/kampuslms;/', $nginxConf, 'Nginx root tidak boleh mengarah ke akar direktori /var/www/kampuslms');
    }

    /**
     * Error 4: APP_DEBUG di .env.example harus bernilai false
     */
    public function test_env_example_has_app_debug_false(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false/m', $envExample, 'APP_DEBUG di .env.example harus false');
        $this->assertDoesNotMatchRegularExpression('/^APP_DEBUG=true/m', $envExample, 'APP_DEBUG di .env.example tidak boleh true');
    }

    /**
     * Error 5: Konfigurasi Supervisor harus ada untuk worker antrean
     */
    public function test_supervisor_configuration_exists_and_configured(): void
    {
        $this->assertFileExists(base_path('supervisor.conf'), 'File konfigurasi supervisor.conf harus ada');
        $supervisorConf = file_get_contents(base_path('supervisor.conf'));

        $this->assertStringContainsString('[program:kampuslms-worker]', $supervisorConf, 'Supervisor harus mendefinisikan program worker');
        $this->assertStringContainsString('command=php /var/www/kampuslms/artisan queue:work', $supervisorConf, 'Supervisor harus menjalankan queue:work');
        $this->assertStringContainsString('autostart=true', $supervisorConf);
        $this->assertStringContainsString('autorestart=true', $supervisorConf);
    }

    /**
     * Error 6: db:seed tidak boleh dijalankan tiap deploy di deploy.sh
     */
    public function test_deploy_script_does_not_run_db_seed(): void
    {
        $deployScript = file_get_contents(base_path('deploy.sh'));

        $this->assertStringNotContainsString('php artisan db:seed', $deployScript, 'db:seed tidak boleh dijalankan otomatis tiap deploy di produksi');
        $this->assertStringContainsString('php artisan migrate --force', $deployScript, 'deploy.sh harus menjalankan migrasi database dengan --force');
    }

    /**
     * Error 7: .gitignore harus mengabaikan berkas .env
     */
    public function test_gitignore_ignores_env_file(): void
    {
        $this->assertFileExists(base_path('.gitignore'), 'File .gitignore harus ada');
        $gitignore = file_get_contents(base_path('.gitignore'));

        $lines = array_map('trim', explode("\n", $gitignore));
        $this->assertContains('.env', $lines, '.gitignore harus memuat baris .env untuk mencegah kebocoran kredensial');
    }
}
