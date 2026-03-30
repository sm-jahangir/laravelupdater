<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Patch Timestamp Command
 *
 * Updates the last_patch_at timestamp in config/patch.php
 * This helps track only files modified after a specific date.
 */
class PatchTimestamp extends Command
{
    protected $signature = 'patch:timestamp
                            {--set= : Set timestamp to specific date (Y-m-d H:i:s)}
                            {--now : Set to current timestamp}
                            {--show : Show current timestamp}';

    protected $description = 'Update or show the last_patch_at timestamp';

    public function handle(): int
    {
        if ($this->option('show')) {
            $this->showTimestamp();
            return self::SUCCESS;
        }

        if ($this->option('now')) {
            $this->setTimestamp(Carbon::now());
            return self::SUCCESS;
        }
 
        if ($this->option('set')) {
            try {
                $timestamp = Carbon::parse($this->option('set'));
                $this->setTimestamp($timestamp);
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Invalid date format. Use: Y-m-d H:i:s");
                return self::FAILURE;
            }
        }

        $this->showTimestamp();
        $this->newLine();
        $this->info('Usage:');
        $this->line('  php artisan patch:timestamp --show       Show current timestamp');
        $this->line('  php artisan patch:timestamp --now        Set to current time');
        $this->line('  php artisan patch:timestamp --set="2026-03-31 15:30:00"  Set specific time');

        return self::SUCCESS;
    }

    protected function showTimestamp(): void
    {
        $lastPatchAt = config('patch.last_patch_at');
        $parsed = Carbon::parse($lastPatchAt);

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║         Patch System - Current Timestamp             ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Current <info>last_patch_at</info>: <comment>{$lastPatchAt}</comment>");
        $this->line("                  ({$parsed->diffForHumans()})");
        $this->newLine();
        $this->warn('Only files modified AFTER this date will be included in patches.');
        $this->line('To update this timestamp, use: <info>php artisan patch:timestamp --now</info>');
    }

    protected function setTimestamp(Carbon $timestamp): void
    {
        $configPath = config_path('patch.php');
        $newTimestamp = $timestamp->format('Y-m-d H:i:s');

        if (!File::exists($configPath)) {
            $this->error("Config file not found: {$configPath}");
            return;
        }

        $configContent = File::get($configPath);

        // Replace the last_patch_at value
        $newContent = preg_replace(
            "/'last_patch_at'\s*=>\s*env\('LAST_PATCH_AT',\s*'[^']+'\)/",
            "'last_patch_at' => env('LAST_PATCH_AT', '{$newTimestamp}')",
            $configContent
        );

        if ($newContent === $configContent) {
            $this->error('Failed to update config. Try editing config/patch.php manually.');
            return;
        }

        File::put($configPath, $newContent);

        // Clear config cache
        if (function_exists('Artisan::call')) {
            \Artisan::call('config:clear');
        }

        $this->info("✓ Updated last_patch_at to: <info>{$newTimestamp}</info>");
        $this->line("  Run <info>php artisan patch:update</info> to scan for files modified after this date.");
    }
}
