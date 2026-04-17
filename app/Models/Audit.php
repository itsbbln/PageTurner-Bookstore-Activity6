<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit as BaseAudit;

class Audit extends BaseAudit
{
    protected $fillable = [
        'uuid',
        'checksum',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $audit) {
            $audit->uuid ??= (string) Str::uuid();
            $audit->checksum = $audit->checksumPayload();
        });
    }

    public function checksumPayload(): string
    {
        $payload = [
            'user_type' => $this->getAttribute('user_type'),
            'user_id' => $this->getAttribute('user_id'),
            'event' => $this->event,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'url' => $this->url,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'tags' => $this->tags,
            'created_at' => optional($this->created_at)->toJSON(),
        ];

        $payload = $this->ksortRecursive($payload);
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json ?: '');
    }

    private function ksortRecursive(array $value): array
    {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[$k] = $this->ksortRecursive($v);
            }
        }
        ksort($value);

        return $value;
    }
}

