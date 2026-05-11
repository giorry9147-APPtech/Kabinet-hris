<?php

namespace App\Filament\Pages;

use App\Filament\Exports\AssetAssignmentExporter;
use App\Filament\Exports\CertificateExporter;
use App\Filament\Exports\EmployeeExporter;
use App\Filament\Exports\EmploymentRecordExporter;
use App\Filament\Exports\LeaveRequestExporter;
use App\Filament\Exports\SalaryAssignmentExporter;
use App\Models\AssetAssignment;
use App\Models\Certificate;
use App\Models\Employee;
use App\Models\EmploymentRecord;
use App\Models\LeaveRequest;
use App\Models\SalaryAssignment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ExportAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class Reports extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Rapportages';

    protected static ?string $navigationLabel = 'Rapportages';

    protected static ?string $title = 'Rapportages';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.reports';

    public function getViewData(): array
    {
        $now = Carbon::now();

        return [
            'sections' => [
                [
                    'title' => 'Personeelsoverzicht',
                    'description' => 'Compleet overzicht van alle medewerkers (actief + uitdienst), met functie, afdeling en contactgegevens.',
                    'icon' => 'heroicon-o-users',
                    'action' => 'exportEmployees',
                    'count_label' => Employee::where('status', 'active')->count().' actief, '
                        .Employee::where('status', 'inactive')->count().' inactief',
                ],
                [
                    'title' => 'Verlof-overzicht',
                    'description' => 'Alle verlofaanvragen met status, periode en goedkeuring. Filter standaard op huidig jaar.',
                    'icon' => 'heroicon-o-sun',
                    'action' => 'exportLeave',
                    'count_label' => LeaveRequest::whereYear('start_date', $now->year)->count().' aanvragen dit jaar',
                ],
                [
                    'title' => 'Verlopende certificaten',
                    'description' => 'STCW & andere certificaten die binnen 90 dagen verlopen of al verlopen zijn — kritisch voor maritieme operatie.',
                    'icon' => 'heroicon-o-academic-cap',
                    'action' => 'exportCertificates',
                    'count_label' => Certificate::whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now->copy()->addDays(90))->count().' verlopend/verlopen',
                ],
                [
                    'title' => 'Salariskosten',
                    'description' => 'Alle actuele en historische salaristoewijzingen met schaal, trede, basis en toelagen — input voor de begroting.',
                    'icon' => 'heroicon-o-banknotes',
                    'action' => 'exportSalary',
                    'count_label' => SalaryAssignment::whereNull('end_date')->count().' actuele toewijzingen',
                ],
                [
                    'title' => 'Asset-toewijzingen',
                    'description' => 'Wie heeft welk asset (laptop, telefoon, voertuig, radio) op welke datum ontvangen of geretourneerd.',
                    'icon' => 'heroicon-o-computer-desktop',
                    'action' => 'exportAssets',
                    'count_label' => AssetAssignment::whereNull('returned_at')->count().' actief uitgegeven',
                ],
                [
                    'title' => 'Dienstverband-mutaties',
                    'description' => 'Alle dienstverband-records: indiensttredingen, promoties, overplaatsingen, uitdienst.',
                    'icon' => 'heroicon-o-arrows-right-left',
                    'action' => 'exportEmployment',
                    'count_label' => EmploymentRecord::whereYear('start_date', $now->year)->count().' mutaties dit jaar',
                ],
            ],
        ];
    }

    public function exportEmployeesAction(): Action
    {
        return ExportAction::make('exportEmployees')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(EmployeeExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'personeelsoverzicht-'.now()->format('Y-m-d'));
    }

    public function exportLeaveAction(): Action
    {
        return ExportAction::make('exportLeave')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(LeaveRequestExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'verlofaanvragen-'.now()->format('Y-m-d'));
    }

    public function exportCertificatesAction(): Action
    {
        return ExportAction::make('exportCertificates')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(CertificateExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'certificaten-'.now()->format('Y-m-d'));
    }

    public function exportSalaryAction(): Action
    {
        return ExportAction::make('exportSalary')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(SalaryAssignmentExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'salaris-'.now()->format('Y-m-d'));
    }

    public function exportAssetsAction(): Action
    {
        return ExportAction::make('exportAssets')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(AssetAssignmentExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'assets-'.now()->format('Y-m-d'));
    }

    public function exportEmploymentAction(): Action
    {
        return ExportAction::make('exportEmployment')
            ->label('Exporteer naar Excel')
            ->color('primary')
            ->icon('heroicon-o-arrow-down-tray')
            ->exporter(EmploymentRecordExporter::class)
            ->formats([ExportFormat::Xlsx, ExportFormat::Csv])
            ->fileName(fn () => 'dienstverband-mutaties-'.now()->format('Y-m-d'));
    }
}
