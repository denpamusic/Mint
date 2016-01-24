<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->bigInteger('balance')->default(0);
			$table->bigInteger('total_received')->default(0);
			$table->integer('num_transactions')->default(0);
            $table->timestamps();
        });

		Schema::table('balances', function (Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('balances');
    }
}
