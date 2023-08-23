<?php


use App\Models\ShippingProvider;
use Illuminate\Database\Seeder;

class ShippingProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ShippingProvider::create([
            'name' => 'local',
            'token' => null
        ]);

        ShippingProvider::create([
            'name' => 'vanex',
            'token' => null
        ]);
    }
}
