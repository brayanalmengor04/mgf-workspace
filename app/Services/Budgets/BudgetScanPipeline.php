<?php

namespace App\Services\Budgets;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BudgetScanPipeline
{
    public function __construct(
        private readonly BudgetScanRateLimiter $rateLimiter,
        private readonly BudgetImageExtractionService $extractionService,
        private readonly BudgetScanNormalizer $normalizer,
        private readonly BudgetScanSession $session,
        private readonly BudgetScanImageOptimizer $imageOptimizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function processUploadedFile(UploadedFile $file, User $user): array
    {
        $this->rateLimiter->ensure($user);

        $maxBytes = $this->maxBytes();
        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException('La imagen supera el tamaño máximo de '.config('services.budget_scan.max_mb', 8).' MB.');
        }

        $storedPath = $file->store('temp/budget-scans', 'local');
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            return $this->processAbsolutePath($absolutePath, $user);
        } finally {
            if ($this->shouldDeleteImageAfter()) {
                Storage::disk('local')->delete($storedPath);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function processAbsolutePath(string $absolutePath, User $user): array
    {
        $this->rateLimiter->ensure($user);

        $optimizedPath = $this->imageOptimizer->optimize($absolutePath);

        try {
            $raw = $this->extractionService->extract($optimizedPath, $user);
        } finally {
            if ($optimizedPath !== $absolutePath && File::exists($optimizedPath)) {
                File::delete($optimizedPath);
            }
        }

        if (isset($raw['error'])) {
            throw new RuntimeException((string) $raw['error']);
        }

        $normalized = $this->normalizer->normalize($raw, $user);

        if (isset($normalized['error'])) {
            throw new RuntimeException((string) $normalized['error']);
        }

        $this->session->put($normalized);
        $this->rateLimiter->hit($user);

        if ($this->shouldDeleteImageAfter() && File::exists($absolutePath)) {
            File::delete($absolutePath);
        }

        return $normalized;
    }

    public function maxBytes(): int
    {
        return (int) config('services.budget_scan.max_mb', 8) * 1024 * 1024;
    }

    private function shouldDeleteImageAfter(): bool
    {
        return filter_var(config('services.budget_scan.delete_image_after', true), FILTER_VALIDATE_BOOL);
    }
}
