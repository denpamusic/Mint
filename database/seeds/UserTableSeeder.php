<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{	
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		DB::table('users')->insert([
            'guid'           => Uuid::generate(4),
            'password'       => str_random(10),
			'name'           => 'mint',
			'rpc_connection' => 'http://mint:' . str_random(10) . '@127.0.0.1:8332',
		]);
    }
}
