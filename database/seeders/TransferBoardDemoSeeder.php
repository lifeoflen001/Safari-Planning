<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityAssignment;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Guide;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class TransferBoardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['name' => 'Tanzania Specialist'],
            ['country' => 'Tanzania', 'contact_email' => 'operations@tanzaniaspecialist.com']
        );

        $supplier = Supplier::firstOrCreate(
            ['name' => 'TS Transfers'],
            ['type' => 'transport', 'city' => 'Arusha', 'status' => 'active']
        );

        $locations = collect([
            ['name' => 'Kilimanjaro International Airport', 'code' => 'JRO', 'type' => 'airport'],
            ['name' => 'Arusha Coffee Lodge', 'code' => 'ARK', 'type' => 'hotel'],
            ['name' => 'Gran Melia Arusha', 'code' => 'ARK', 'type' => 'hotel'],
            ['name' => 'Tarangire Safari Lodge', 'code' => 'TAR', 'type' => 'lodge'],
            ['name' => 'Arusha Airport', 'code' => 'ARK', 'type' => 'airport'],
        ])->mapWithKeys(function (array $location): array {
            return [$location['name'] => Location::firstOrCreate(
                ['name' => $location['name']],
                ['code' => $location['code'], 'type' => $location['type'], 'region' => 'Northern Tanzania']
            )->id];
        });

        $guides = collect(['Joel Mollel', 'Amani Kweka', 'Sofia Nyerere', 'Peter Mushi'])
            ->mapWithKeys(fn (string $name): array => [$name => Guide::firstOrCreate(
                ['name' => $name],
                ['status' => 'available', 'languages' => ['English'], 'license_number' => 'DEMO-'.strtoupper(substr(md5($name), 0, 5))]
            )->id]);

        $vehicles = collect(['T 648 EDR', 'T 208 DUQ', 'T 351 ERS', 'T 720 AVA'])
            ->mapWithKeys(fn (string $registration): array => [$registration => Vehicle::firstOrCreate(
                ['registration_number' => $registration],
                ['capacity' => 7, 'status' => 'available', 'type' => 'Land Cruiser 4x4']
            )->id]);

        $rows = [
            ['reference' => 'DEMO-TS-0701', 'date' => '2026-07-01', 'time' => '05:45', 'group' => 'Flavia Bernhard (2 pax)', 'pax' => 2, 'guide' => 'Joel Mollel', 'contact' => '+255 754 533 815', 'vehicle' => 'T 648 EDR', 'start' => 'Kilimanjaro International Airport', 'end' => 'Gran Melia Arusha', 'flight' => 'KL 569', 'notes' => 'Meet at arrivals · luggage assistance', 'status' => 'confirmed'],
            ['reference' => 'DEMO-TS-0702', 'date' => '2026-07-01', 'time' => '07:25', 'group' => 'Sandra Firla (2 pax)', 'pax' => 2, 'guide' => 'Amani Kweka', 'contact' => '+255 713 334 516', 'vehicle' => 'T 208 DUQ', 'start' => 'Kilimanjaro International Airport', 'end' => 'Arusha Coffee Lodge', 'flight' => 'KQ 567', 'notes' => 'Flight arrives 07:15', 'status' => 'planned'],
            ['reference' => 'DEMO-TS-0703', 'date' => '2026-07-01', 'time' => '09:15', 'group' => 'Piet Kuivenhoven (6 pax)', 'pax' => 6, 'guide' => 'Sofia Nyerere', 'contact' => '+255 762 640 277', 'vehicle' => 'T 351 ERS', 'start' => 'Arusha Coffee Lodge', 'end' => 'Tarangire Safari Lodge', 'flight' => '', 'notes' => 'Early breakfast packed', 'status' => 'in_progress'],
            ['reference' => 'DEMO-TS-0704', 'date' => '2026-07-01', 'time' => '12:15', 'group' => 'Michael Franke (4 pax)', 'pax' => 4, 'guide' => 'Peter Mushi', 'contact' => '+255 690 137 000', 'vehicle' => 'T 720 AVA', 'start' => 'Gran Melia Arusha', 'end' => 'Kilimanjaro International Airport', 'flight' => 'LH 4396', 'notes' => 'Allow extra time for roadworks', 'status' => 'delayed'],
            ['reference' => 'DEMO-TS-0705', 'date' => '2026-07-02', 'time' => '06:30', 'group' => 'Torsten Bielmann (3 pax)', 'pax' => 3, 'guide' => 'Joel Mollel', 'contact' => '+255 769 977 950', 'vehicle' => 'T 648 EDR', 'start' => 'Kilimanjaro International Airport', 'end' => 'Arusha Coffee Lodge', 'flight' => 'LH 4396', 'notes' => 'German speaking guide requested', 'status' => 'confirmed'],
            ['reference' => 'DEMO-TS-0706', 'date' => '2026-07-02', 'time' => '10:45', 'group' => 'Leon Fasel (2 pax)', 'pax' => 2, 'guide' => null, 'contact' => '+255 763 168 706', 'vehicle' => null, 'start' => 'Arusha Coffee Lodge', 'end' => 'Tarangire Safari Lodge', 'flight' => '', 'notes' => 'Assignment still required', 'status' => 'planned'],
            ['reference' => 'DEMO-TS-0707', 'date' => '2026-07-03', 'time' => '08:00', 'group' => 'Ralf Gervais (4 pax)', 'pax' => 4, 'guide' => 'Amani Kweka', 'contact' => '+255 763 271 235', 'vehicle' => 'T 208 DUQ', 'start' => 'Kilimanjaro International Airport', 'end' => 'Gran Melia Arusha', 'flight' => 'AF 4136', 'notes' => 'Airport pickup · name board', 'status' => 'completed'],
            ['reference' => 'DEMO-TS-0708', 'date' => '2026-07-03', 'time' => '16:15', 'group' => 'Jennifer Parker (2 pax)', 'pax' => 2, 'guide' => 'Sofia Nyerere', 'contact' => '+255 746 528 889', 'vehicle' => 'T 351 ERS', 'start' => 'Gran Melia Arusha', 'end' => 'Arusha Airport', 'flight' => 'PW 718', 'notes' => 'Check-in closes at 17:20', 'status' => 'confirmed'],
            ['reference' => 'DEMO-TS-0709', 'date' => '2026-07-04', 'time' => '07:35', 'group' => 'Christoffer Grundbalsen (3 pax)', 'pax' => 3, 'guide' => 'Peter Mushi', 'contact' => '+255 783 221 488', 'vehicle' => 'T 720 AVA', 'start' => 'Kilimanjaro International Airport', 'end' => 'Arusha Coffee Lodge', 'flight' => 'LH 4396', 'notes' => 'Child seat required', 'status' => 'planned'],
            ['reference' => 'DEMO-TS-0710', 'date' => '2026-07-05', 'time' => '20:00', 'group' => 'Matthew Joly (2 pax)', 'pax' => 2, 'guide' => null, 'contact' => '+255 713 949 191', 'vehicle' => null, 'start' => 'Kilimanjaro International Airport', 'end' => 'Gran Melia Arusha', 'flight' => 'KL 569', 'notes' => 'Late arrival · confirm key handover', 'status' => 'planned'],
        ];

        foreach ($rows as $row) {
            $booking = Booking::firstOrCreate(
                ['reference' => $row['reference']],
                [
                    'group_name' => $row['group'],
                    'pax' => $row['pax'],
                    'company_id' => $company->id,
                    'arrival_date' => $row['date'],
                    'departure_date' => $row['date'],
                    'plan_year' => 2026,
                    'plan_month' => 7,
                    'status' => 'confirmed',
                    'payment_status' => 'pending',
                ]
            );

            $startsAt = $row['date'].' '.$row['time'];
            $transfer = Activity::updateOrCreate(
                ['booking_id' => $booking->id, 'type' => 'transfer'],
                [
                    'supplier_id' => $supplier->id,
                    'title' => 'Transfer · '.$row['group'],
                    'start_location_id' => $locations[$row['start']],
                    'end_location_id' => $locations[$row['end']],
                    'starts_at' => $startsAt,
                    'ends_at' => date('Y-m-d H:i:s', strtotime($startsAt.' +90 minutes')),
                    'status' => $row['status'],
                    'flight_number' => $row['flight'] ?: null,
                    'contact_number' => $row['contact'],
                    'notes' => $row['notes'],
                ]
            );

            if ($row['guide'] || $row['vehicle']) {
                ActivityAssignment::updateOrCreate(
                    ['activity_id' => $transfer->id],
                    [
                        'guide_id' => $row['guide'] ? $guides[$row['guide']] : null,
                        'vehicle_id' => $row['vehicle'] ? $vehicles[$row['vehicle']] : null,
                        'assignment_status' => 'assigned',
                    ]
                );
            }
        }
    }
}
