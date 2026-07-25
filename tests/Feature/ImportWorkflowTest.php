<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\Guide;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_csv_import_reports_duplicates_and_imports_only_new_rows(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        Guide::create(['name' => 'Existing Guide', 'email' => 'existing@example.com', 'license_number' => 'TATO-001']);
        $csv = implode("\n", [
            'Name,Email,Phone,Languages,License number,License expiry,Status',
            'Existing Guide,existing@example.com,+255 700 000 001,English,TATO-001,2027-12-31,available',
            'New Guide,new@example.com,+255 700 000 002,"English, French",TATO-002,2027-12-31,available',
        ]);

        $response = $this->actingAs($admin)->post(route('planner.guides.import'), [
            'file' => UploadedFile::fake()->createWithContent('guides.csv', $csv),
        ]);

        $response->assertRedirect(route('planner.guides'))
            ->assertSessionHas('import_report', fn (array $report) => $report['total'] === 2
                && $report['imported'] === 1
                && $report['duplicates'] === 1
                && $report['failed'] === []);
        $this->assertDatabaseHas('guides', ['name' => 'New Guide']);
        $this->assertDatabaseCount('guides', 2);
    }

    public function test_vehicle_import_is_atomic_and_returns_exact_failed_row_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $csv = implode("\n", [
            'Registration number,Type,Capacity,Status,Last service,Next service',
            'T 001 ABC,Land Cruiser,7,available,2026-01-01,2026-07-01',
            'T 002 ABC,Land Cruiser,not-a-number,available,2026-01-01,2026-07-01',
        ]);

        $response = $this->actingAs($admin)->post(route('planner.vehicles.import'), [
            'file' => UploadedFile::fake()->createWithContent('vehicles.csv', $csv),
        ]);

        $response->assertRedirect(route('planner.vehicles'))
            ->assertSessionHas('import_report', fn (array $report) => $report['total'] === 2
                && $report['imported'] === 0
                && count($report['failed']) === 1
                && str_contains($report['failed'][0]['reason'], 'Capacity'));
        $this->assertDatabaseMissing('vehicles', ['registration_number' => 'T 001 ABC']);
    }

    public function test_vehicle_import_supports_xlsx_files(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $xlsx = $this->xlsxContent([
            ['Registration number', 'Type', 'Capacity', 'Status'],
            ['T 009 XYZ', 'Land Cruiser', '7', 'available'],
        ]);

        $this->actingAs($admin)->post(route('planner.vehicles.import'), [
            'file' => UploadedFile::fake()->createWithContent('vehicles.xlsx', $xlsx),
        ])->assertSessionHas('import_report', fn (array $report) => $report['imported'] === 1 && $report['failed'] === []);

        $this->assertDatabaseHas('vehicles', ['registration_number' => 'T 009 XYZ', 'capacity' => 7]);
    }

    public function test_transfer_import_supports_csv_and_skips_a_duplicate(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $csv = implode("\n", [
            'Date,Pickup Time,Group / Guest Name,Driver / Guide,Contact Number,Supplier,Vehicle,Start Location,End Location,Notes,Status',
            '04/08/2026,08:30,Airport Group (2 pax),,,,,Kilimanjaro Airport,Arusha Coffee Lodge,KQ123,planned',
            '04/08/2026,08:30,Airport Group (2 pax),,,,,Kilimanjaro Airport,Arusha Coffee Lodge,KQ123,planned',
        ]);

        $response = $this->actingAs($admin)->post(route('planner.transfers.import', [2026, 'august']), [
            'file' => UploadedFile::fake()->createWithContent('transfers.csv', $csv),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('import_report', fn (array $report) => $report['total'] === 2
                && $report['imported'] === 1
                && $report['duplicates'] === 1
                && $report['failed'] === []);
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['type' => 'transfer', 'status' => 'planned']);
    }

    public function test_safari_import_rolls_back_all_rows_when_one_row_fails(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $csv = implode("\n", [
            'Reference,Group Name,PAX,Arrival Date,Departure Date,Status,Payment Status',
            'TS-ATOMIC-001,Valid Group,2,04/01/2026,05/01/2026,confirmed,pending',
            'TS-ATOMIC-002,Invalid Group,not-a-pax,04/01/2026,05/01/2026,confirmed,pending',
        ]);

        $response = $this->actingAs($admin)->post(route('safari-board.import', [2026, 'january']), [
            'file' => UploadedFile::fake()->createWithContent('safari.csv', $csv),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('import_report', function (array $report): bool {
                return $report['total'] === 2
                    && $report['imported'] === 0
                    && count($report['failed']) === 1
                    && str_contains(strtolower($report['failed'][0]['reason']), 'pax');
            });
        $this->assertDatabaseMissing('bookings', ['reference' => 'TS-ATOMIC-001']);
        $this->assertDatabaseMissing('bookings', ['reference' => 'TS-ATOMIC-002']);
    }

    private function xlsxContent(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.implode('', array_map(function (array $row, int $rowNumber): string {
            $cells = [];
            foreach ($row as $column => $value) {
                $reference = chr(65 + $column).($rowNumber + 1);
                $cells[] = '<c r="'.$reference.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            }
            return '<row r="'.($rowNumber + 1).'">'.implode('', $cells).'</row>';
        }, $rows, array_keys($rows))).'</sheetData></worksheet>');
        $zip->close();
        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }
}
