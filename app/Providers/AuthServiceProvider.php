<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use App\Models\ContractHistory;
use App\Models\ContractHistoryType;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\SatisfactionSurvey;
use App\Models\SchoolAppointment;
use App\Models\SchoolGoal;
use App\Models\StudentAnnouncement;
use App\Policies\CommercialLeadPolicy;
use App\Policies\CommercialVisitPolicy;
use App\Policies\ContractHistoryPolicy;
use App\Policies\ContractHistoryTypePolicy;
use App\Policies\ExportPolicy;
use App\Policies\InstallmentPolicy;
use App\Policies\PayablePolicy;
use App\Policies\SatisfactionSurveyPolicy;
use App\Policies\SchoolAppointmentPolicy;
use App\Policies\SchoolGoalPolicy;
use App\Policies\StudentAnnouncementPolicy;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Installment::class => InstallmentPolicy::class,
        CommercialLead::class => CommercialLeadPolicy::class,
        CommercialVisit::class => CommercialVisitPolicy::class,
        ContractHistory::class => ContractHistoryPolicy::class,
        ContractHistoryType::class => ContractHistoryTypePolicy::class,
        Export::class => ExportPolicy::class,
        Payable::class => PayablePolicy::class,
        SatisfactionSurvey::class => SatisfactionSurveyPolicy::class,
        SchoolAppointment::class => SchoolAppointmentPolicy::class,
        SchoolGoal::class => SchoolGoalPolicy::class,
        StudentAnnouncement::class => StudentAnnouncementPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
