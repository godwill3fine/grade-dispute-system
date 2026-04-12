<?php

namespace App\Models\Concerns;

trait HasSplitName
{
    public function getFullNameAttribute(): string
    {
        $legacyFullName = $this->normalizeName($this->attributes['full_name'] ?? null);
        if ($legacyFullName !== '') {
            return $legacyFullName;
        }

        return $this->combineNameParts(
            $this->attributes['first_name'] ?? null,
            $this->attributes['last_name'] ?? null
        );
    }

    public function setFullNameAttribute(?string $value): void
    {
        [$firstName, $lastName] = $this->splitNameParts($value);

        $this->attributes['first_name'] = $firstName;
        $this->attributes['last_name'] = $lastName;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    protected function combineNameParts(?string $firstName, ?string $lastName): string
    {
        $parts = array_filter([
            $this->normalizeName($firstName),
            $this->normalizeName($lastName),
        ], static fn (?string $part): bool => $part !== null && $part !== '');

        return trim(implode(' ', $parts));
    }

    protected function splitNameParts(?string $fullName): array
    {
        $normalized = $this->normalizeName($fullName);
        if ($normalized === '') {
            return [null, null];
        }

        $parts = explode(' ', $normalized, 2);

        return [
            $parts[0],
            $parts[1] ?? null,
        ];
    }

    protected function normalizeName(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return (string) preg_replace('/\s+/', ' ', $trimmed);
    }
}
