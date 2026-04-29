<?php

namespace App\Models\Concerns;

trait ResolvesMediaUrl
{
    protected function resolveMediaUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (
            str_starts_with($value, 'data:') ||
            str_starts_with($value, 'http://') ||
            str_starts_with($value, 'https://')
        ) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
