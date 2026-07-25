<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityAssignment;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Guide;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\Translator;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::create(['name' => 'Tanzania Specialist', 'country' => 'Netherlands', 'contact_email' => 'operations@tanzaniaspecialist.com']);
        $partner = Company::create(['name' => 'Safari Collective', 'country' => 'United Kingdom', 'contact_email' => 'ops@safaricollective.co.uk']);
        User::factory()->create(['name' => 'Lisa Mollel', 'email' => 'lisa@tanzaniaspecialist.com', 'role' => 'admin', 'job_title' => 'Operations manager', 'phone' => '+255 754 210 118', 'company_id' => $company->id]);
        User::factory()->create(['name' => 'Asha Kweka', 'email' => 'asha@tanzaniaspecialist.com', 'role' => 'editor', 'job_title' => 'Safari planner', 'company_id' => $company->id]);
        User::factory()->create(['name' => 'Miriam Paul', 'email' => 'miriam@tanzaniaspecialist.com', 'role' => 'commenter', 'job_title' => 'Guest relations', 'company_id' => $company->id]);
        User::factory()->create(['name' => 'Jonas Berg', 'email' => 'jonas@tanzaniaspecialist.com', 'role' => 'viewer', 'job_title' => 'Finance coordinator', 'company_id' => $company->id]);
        $supplier = Supplier::create(['name' => 'Gran Melia Arusha', 'type' => 'hotel', 'city' => 'Arusha']);
        $jro = Location::create(['name' => 'Kilimanjaro International Airport', 'code' => 'JRO', 'type' => 'airport', 'region' => 'Arusha']);
        $arusha = Location::create(['name' => 'Arusha', 'code' => 'ARK', 'type' => 'town', 'region' => 'Arusha']);
        $tarangire = Location::create(['name' => 'Tarangire National Park', 'code' => 'TAR', 'type' => 'park', 'region' => 'Manyara']);
        $joel = Guide::create(['name' => 'Joel Mollel', 'status' => 'on_trip', 'languages' => ['English', 'German'], 'license_number' => 'TATO-1184']);
        $amani = Guide::create(['name' => 'Amani Kweka', 'status' => 'on_trip', 'languages' => ['English', 'French'], 'license_number' => 'TATO-1042']);
        Guide::create(['name' => 'Sofia Nyerere', 'status' => 'available', 'languages' => ['English', 'Swedish', 'German'], 'license_number' => 'TATO-1320']);
        Guide::create(['name' => 'Peter Mushi', 'status' => 'on_trip', 'languages' => ['English', 'Spanish'], 'license_number' => 'TATO-1017']);
        $vehicle = Vehicle::create(['registration_number' => 'TZ 381 AB', 'capacity' => 7, 'status' => 'on_trip', 'type' => 'Land Cruiser 4x4']);
        Vehicle::create(['registration_number' => 'TZ 412 JX', 'capacity' => 7, 'status' => 'on_transfer', 'type' => 'Land Cruiser 4x4']);
        Vehicle::create(['registration_number' => 'TZ 228 LC', 'capacity' => 5, 'status' => 'available', 'type' => 'Land Cruiser 4x4']);
        Translator::create(['name' => 'Marta Klein', 'status' => 'available', 'languages' => ['German', 'English']]);

        $booking = Booking::create(['reference' => 'TS-2026-0184', 'group_name' => 'The Anderson Family', 'pax' => 4, 'company_id' => $company->id, 'country' => 'United States', 'language' => 'English', 'arrival_date' => '2026-06-14', 'departure_date' => '2026-06-21', 'plan_year' => 2026, 'plan_month' => 6, 'guide_id' => $joel->id, 'vehicle_id' => $vehicle->id, 'status' => 'confirmed', 'payment_status' => 'paid', 'total_amount' => 12840]);
        $activity = Activity::create(['booking_id' => $booking->id, 'supplier_id' => $supplier->id, 'type' => 'transfer', 'title' => 'KLM 567 · Airport pickup', 'start_location_id' => $jro->id, 'end_location_id' => $arusha->id, 'starts_at' => '2026-06-14 06:45', 'ends_at' => '2026-06-14 08:30', 'status' => 'confirmed', 'flight_number' => 'KL 567']);
        ActivityAssignment::create(['activity_id' => $activity->id, 'guide_id' => $joel->id, 'vehicle_id' => $vehicle->id, 'assignment_status' => 'assigned']);
        Payment::create(['booking_id' => $booking->id, 'amount' => 12840, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => '2026-05-30', 'method' => 'bank_transfer']);

        $second = Booking::create(['reference' => 'TS-2026-0183', 'group_name' => 'Wildlife Wanderers', 'pax' => 6, 'company_id' => $partner->id, 'country' => 'United Kingdom', 'language' => 'English', 'arrival_date' => '2026-06-14', 'departure_date' => '2026-06-19', 'plan_year' => 2026, 'plan_month' => 6, 'guide_id' => $amani->id, 'vehicle_id' => $vehicle->id, 'status' => 'in_progress', 'payment_status' => 'part_paid', 'total_amount' => 9650]);
        $safari = Activity::create(['booking_id' => $second->id, 'type' => 'safari', 'title' => 'Northern Circuit · Day 1', 'start_location_id' => $arusha->id, 'end_location_id' => $tarangire->id, 'starts_at' => '2026-06-14 08:30', 'ends_at' => '2026-06-14 17:00', 'status' => 'confirmed']);
        ActivityAssignment::create(['activity_id' => $safari->id, 'guide_id' => $amani->id, 'vehicle_id' => $vehicle->id, 'assignment_status' => 'assigned']);
        Payment::create(['booking_id' => $second->id, 'amount' => 5000, 'currency' => 'EUR', 'status' => 'paid', 'paid_at' => '2026-05-20', 'method' => 'bank_transfer']);
        Booking::create(['reference' => 'TS-2026-0182', 'group_name' => 'Mendoza Group', 'pax' => 8, 'company_id' => $company->id, 'country' => 'Spain', 'language' => 'Spanish', 'arrival_date' => '2026-06-14', 'departure_date' => '2026-06-17', 'plan_year' => 2026, 'plan_month' => 6, 'status' => 'confirmed', 'payment_status' => 'pending', 'total_amount' => 8400]);
        Booking::create(['reference' => 'TS-2026-0181', 'group_name' => 'Lena & Markus', 'pax' => 2, 'company_id' => $company->id, 'country' => 'Germany', 'language' => 'German', 'arrival_date' => '2026-06-15', 'departure_date' => '2026-06-22', 'plan_year' => 2026, 'plan_month' => 6, 'status' => 'confirmed', 'payment_status' => 'paid', 'total_amount' => 5400]);

        $this->call(TransferBoardDemoSeeder::class);
    }
}
