<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class StudentAccessLog extends Model
{
    use HasFactory;

    protected $table = 'student_access_logs';

    protected $fillable = [
        'contract_id',
        'student_id',
        'user_id',
        'lesson_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LmsLesson::class, 'lesson_id');
    }

    public static function log(
        string $action,
        string $description,
        ?Contract $contract = null,
        ?int $lessonId = null,
        ?Person $student = null
    ): self {
        $studentId = $student?->id ?? $contract?->student_id;
        $contractId = $contract?->id;
        $userId = Auth::id();

        $ip = request()?->ip();
        $userAgent = request()?->userAgent();

        return self::create([
            'contract_id' => $contractId,
            'student_id' => $studentId,
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }
}
