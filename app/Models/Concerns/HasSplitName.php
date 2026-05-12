<?php

namespace App\Models\Concerns;

trait HasSplitName
{
    public function getFullNameAttribute(): string
    {
        // Prefer the legacy full_name field until all records have split names.
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
        // Writing full_name keeps first_name and last_name in sync for newer screens.
        [$firstName, $lastName] = $this->splitNameParts($value);

        $this->attributes['first_name'] = $firstName;
        $this->attributes['last_name'] = $lastName;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->getFullNameAttribute();
    }

    public function getFormalNameAttribute(): string
    {
        // Staff-facing screens use salutation plus last name when available.
        $salutation = $this->normalizeName($this->attributes['salutation'] ?? null);
        $lastName = $this->normalizeName($this->attributes['last_name'] ?? null);

        if ($salutation !== '' && $lastName !== '') {
            return trim($salutation.' '.$lastName);
        }

        if ($lastName !== '') {
            return $lastName;
        }

        return $this->getDisplayNameAttribute();
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
        // Collapse accidental extra spacing before names reach the UI.
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return (string) preg_replace('/\s+/', ' ', $trimmed);
    }
}
