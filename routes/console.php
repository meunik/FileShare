<?php

use App\Jobs\CleanupExpiredFilesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agenda a limpeza de arquivos expirados para executar a cada hora
Schedule::job(new CleanupExpiredFilesJob)->hourly();
