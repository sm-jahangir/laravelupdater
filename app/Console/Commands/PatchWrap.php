<?php

namespace App\Console\Commands;

use App\Models\Patch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;
 
/**
 * Patch Wrap Command
 *
 * Creates a zip file from all stored patches in the database.
 * Maintains the original folder structure inside the zip.
 */
class PatchWrap extends Command
{
    protected $signature = 'patch:wrap
                            {--c|cleanup : Clear database after creating zip}
                            {--zip-name= : Custom name for the zip file (without extension)}
                            {--t|update-timestamp : Update last_patch_at to current time after wrapping}';

    protected $description = 'Create a zip file from stored patches';

    protected const PATCHES_DIR = 'patches';

    protected string $basePath;
    protected string $patchesDir;
    protected string $tempDir;

    protected int $filesCopied = 0;
    protected int $filesFailed = 0;

    public function handle(): int
    {
        if (!config('patch.enabled')) {
            $this->error('Patch system is disabled in config/patch.php');
            return self::FAILURE;
        }

        $patches = Patch::all();

        if ($patches->isEmpty()) {
            $this->warn('No patches found in database.');
            $this->line('Run <info>php artisan patch:update</info> first to scan for modified files.');
            return self::FAILURE;
        }

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║         Laravel Patch System - Zip Creator           ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->setupPaths($patches->count());
        $this->createTempDirectory();
        $this->copyFilesToTemp($patches);
        $zipPath = $this->createZipFile();
        $this->cleanup();
        $this->displaySummary($zipPath);

        if ($this->option('update-timestamp') && $this->confirm("\nDo you want to update last_patch_at to current time?", true)) {
            $this->updateTimestamp();
        }

        if ($this->option('cleanup') && $this->confirm("\nDo you want to clear all patches from database?", false)) {
            $this->clearDatabase();
        }

        return self::SUCCESS;
    }

    protected function setupPaths(int $patchCount): void
    {
        $this->basePath = base_path();
        $this->patchesDir = $this->basePath . DIRECTORY_SEPARATOR . self::PATCHES_DIR;
        $this->tempDir = $this->patchesDir . DIRECTORY_SEPARATOR . 'temp';

        $this->line("Found <info>{$patchCount}</info> patch(es) in database");
        $this->line("Output directory: <info>{$this->patchesDir}</info>");
        $this->newLine();
    }

    protected function createTempDirectory(): void
    {
        if (!File::exists($this->patchesDir)) {
            File::makeDirectory($this->patchesDir, 0755, true);
        }

        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function copyFilesToTemp($patches): void
    {
        $this->info('📦 Copying files to temporary directory...');

        $bar = $this->output->createProgressBar($patches->count());
        $bar->start();

        foreach ($patches as $patch) {
            $sourcePath = $this->basePath . DIRECTORY_SEPARATOR . $patch->file_from;

            if ($this->isValidFile($sourcePath, $patch->file_from)) {
                $this->copyFile($sourcePath, $patch->file_from);
            } else {
                $this->filesFailed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();
    }

    protected function isValidFile(string $sourcePath, string $relativePath): bool
    {
        if (!File::exists($sourcePath)) {
            $this->warn("  ⚠ File not found: {$relativePath}");
            return false;
        }

        $realSource = realpath($sourcePath);
        $realBase = realpath($this->basePath);

        if ($realSource === false || strpos($realSource, $realBase) !== 0) {
            $this->warn("  ⚠ Invalid path (potential traversal): {$relativePath}");
            return false;
        }

        return true;
    }

    protected function copyFile(string $sourcePath, string $relativePath): void
    {
        $destinationPath = $this->tempDir . DIRECTORY_SEPARATOR . $relativePath;
        $destinationDir = dirname($destinationPath);

        if (!File::exists($destinationDir)) {
            File::makeDirectory($destinationDir, 0755, true);
        }

        if (File::copy($sourcePath, $destinationPath)) {
            $this->filesCopied++;
        } else {
            $this->filesFailed++;
            $this->error("  ✗ Failed to copy: {$relativePath}");
        }
    }

    protected function createZipFile(): ?string
    {
        if ($this->filesCopied === 0) {
            $this->error('No files were copied to temp directory');
            return null;
        }

        $customName = $this->option('zip-name');
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $zipName = $customName
            ? $customName . '.zip'
            : 'patch_' . $timestamp . '.zip';
        $zipPath = $this->patchesDir . DIRECTORY_SEPARATOR . $zipName;

        $this->info("🗜️  Creating zip file: <info>{$zipName}</info>");

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Failed to create zip archive");
            return null;
        }

        foreach (File::allFiles($this->tempDir) as $file) {
            $zip->addFile($file->getRealPath(), $file->getRelativePathname());
        }

        $zip->close();

        $this->info("   Added <info>{$this->filesCopied}</info> file(s) to archive");

        return $zipPath;
    }

    protected function cleanup(): void
    {
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
    }

    protected function displaySummary(?string $zipPath): void
    {
        if ($zipPath === null) {
            return;
        }

        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║                    Zip Summary                        ║');
        $this->info('╚══════════════════════════════════════════════════════╝');

        $relativePath = str_replace($this->basePath . DIRECTORY_SEPARATOR, '', $zipPath);
        $fileSize = $this->formatFileSize(filesize($zipPath));

        $this->line("  Zip location:    <info>{$relativePath}</info>");
        $this->line("  File size:       <comment>{$fileSize}</comment>");
        $this->line("  Files included:  <info>{$this->filesCopied}</info>");
        $this->line("  Files failed:    <error>{$this->filesFailed}</error>");

        if ($this->filesFailed > 0) {
            $this->newLine();
            $this->warn('Some files failed to copy. Check the warnings above.');
        }
    }

    protected function clearDatabase(): void
    {
        $count = Patch::count();
        Patch::truncate();
        $this->newLine();
        $this->info("🗑️  Cleared <info>{$count}</info> patch(es) from database.");
    }

    protected function updateTimestamp(): void
    {
        $configPath = config_path('patch.php');
        $newTimestamp = Carbon::now()->format('Y-m-d H:i:s');

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

        $this->newLine();
        $this->info("✓ Updated last_patch_at to: <info>{$newTimestamp}</info>");
        $this->line("  Next patch will only include files modified after this time.");
    }

    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return number_format($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
