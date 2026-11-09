<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SaasSetting;

class SaasSupportContact
{
    public function phone(): string
    {
        $configuredPhone = SaasSetting::query()
            ->where('key', 'support_phone')
            ->value('value');

        return (string) ($configuredPhone ?? config('license.support_phone', ''));
    }

    public function phoneDigits(): string
    {
        return preg_replace('/\D/', '', $this->phone()) ?? '';
    }
}
