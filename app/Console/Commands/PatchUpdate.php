<?php

namespace App\Console\Commands;

use App\Models\Patch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo as FinderSplFileInfo;

/**
 * Patch Update Command
 *
 * Scans the project for modified files and stores them in the database.
 * Only files modified after the configured last_patch_at timestamp are stored.
 */
class PatchUpdate extends Command
{
    protected $signature = 'patch:update
                            {--dry-run : Show what would be stored without saving}';

    protected $description = 'Scan project for modified files and store them in database';

    protected string $basePath;
    protected array $ignorePaths;
    protected array $ignoreExtensions;
    protected Carbon $lastPatchAt;

    protected int $filesFound = 0;
    protected int $filesStored = 0;
    protected int $filesSkipped = 0;
    protected bool $isDryRun = false;

    public function handle(): int
    {
        if (!config('patch.enabled')) {
            $this->error('Patch system is disabled in config/patch.php');
            return self::FAILURE;
        }

        $this->isDryRun = $this->option('dry-run');

        if ($this->isDryRun) {
            $this->warn('DRY RUN MODE - No files will be stored');
            $this->newLine();
        }

        $this->setupConfig();
        $this->scanPaths();
        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function setupConfig(): void
    {
        $this->basePath = base_path();
        $this->ignorePaths = config('patch.ignore_paths', []);
        $this->ignoreExtensions = config('patch.ignore_extensions', []);
        $this->lastPatchAt = Carbon::parse(config('patch.last_patch_at'));

        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║         Laravel Patch System - File Scanner          ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Base path: <info>{$this->basePath}</info>");
        $this->line("Looking for files modified after: <comment>{$this->lastPatchAt->format('Y-m-d H:i:s')}</comment>");
        $this->line("Scan paths: <info>" . implode(', ', config('patch.scan_paths', [])) . "</info>");
        $this->newLine();
    }

    protected function scanPaths(): void
    {
        $this->info('🔍 Scanning directories...');

        foreach (config('patch.scan_paths', []) as $scanPath) {
            $this->scanDirectory($scanPath);
        }
    }

    protected function scanDirectory(string $scanPath): void
    {
        $fullPath = $this->basePath . DIRECTORY_SEPARATOR . $scanPath;

        if (!is_dir($fullPath)) {
            $this->warn("  ⚠ Directory not found: {$scanPath}");
            return;
        }

        $this->line("  📁 Scanning: <info>{$scanPath}</info>");

        foreach (File::allFiles($fullPath, true) as $file) {
            $this->processFile($file, $scanPath);
        }
    }

    protected function processFile(FinderSplFileInfo $file, string $scanPath): void
    {
        $this->filesFound++;
        $relativePath = $file->getRelativePathname();
        $fullRelativePath = str_replace('\\', '/', $scanPath . '/' . $relativePath);

        if ($this->shouldIgnore($fullRelativePath)) {
            $this->filesSkipped++;
            return;
        }
 
        $modifiedAt = Carbon::createFromTimestamp($file->getMTime());

        if ($modifiedAt->lte($this->lastPatchAt)) {
            $this->filesSkipped++;
            return;
        }

        $this->storePatch($fullRelativePath, $modifiedAt);
    }

    protected function shouldIgnore(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        foreach ($this->ignorePaths as $ignore) {
            if (str_contains($normalizedPath, $ignore)) {
                return true;
            }
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), $this->ignoreExtensions);
    }

    protected function storePatch(string $path, Carbon $modifiedAt): void
    {
        try {
            if (!$this->isDryRun) {
                Patch::updateOrCreate(
                    ['file_from' => $path],
                    [
                        'modified_at' => $modifiedAt,
                    ]
                );
            }

            $this->filesStored++;
            $this->line("    ✓ <info>{$path}</info> <comment>({$modifiedAt->format('Y-m-d H:i:s')})</comment>");
        } catch (\Exception $e) {
            $this->error("    ✗ Failed to store {$path}: {$e->getMessage()}");
        }
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║                    Scan Summary                       ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->line("  Total files found:  <info>{$this->filesFound}</info>");
        $this->line("  Files to store:     <info>{$this->filesStored}</info>");
        $this->line("  Files skipped:      <comment>{$this->filesSkipped}</comment>");

        if ($this->isDryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. No files were stored.');
            $this->line('To actually store files, run without --dry-run');
        }

        if ($this->filesStored > 0 && !$this->isDryRun) {
            $this->newLine();
            $this->info('✨ Success! Run <comment>php artisan patch:wrap</comment> to create the patch zip.');
        }
    }
}
