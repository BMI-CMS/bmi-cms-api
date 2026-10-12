<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContactInformation;

class ContactInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [
            [
                'contact_number' => '09171234567',
                'email' => 'rachel.delacruz@example.com',
                'social_media_account' => 'racheldelacruz',
            ],

            [
                'contact_number' => '09181234567',
                'email' => 'marife.castillo@example.com',
                'social_media_account' => 'marifecastillo',
            ],

            [
                'contact_number' => '09191234567',
                'email' => 'joseph.saludes@example.com',
                'social_media_account' => 'josephsaludes',
            ],

            [
                'contact_number' => '09201234567',
                'email' => 'christoper.deleon@example.com',
                'social_media_account' => 'christoperdeleon',
            ],

            [
                'contact_number' => '09211234567',
                'email' => 'maria.clara@example.com',
                'social_media_account' => 'mariaclara',
            ],

            [
                'contact_number' => '09221234567',
                'email' => 'juan.delacruz@example.com',
                'social_media_account' => 'juandelacruz',
            ],

            [
                'contact_number' => '09231234567',
                'email' => 'pedro.penduko@example.com',
                'social_media_account' => 'pedropenduko',
            ],

            [
                'contact_number' => '09241234567',
                'email' => 'ana.roces@example.com',
                'social_media_account' => 'anaroces',
            ],

            [
                'contact_number' => '09251234567',
                'email' => 'jose.rizal@example.com',
                'social_media_account' => 'joserizal',
            ],

            [
                'contact_number' => '09261234567',
                'email' => 'liza.soberano@example.com',
                'social_media_account' => 'lizasoberano',
            ],

            [
                'contact_number' => '09271234567',
                'email' => 'vic.sotto@example.com',
                'social_media_account' => 'vicsotto',
            ],

            [
                'contact_number' => '09281234567',
                'email' => 'sarah.geronimo@example.com',
                'social_media_account' => 'sarahgeronimo',
            ],

            [
                'contact_number' => '09291234567',
                'email' => 'coco.martin@example.com',
                'social_media_account' => 'cocomartin',
            ],

            [
                'contact_number' => '09301234567',
                'email' => 'kathryn.bernardo@example.com',
                'social_media_account' => 'kathrynbernardo',
            ],
        ];

        $count = 1;
        foreach ($contacts as $accountNumber => $contact) {
            ContactInformation::create([
                'account_id' =>  $count,
                ...$contact,
            ]);

            $count++;
        }
    }
}
