<?php

use Illuminate\Database\Seeder;

class BalanceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		DB::table('balances')->insert([
			'user_id'          => 1,
            'balance'          => 0,
            'total_received'   => 0,
			'num_transactions' => 0,
		]);
    }
}
