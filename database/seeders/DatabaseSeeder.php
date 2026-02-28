<?php

namespace Database\Seeders;

use App\Enums\OfferType;
use App\Enums\UserRole;
use App\Models\Offer;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test Bruger',
            'email' => 'test@koreskole.dk',
            'role' => UserRole::Admin,
        ]);

        $this->seedOffers();
    }

    private function seedOffers(): void
    {
        $packages = [
            [
                'name' => 'Normal lovpakke',
                'description' => "Ubegrænset teori\nManøvrebane\nGlatbane\n16 kørelektioner",
                'price' => 15500.00,
                'type' => OfferType::Primary,
                'theory_lessons' => 29,
                'driving_lessons' => 16,
                'track_required' => true,
                'slippery_required' => true,
                'image_url' => 'https://picsum.photos/seed/normal/800/600',
            ],
            [
                'name' => 'Studenterpakken',
                'description' => "Ubegrænset teori\nManøvrebane\nGlatbane\nKør aften og weekend og når det passer dig.\nFørstehjælp\nOnline teori (Gælder i 4 måneder)",
                'price' => 14500.00,
                'type' => OfferType::Primary,
                'theory_lessons' => 29,
                'driving_lessons' => 16,
                'track_required' => true,
                'slippery_required' => true,
                'image_url' => 'https://picsum.photos/seed/student/800/600',
            ],
            [
                'name' => 'Lynpakken',
                'description' => "Ubegrænset teori\nManøvrebane\nGlatbane\n16 kørelektioner\nOnlineteori (Gælder i 4 måneder)",
                'price' => 17500.00,
                'type' => OfferType::Primary,
                'theory_lessons' => 29,
                'driving_lessons' => 16,
                'track_required' => true,
                'slippery_required' => true,
                'image_url' => 'https://picsum.photos/seed/lyn/800/600',
            ],
        ];

        foreach ($packages as $pkgData) {
            $imageUrl = $pkgData['image_url'];
            unset($pkgData['image_url']);

            $offer = Offer::create($pkgData);

            try {
                $offer->addMediaFromUrl($imageUrl)->toMediaCollection('default');
            } catch (\Exception $e) {
                // Ignore if download fails
            }
        }

        $addons = [
            ['name' => 'Manøvrebane', 'description' => 'Lukket øvelsesplads 4 lektioner a 45 min.', 'price' => 2400.00],
            ['name' => 'Glatbane', 'description' => 'Køretekniks anlæg 4 lektioner a 45 min.', 'price' => 2500.00],
            ['name' => 'Kørelektion på 45 minutter', 'description' => 'Kørelektion på offentlig vej', 'price' => 500.00],
            ['name' => 'Kørelektion weekend/aften', 'description' => 'Kørelektion på 45 minutter i weekend og efter kl. 17', 'price' => 550.00],
            ['name' => 'Førstehjælp', 'description' => 'Lovpligtigt førsthjælpskursus', 'price' => 800.00],
            ['name' => 'Praktisk køreprøve', 'description' => 'Inklusiv en opvarmnings lektion', 'price' => 1500.00],
            ['name' => 'Administrationsgebyr', 'description' => 'Vi skal stå med godkendelse af ansøgning og oprettelse. Samt booking af den 1. Teori prøve og 1.Køreprøve', 'price' => 200.00],
            ['name' => 'Beramning af prøve', 'description' => 'Hvis man ikke klare prøverne og der skal bookes flere prøver', 'price' => 100.00],
            ['name' => 'Prøvegebyr', 'description' => 'Betales cirka 5 uger frem i forløbet via www.koreprovebooking.dk', 'price' => 1450.00],
            ['name' => 'Prøvegebyr (generhverv)', 'description' => 'Prøvegebyr til færdselsstyrelsen ved generhverv', 'price' => 1510.00],
            ['name' => 'Teoribog', 'description' => 'Teoribog', 'price' => 395.00],
        ];

        foreach ($addons as $addonData) {
            Offer::create([
                'name' => $addonData['name'],
                'description' => $addonData['description'],
                'price' => $addonData['price'],
                'type' => OfferType::Addon,
                'theory_lessons' => 0,
                'driving_lessons' => 0,
                'track_required' => false,
                'slippery_required' => false,
            ]);
        }
    }
}
