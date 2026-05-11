<?php

namespace Database\Seeders;

use App\Models\AssetRequest;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Vult de overige modules met realistische demo-data zodat ALLE
 * navigatie-items in de admin zichtbaar gevulde lijsten tonen:
 *   - Kabinet-specifieke certificate types + ~20 certificaten met
 *     verschillende vervalstatus (verlopen / < 30d / < 90d / geldig)
 *   - Asset-requests in diverse statussen (pending / approved / rejected)
 *   - Employee documents (CV / ID / contract / medisch / belasting)
 */
class DemoFillSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCertificateTypes();
        $this->seedCertificates();
        $this->seedAssetRequests();
        $this->seedEmployeeDocuments();
    }

    private function seedCertificateTypes(): void
    {
        $types = [
            ['code' => 'KAB-INT',   'name' => 'Integriteits- en anti-corruptietraining', 'category' => 'Integriteit', 'months' => 24],
            ['code' => 'KAB-GEH',   'name' => 'Geheimhoudings- en vertrouwelijkheidsverklaring', 'category' => 'Integriteit', 'months' => null],
            ['code' => 'KAB-AVG',   'name' => 'AVG / Privacy-training', 'category' => 'Compliance', 'months' => 24],
            ['code' => 'KAB-EHBO',  'name' => 'EHBO-certificaat', 'category' => 'Veiligheid', 'months' => 24],
            ['code' => 'KAB-WAP',   'name' => 'Wapenvergunning Persoonsbeveiliging', 'category' => 'Beveiliging', 'months' => 12],
            ['code' => 'KAB-PBEV',  'name' => 'Opleiding Persoonsbeveiliging (PB) President', 'category' => 'Beveiliging', 'months' => 36],
            ['code' => 'KAB-VOG',   'name' => 'Verklaring Omtrent het Gedrag (VOG)', 'category' => 'Integriteit', 'months' => 36],
            ['code' => 'KAB-PROT',  'name' => 'Diplomatiek protocol & etiquette', 'category' => 'Protocol', 'months' => 60],
            ['code' => 'DRIVE-C',   'name' => 'Rijbewijs C (vrachtwagen/zware voertuigen)', 'category' => 'Algemeen', 'months' => 60],
            ['code' => 'DRIVE-D',   'name' => 'Rijbewijs D (bus, ceremonieel transport)', 'category' => 'Algemeen', 'months' => 60],
            ['code' => 'KAB-MED',   'name' => 'Medische keuring (jaarlijks)', 'category' => 'Medisch', 'months' => 12],
            ['code' => 'KAB-ICT',   'name' => 'ICT-beveiliging / cyber-awareness', 'category' => 'ICT', 'months' => 12],
        ];

        foreach ($types as $t) {
            CertificateType::updateOrCreate(
                ['code' => $t['code']],
                [
                    'name' => $t['name'],
                    'category' => $t['category'],
                    'requires_expiry' => $t['months'] !== null,
                    'default_validity_months' => $t['months'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedCertificates(): void
    {
        $byEmp = fn (string $no) => Employee::where('employee_number', $no)->first();
        $byType = fn (string $code) => CertificateType::where('code', $code)->value('id');

        // [empNum, typeCode, number, issuer, issued, expiresOffsetDays (null=geen vervaldatum)]
        $rows = [
            // Sergio - Kabinetschef
            ['KAB-0001', 'KAB-INT',  'INT-2024-001', 'Stichting Integriteit Suriname', '2024-03-15', 365 * 1 - 30],
            ['KAB-0001', 'KAB-GEH',  'GEH-2020-001', 'Kabinet van de President',       '2020-07-16', null],
            ['KAB-0001', 'KAB-AVG',  'AVG-2025-005', 'DataPrivacy Suriname',           '2025-02-10', 365 * 1 + 45],
            ['KAB-0001', 'KAB-VOG',  'VOG-2024-A12', 'Procureur-Generaal',             '2024-01-08', 365 * 2 - 90],

            // Soraya - Juridisch
            ['KAB-0002', 'KAB-AVG',  'AVG-2024-018', 'DataPrivacy Suriname',           '2024-11-20', 200],
            ['KAB-0002', 'KAB-GEH',  'GEH-2020-002', 'Kabinet van de President',       '2020-09-15', null],
            ['KAB-0002', 'KAB-VOG',  'VOG-2023-B05', 'Procureur-Generaal',             '2023-12-10', 365 * 1 + 200],

            // Anand - Communicatie
            ['KAB-0003', 'KAB-INT',  'INT-2024-008', 'Stichting Integriteit Suriname', '2024-04-22', -45], // verlopen
            ['KAB-0003', 'KAB-GEH',  'GEH-2020-003', 'Kabinet van de President',       '2020-10-01', null],

            // Marlinde - Protocol
            ['KAB-0004', 'KAB-PROT', 'PROT-2022-01', 'Ministerie van Buitenlandse Zaken', '2022-06-15', 365 * 3 + 60],
            ['KAB-0004', 'KAB-EHBO', 'EHBO-2024-12', 'Rode Kruis Suriname',            '2024-05-20', 25], // bijna verlopen
            ['KAB-0004', 'KAB-GEH',  'GEH-2021-004', 'Kabinet van de President',       '2021-01-12', null],

            // Kenrick - HR
            ['KAB-0005', 'KAB-AVG',  'AVG-2025-001', 'DataPrivacy Suriname',           '2025-01-15', 360],
            ['KAB-0005', 'KAB-INT',  'INT-2025-003', 'Stichting Integriteit Suriname', '2025-03-01', 365 * 2 - 30],

            // Rianne - Financiën
            ['KAB-0006', 'KAB-AVG',  'AVG-2024-022', 'DataPrivacy Suriname',           '2024-08-10', 85], // < 90d
            ['KAB-0006', 'KAB-INT',  'INT-2024-014', 'Stichting Integriteit Suriname', '2024-10-05', 365 * 1 + 150],

            // Tarun - ICT
            ['KAB-0007', 'KAB-ICT',  'ICT-2025-002', 'CERT Suriname',                  '2025-02-18', 280],
            ['KAB-0007', 'KAB-AVG',  'AVG-2024-009', 'DataPrivacy Suriname',           '2024-06-30', 50], // < 90d

            // Lakshmi - HR
            ['KAB-0008', 'KAB-EHBO', 'EHBO-2025-04', 'Rode Kruis Suriname',            '2025-03-12', 365 * 2 - 60],

            // Miguel - Communicatie
            ['KAB-0009', 'KAB-INT',  'INT-2025-009', 'Stichting Integriteit Suriname', '2025-04-08', 365 * 2 - 30],

            // Farah - Beleid
            ['KAB-0010', 'KAB-GEH',  'GEH-2021-010', 'Kabinet van de President',       '2021-09-15', null],
            ['KAB-0010', 'KAB-INT',  'INT-2024-021', 'Stichting Integriteit Suriname', '2024-12-04', 365 * 1 + 180],

            // Quincy - Hoofd Beveiliging
            ['KAB-0011', 'KAB-WAP',  'WAP-2025-001', 'Korps Politie Suriname',         '2025-01-10', 240],
            ['KAB-0011', 'KAB-PBEV', 'PB-2023-001',  'NCBS - Suriname',                '2023-08-15', 365 * 2 + 120],
            ['KAB-0011', 'KAB-EHBO', 'EHBO-2024-30', 'Rode Kruis Suriname',            '2024-07-22', 75], // < 90d
            ['KAB-0011', 'DRIVE-C',  'RBW-C-3344',   'CBB Verkeer en Vervoer',         '2022-04-10', 365 * 3 + 90],

            // Devika - Protocol
            ['KAB-0012', 'KAB-EHBO', 'EHBO-2024-44', 'Rode Kruis Suriname',            '2024-09-18', 130],
            ['KAB-0012', 'KAB-PROT', 'PROT-2023-09', 'Ministerie van Buitenlandse Zaken', '2023-11-05', 365 * 3 + 200],
        ];

        foreach ($rows as $r) {
            $employee = $byEmp($r[0]);
            $typeId = $byType($r[1]);
            if (! $employee || ! $typeId) continue;

            $issued = $r[4];
            $expires = $r[5] === null ? null : Carbon::today()->addDays($r[5])->toDateString();

            Certificate::updateOrCreate(
                ['employee_id' => $employee->id, 'certificate_type_id' => $typeId, 'number' => $r[2]],
                [
                    'issuer' => $r[3],
                    'issued_at' => $issued,
                    'expires_at' => $expires,
                ]
            );
        }
    }

    private function seedAssetRequests(): void
    {
        $byEmp = fn (string $no) => Employee::where('employee_number', $no)->first();
        $sergio = User::where('email', 'sergio.akiemboto@kabinet.sr')->first();

        $rows = [
            // Pending - door Sergio te beslissen
            ['KAB-0010', 'Laptop',  'Vervanging laptop adviseur beleid',          'Huidige laptop vertraagt sterk, batterij houdt niet meer. Voor beleidsanalyses heb ik snellere machine nodig.', 14,  'pending',  null, null, null],
            ['KAB-0009', 'Mobiel',  'Werktelefoon — persvoorlichter',             'Op pad tijdens persconferenties, eigen telefoon volstaat niet meer voor het verkeer.', 7,   'pending',  null, null, null],
            ['KAB-0007', 'Server',  'Backup-server Kabinet',                       'Lokale backup-server is verouderd, geen redundantie meer. Voorstel voor aanschaf nieuwe NAS-appliance.', 30, 'pending',  null, null, null],

            // Approved
            ['KAB-0004', 'Voertuig', 'Tweede protocolauto staatsbezoek juni',     'Voor inkomend staatsbezoek juni hebben we extra voertuig nodig voor de delegatie.', 45, 'approved', $sergio?->id, -3, 'Akkoord — leasing via FAB tot eind juni.'],
            ['KAB-0011', 'Beveiligingsmateriaal', 'Aanvullende communicatie-apparatuur PB', 'Twee extra portofoons voor uitbreiding persoonsbeveiligingsteam.', 21, 'approved', $sergio?->id, -10, 'Akkoord. Aanschaf via standaard beveiligingsleverancier.'],

            // Rejected
            ['KAB-0008', 'Laptop',  'Tweede laptop HR-medewerker',                 'Voor mobiel werken vanuit huis een tweede laptop.', 14, 'rejected', $sergio?->id, -5, 'Afgewezen. Huidige laptop voldoet; thuiswerk-toegang via VPN volstaat.'],
        ];

        foreach ($rows as $r) {
            $employee = $byEmp($r[0]);
            if (! $employee) continue;

            $neededBy = is_int($r[4]) ? Carbon::today()->addDays($r[4])->toDateString() : $r[4];
            $decidedAt = $r[7] === null ? null : Carbon::now()->addDays($r[7]);

            AssetRequest::updateOrCreate(
                ['employee_id' => $employee->id, 'subject' => $r[2]],
                [
                    'category' => $r[1],
                    'reason' => $r[3],
                    'needed_by' => $neededBy,
                    'status' => $r[5],
                    'decided_by' => $r[6],
                    'decided_at' => $decidedAt,
                    'decision_reason' => $r[8],
                ]
            );
        }
    }

    private function seedEmployeeDocuments(): void
    {
        $byEmp = fn (string $no) => Employee::where('employee_number', $no)->first();
        $sergio = User::where('email', 'sergio.akiemboto@kabinet.sr')->first();

        // Elke kerneenheid krijgt een setje typische documenten — placeholders zonder echte bestanden
        $byEmpDocs = [
            'KAB-0001' => ['ID-kopie paspoort','Diploma Master Public Administration','Arbeidscontract Kabinetschef','Recente medische keuring','Loonbelastingverklaring 2026'],
            'KAB-0002' => ['ID-kopie paspoort','Diploma Master Rechten','Arbeidscontract Hoofd Juridische Zaken','Loonbelastingverklaring 2026'],
            'KAB-0003' => ['ID-kopie paspoort','Diploma Communicatiewetenschappen','Arbeidscontract Hoofd Communicatie','Loonbelastingverklaring 2026'],
            'KAB-0004' => ['ID-kopie paspoort','Certificaat Diplomatiek Protocol','Arbeidscontract Hoofd Protocol','Loonbelastingverklaring 2026','Recente medische keuring'],
            'KAB-0005' => ['ID-kopie paspoort','Diploma HR-management','Arbeidscontract Hoofd Personeelszaken','Loonbelastingverklaring 2026'],
            'KAB-0006' => ['ID-kopie paspoort','Diploma Bedrijfseconomie','Arbeidscontract Hoofd Financiën','Loonbelastingverklaring 2026'],
            'KAB-0007' => ['ID-kopie paspoort','Diploma Informatica','Detacheringsovereenkomst Telesur','Loonbelastingverklaring 2026'],
            'KAB-0009' => ['ID-kopie paspoort','Diploma Journalistiek','Arbeidscontract Persvoorlichter (tijdelijk)','Loonbelastingverklaring 2026'],
            'KAB-0010' => ['ID-kopie paspoort','Diploma MSc Economie','Consultancy-overeenkomst Senior Beleidsadviseur','Loonbelastingverklaring 2026'],
            'KAB-0011' => ['ID-kopie paspoort','Politiediploma + wapencertificaat','Arbeidscontract Hoofd Beveiliging','Recente medische keuring','Loonbelastingverklaring 2026'],
        ];

        $categoryMap = [
            'ID-kopie' => 'id_copy',
            'paspoort' => 'id_copy',
            'Diploma' => 'diploma',
            'Master' => 'diploma',
            'MSc' => 'diploma',
            'contract' => 'contract',
            'Detachering' => 'contract',
            'Consultancy' => 'contract',
            'Certificaat' => 'diploma',
            'Politiediploma' => 'diploma',
            'medische' => 'medical',
            'Loonbelasting' => 'tax',
        ];

        $detectCategory = function (string $title) use ($categoryMap): string {
            foreach ($categoryMap as $needle => $cat) {
                if (stripos($title, $needle) !== false) return $cat;
            }
            return 'other';
        };

        foreach ($byEmpDocs as $empNo => $titles) {
            $employee = $byEmp($empNo);
            if (! $employee) continue;

            foreach ($titles as $idx => $title) {
                $category = $detectCategory($title);
                // Approved voor de meeste, één per medewerker pending om de inbox-flow te tonen
                $status = $idx === 0 ? 'pending' : 'approved';
                $decidedAt = $status === 'approved' ? Carbon::now()->subDays(rand(1, 90)) : null;

                EmployeeDocument::updateOrCreate(
                    ['employee_id' => $employee->id, 'title' => $title],
                    [
                        'category' => $category,
                        'status' => $status,
                        'decided_by' => $status === 'approved' ? $sergio?->id : null,
                        'decided_at' => $decidedAt,
                        'notes' => $status === 'approved' ? null : 'Door medewerker ingediend, ter beoordeling.',
                        'decision_notes' => $status === 'approved' ? 'Beoordeeld en akkoord bevonden.' : null,
                    ]
                );
            }
        }
    }
}
