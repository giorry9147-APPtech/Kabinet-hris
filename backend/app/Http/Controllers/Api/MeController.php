<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MeController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $employee->load(['currentPosition.orgUnit.parent']);

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'middle_name' => $employee->middle_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'date_of_birth' => $employee->date_of_birth?->toDateString(),
                'gender' => $employee->gender,
                'marital_status' => $employee->marital_status,
                'nationality' => $employee->nationality,
                'national_id' => $employee->national_id,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'address' => $employee->address,
                'status' => $employee->status,
                'joined_at' => $employee->joined_at?->toDateString(),
                'avatar_url' => $employee->getFirstMediaUrl('avatar', 'thumb') ?: null,
                'position' => $employee->currentPosition ? [
                    'id' => $employee->currentPosition->id,
                    'title' => $employee->currentPosition->title,
                    'code' => $employee->currentPosition->code,
                    'org_unit' => $employee->currentPosition->orgUnit ? [
                        'name' => $employee->currentPosition->orgUnit->name,
                        'parent_name' => $employee->currentPosition->orgUnit->parent?->name,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function employment(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);
        $records = $employee->employmentRecords()->with('position.orgUnit')->get();

        return response()->json([
            'records' => $records->map(fn ($r) => [
                'id' => $r->id,
                'position_title' => $r->position?->title,
                'org_unit' => $r->position?->orgUnit?->name,
                'start_date' => $r->start_date?->toDateString(),
                'end_date' => $r->end_date?->toDateString(),
                'status' => $r->status,
                'reason' => $r->reason,
                'notes' => $r->notes,
            ]),
        ]);
    }

    public function salary(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);
        $assignments = $employee->salaryAssignments()->with('salaryGrade')->get();

        return response()->json([
            'current' => $assignments->whereNull('end_date')->sortByDesc('start_date')->first()?->only([
                'id', 'base_amount', 'allowances', 'currency', 'start_date', 'end_date',
            ]),
            'history' => $assignments->map(fn ($s) => [
                'id' => $s->id,
                'grade_code' => $s->salaryGrade?->code,
                'schaal' => $s->salaryGrade?->schaal,
                'trede' => $s->salaryGrade?->trede,
                'base_amount' => (float) $s->base_amount,
                'allowances' => (float) $s->allowances,
                'total' => (float) $s->base_amount + (float) $s->allowances,
                'currency' => $s->currency,
                'start_date' => $s->start_date?->toDateString(),
                'end_date' => $s->end_date?->toDateString(),
            ])->values(),
        ]);
    }

    public function certificates(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);
        $certs = $employee->certificates()->with('certificateType')->get();
        $now = Carbon::now()->startOfDay();

        return response()->json([
            'certificates' => $certs->map(function ($c) use ($now) {
                $expires = $c->expires_at;
                $status = match (true) {
                    $expires === null => 'no_expiry',
                    $expires->lt($now) => 'expired',
                    $expires->lte($now->copy()->addDays(90)) => 'expiring_soon',
                    default => 'valid',
                };
                return [
                    'id' => $c->id,
                    'type_name' => $c->certificateType?->name,
                    'type_category' => $c->certificateType?->category,
                    'number' => $c->number,
                    'issuer' => $c->issuer,
                    'issued_at' => $c->issued_at?->toDateString(),
                    'expires_at' => $expires?->toDateString(),
                    'status' => $status,
                    'days_until_expiry' => $expires ? (int) $now->diffInDays($expires, false) : null,
                    'file_url' => $c->getFirstMediaUrl('file') ?: null,
                ];
            })->values(),
        ]);
    }

    public function leaveIndex(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);
        $year = (int) ($request->query('year') ?: now()->year);

        $requests = $employee->leaveRequests()
            ->with('approver:id,name')
            ->orderByDesc('start_date')
            ->get();

        $approvedThisYear = (float) $requests
            ->where('status', 'approved')
            ->filter(fn ($r) => $r->start_date && $r->start_date->year === $year)
            ->sum('days_count');

        $defaultYearlyDays = (float) config('mas.leave_yearly_days', 24);

        return response()->json([
            'year' => $year,
            'balance' => [
                'yearly_total' => $defaultYearlyDays,
                'used' => $approvedThisYear,
                'remaining' => max(0, $defaultYearlyDays - $approvedThisYear),
            ],
            'requests' => $requests->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'start_date' => $r->start_date?->toDateString(),
                'end_date' => $r->end_date?->toDateString(),
                'days_count' => (float) $r->days_count,
                'status' => $r->status,
                'reason' => $r->reason,
                'approver_name' => $r->approver?->name
                    ?? (in_array($r->status, ['approved', 'rejected', 'cancelled']) ? 'MAS Administrator' : null),
                'decided_at' => $r->decided_at?->toDateTimeString(),
                'decision_reason' => $r->decision_reason,
                'created_at' => $r->created_at?->toDateTimeString(),
                'can_cancel' => $r->status === 'pending',
            ])->values(),
        ]);
    }

    public function leaveStore(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'type' => ['required', 'in:vacation,sick,special,unpaid,maternity,study'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $days = $start->diffInDaysFiltered(fn (Carbon $d) => ! $d->isWeekend(), $end) + 1;

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => $data['type'],
            'start_date' => $start,
            'end_date' => $end,
            'days_count' => $days,
            'status' => 'pending',
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Verlofaanvraag ingediend.',
            'request' => [
                'id' => $leaveRequest->id,
                'days_count' => $days,
                'status' => $leaveRequest->status,
            ],
        ], 201);
    }

    public function leaveCancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        if ($leaveRequest->employee_id !== $employee->id) {
            return response()->json(['message' => 'Niet toegestaan.'], 403);
        }

        if ($leaveRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Alleen aanvragen in behandeling kunnen worden ingetrokken.'],
            ]);
        }

        $leaveRequest->update([
            'status' => 'cancelled',
            'decided_at' => now(),
        ]);

        return response()->json(['message' => 'Aanvraag ingetrokken.']);
    }

    public function assets(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);
        $assignments = $employee->assetAssignments()->with('asset')->get();

        return response()->json([
            'assignments' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'asset_code' => $a->asset?->asset_code,
                'asset_name' => $a->asset?->name,
                'category' => $a->asset?->category,
                'serial_number' => $a->asset?->serial_number,
                'assigned_at' => $a->assigned_at?->toDateString(),
                'returned_at' => $a->returned_at?->toDateString(),
                'condition_at_assignment' => $a->condition_at_assignment,
                'is_active' => $a->returned_at === null,
            ])->values(),
        ]);
    }

    public function assetRequestIndex(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $requests = AssetRequest::query()
            ->where('employee_id', $employee->id)
            ->with('decider:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'requests' => $requests->map(fn ($r) => [
                'id' => $r->id,
                'category' => $r->category,
                'subject' => $r->subject,
                'reason' => $r->reason,
                'needed_by' => $r->needed_by?->toDateString(),
                'status' => $r->status,
                'decider_name' => $r->decider?->name
                    ?? (in_array($r->status, ['approved', 'rejected', 'fulfilled', 'cancelled']) ? 'MAS Administrator' : null),
                'decided_at' => $r->decided_at?->toDateTimeString(),
                'decision_reason' => $r->decision_reason,
                'created_at' => $r->created_at?->toDateTimeString(),
                'can_cancel' => $r->status === 'pending',
            ])->values(),
        ]);
    }

    public function assetRequestStore(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'needed_by' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $assetRequest = AssetRequest::create([
            'employee_id' => $employee->id,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'reason' => $data['reason'] ?? null,
            'needed_by' => $data['needed_by'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Aanvraag ingediend.',
            'request' => [
                'id' => $assetRequest->id,
                'status' => $assetRequest->status,
            ],
        ], 201);
    }

    public function assetRequestCancel(Request $request, AssetRequest $assetRequest): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        if ($assetRequest->employee_id !== $employee->id) {
            return response()->json(['message' => 'Niet toegestaan.'], 403);
        }

        if ($assetRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Alleen aanvragen in behandeling kunnen worden ingetrokken.'],
            ]);
        }

        $assetRequest->update([
            'status' => 'cancelled',
            'decided_at' => now(),
        ]);

        return response()->json(['message' => 'Aanvraag ingetrokken.']);
    }

    public function documentsIndex(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $docs = $employee->uploadedDocuments()->with('decider:id,name')->get();

        return response()->json([
            'categories' => EmployeeDocument::CATEGORIES,
            'documents' => $docs->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category,
                'category_label' => EmployeeDocument::CATEGORIES[$d->category] ?? $d->category,
                'notes' => $d->notes,
                'status' => $d->status,
                'decider_name' => $d->decider?->name
                    ?? (in_array($d->status, ['approved', 'rejected']) ? 'MAS Administrator' : null),
                'decided_at' => $d->decided_at?->toDateTimeString(),
                'decision_notes' => $d->decision_notes,
                'file_url' => $d->getFirstMediaUrl('file') ?: null,
                'file_name' => $d->getFirstMedia('file')?->file_name,
                'created_at' => $d->created_at?->toDateTimeString(),
                'can_delete' => $d->status === 'pending',
            ])->values(),
        ]);
    }

    public function documentsStore(Request $request): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(EmployeeDocument::CATEGORIES))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        $document->addMediaFromRequest('file')->toMediaCollection('file');

        return response()->json([
            'message' => 'Document geüpload.',
            'document' => [
                'id' => $document->id,
                'status' => $document->status,
            ],
        ], 201);
    }

    public function documentsDestroy(Request $request, EmployeeDocument $employeeDocument): JsonResponse
    {
        $employee = $this->employeeOrFail($request);

        if ($employeeDocument->employee_id !== $employee->id) {
            return response()->json(['message' => 'Niet toegestaan.'], 403);
        }

        if ($employeeDocument->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Alleen documenten in review kunnen worden verwijderd.'],
            ]);
        }

        $employeeDocument->delete();

        return response()->json(['message' => 'Document verwijderd.']);
    }

    private function employeeOrFail(Request $request): Employee
    {
        $employee = $request->user()?->employee;

        if (! $employee) {
            abort(response()->json([
                'message' => 'Aan dit account is geen medewerkersdossier gekoppeld.',
            ], 404));
        }

        return $employee;
    }
}
