<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		DB::table('settings')->insert([
            'key'         => 'mint',
            'value'       => config('mint.version'),
			'description' => 'Mint API version.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'api_key',
            'value'       => str_random(10),
			'description' => 'API key that must be passed to Mint by your application.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'api_secret',
            'value'       => str_random(10),
			'description' => 'API secret that Mint will pass to your application with each callback.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'min_confirmations',
            'value'       => 6,
			'description' => 'Minimum confirmations required to fire a callback.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'callback_method',
            'value'       => 'get',
			'description' => 'Method used to call your callbacks.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'tx_fee',
            'value'       => number_format(0.00005, 8),
			'description' => 'Transaction fee for all outgoing non-local transactions.',
        ]);

		DB::table('settings')->insert([
            'key'         => 'merchant_fee',
            'value'       => number_format(0, 8),
			'description' => 'Merchant fee for all outgoing non-local transactions.',
        ]);
    }
}
