# Sprint 6 — Echte MAS-data + Excel-importer

> **Doel**: Demo-medewerkers vervangen door echte MAS-medewerkers + Excel/CSV-import bouwen zodat HR voortaan zelf bulk-toevoegingen kan doen.
>
> **Effort**: 1-2 dagen (afhankelijk van data-kwaliteit van bron-bestand).
>
> **Blocker**: HR moet huidige personeels-export aanleveren (Excel/PDF/handmatig?).

## Vragen vóóraf

- Hoeveel medewerkers heeft MAS totaal?
- In welk formaat zit de huidige personeelsadministratie?
  - Eigen Excel-bestand → meest waarschijnlijk
  - Bestaand HR-pakket met export-functie?
  - Papier? (dan handmatig invoeren)
- Welke velden zijn verplicht en welke optioneel?
- Worden alle 38 org-eenheden uit het organogram daadwerkelijk gebruikt, of zijn er lege?
- Welke positie-codes wil HR aanhouden? (Onze huidige zijn placeholder, kan veranderen)

## Tasks

### A. Echte data inladen (handmatig of seeder)

**Optie 1 — Eenmalige seed van CSV:**
- [ ] HR levert `medewerkers.csv` met kolommen: personeelsnummer, voornaam, tussenvoegsel, achternaam, geboortedatum, geslacht (m/v/x), nationaliteit, ID-nr, e-mail, telefoon, adres, functie-code, schaal, trede, in-dienst-datum
- [ ] Maak `RealEmployeesSeeder` die de CSV inleest:
  ```php
  // database/seeders/RealEmployeesSeeder.php
  $rows = array_map('str_getcsv', file(database_path('seeds/medewerkers.csv')));
  $headers = array_shift($rows);
  foreach ($rows as $row) {
      $data = array_combine($headers, $row);
      Employee::updateOrCreate(['employee_number' => $data['personeelsnummer']], [...]);
  }
  ```
- [ ] Run `php artisan db:seed --class=RealEmployeesSeeder`
- [ ] HR controleert in admin of alles klopt
- [ ] Daarna: `DemoEmployeeSeeder` uit `DatabaseSeeder.run()` weghalen

**Optie 2 — Filament Importer (volgende sprint herbruikbaar):**
Zie sectie B hieronder.

### B. Filament Importer voor herhaaldelijk gebruik

Filament 3 heeft een `Import`-feature parallel aan `Export`. Eens gebouwd, kan HR voortaan zelf medewerkers in bulk toevoegen via een Excel-upload — zonder developer-hulp.

- [ ] `php artisan make:filament-importer Employee`
- [ ] Definieer kolommen + validatie in `app/Filament/Imports/EmployeeImporter.php`:
  ```php
  public static function getColumns(): array {
      return [
          ImportColumn::make('employee_number')->label('Personeelsnr')->requiredMapping()->rules(['required', 'unique:employees,employee_number']),
          ImportColumn::make('first_name')->label('Voornaam')->requiredMapping(),
          ImportColumn::make('last_name')->label('Achternaam')->requiredMapping(),
          ImportColumn::make('email')->rules(['email', 'nullable']),
          ImportColumn::make('phone')->rules(['nullable', 'string']),
          ImportColumn::make('gender')->rules(['nullable', 'in:m,v,x']),
          ImportColumn::make('joined_at')->castStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state) : null),
          // FK lookup op functie-code:
          ImportColumn::make('current_position_id')
              ->label('Functie-code')
              ->fillRecordUsing(function (Employee $record, string $state) {
                  $record->current_position_id = Position::where('code', $state)->value('id');
              }),
      ];
  }
  ```
- [ ] `resolveRecord()` voor updateOrCreate-gedrag op `employee_number`
- [ ] `getCompletedNotificationBody()` met Nederlandse tekst
- [ ] `ImportAction::make()->importer(EmployeeImporter::class)` toevoegen aan `ListEmployees::getHeaderActions()`

### C. Excel-template voor HR

- [ ] `docs/templates/employee-import-template.xlsx` met:
  - Eerste sheet: data (lege rijen + voorbeeld-rij in groen)
  - Tweede sheet: instructies (welke kolommen verplicht, welke waarden voor `gender`/`status`)
  - Derde sheet: alle beschikbare functie-codes (gegenereerd via export uit DB)
- [ ] HR krijgt training: "open template, vul rijen in, upload via Medewerkers → Importeren"
- [ ] Bij errors: Filament toont per-rij welke kolommen mislukten (uit `failed_import_rows` tabel die we al gemaakt hebben)

### D. Vergelijkbare importers (optioneel — zelfde patroon)

- [ ] **Position importer**: voor wanneer MAS de functietabel uitbreidt
- [ ] **Certificate importer**: bulk toevoegen van bestaande certs (kolommen: personeelsnr, type-code, nummer, uitgegeven, vervalt)
- [ ] **Salary assignment importer**: jaarlijkse salarisronde

### E. Cleanup

- [ ] `DemoEmployeeSeeder` uit `DatabaseSeeder.run()` halen
- [ ] `DemoEmployeeSeeder.php` blijft bestaan voor lokale dev / staging
- [ ] Alle 9 demo-user-accounts verwijderen via UI (Marlon, Marciano, etc.)
- [ ] 1 echte super-admin aanmaken via `SuperAdminSeeder` met **echte HR-manager e-mail**

## Acceptance criteria

- [ ] Lijst medewerkers in admin toont echte MAS-medewerkers (geen Roy Karijodikoro etc.)
- [ ] HR kan via Medewerkers → Importeren een Excel uploaden en X medewerkers in 1× toevoegen
- [ ] Foutieve rijen worden gerapporteerd met regel-nr en reden
- [ ] Bestaande medewerkers worden NIET gedupliceerd bij re-import (`updateOrCreate` op `employee_number`)
- [ ] Filament navigation toont "X medewerkers" met correct aantal

## Voorbeeld CSV-template

```csv
personeelsnummer,voornaam,tussenvoegsel,achternaam,geboortedatum,geslacht,nationaliteit,id_nummer,email,telefoon,adres,functie_code,schaal,trede,in_dienst_datum,status
MAS-1001,Anjali,,Ramdin,1985-03-15,v,Surinaams,FQ-12345,anjali.ramdin@mas.sr,+597 8123456,Domineestraat 12 Paramaribo,MA-001,15,4,2019-02-01,active
MAS-1002,Marlon,,Sapoen,1980-07-22,m,Surinaams,FQ-67890,marlon.sapoen@mas.sr,+597 8234567,Wanicastraat 45,OPS-001,15,6,2017-06-12,active
```

## Bekende valkuilen

- **CSV encoding**: HR's Excel slaat default op als CP1252 (Latin-1). Bij speciale tekens (Surinaamse namen met diakritiek) breekt UTF-8. Forceer "Save As → CSV UTF-8" in Excel.
- **Datum-formaat**: Excel toont `15-03-1985` maar slaat soms `1985-03-15` op of een Excel-serial number (31123). Carbon's `parse()` lost meestal op, maar test met edge cases.
- **Dubbele functie-codes**: HR kan in template een typo maken op `functie_code`. Build de importer zo dat een onbekende code een duidelijke error geeft, niet een silent NULL.
- **Telefoonnummers**: Surinaamse nummers met spaties of strepen — normaliseer in een mutator op het Employee-model.

## Wat NIET in deze sprint

- Migratie van CV's, foto's, certificaten als losse files → kan later via een `MediaImport`-script
- Migratie van historische verlof-aanvragen → niet zinvol, begin met clean slate
- Migratie van oude salaris-historie → alleen als HR het wil; meestal genoeg om met huidige salaris te starten
