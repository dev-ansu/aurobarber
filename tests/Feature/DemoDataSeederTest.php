<?php

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\ContractType;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\StudentSchedule;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('demo seeder creates active contracts with products, installments, and schedules idempotently', function () {
    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    expect(Course::query()->count())->toBe(4)
        ->and(Formation::query()->count())->toBe(2)
        ->and(ContractType::query()->where('is_active', true)->count())->toBe(1)
        ->and(Contract::query()->where('contract_number', 'like', 'DEMO-%')->count())->toBe(3)
        ->and(Contract::query()->where('status', 'active')->where('contract_number', 'like', 'DEMO-%')->count())->toBe(3)
        ->and(ContractItem::query()->count())->toBe(3)
        ->and(ContractItem::query()->whereNull('formation_id')->count())->toBe(0)
        ->and(Schema::hasColumn('contract_items', 'price'))->toBeFalse()
        ->and(ContractCourse::query()->count())->toBe(6)
        ->and((float) Contract::query()->where('contract_number', 'like', 'DEMO-%')->value('total_value'))->toBe(2400.0)
        ->and(Installment::query()->count())->toBe(24)
        ->and(Installment::query()->where('status', 'paid')->count())->toBe(3)
        ->and(Installment::query()->where('status', 'pending')->count())->toBe(21)
        ->and(StudentSchedule::query()->count())->toBe(6);
});
