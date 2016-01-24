<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->integer('user_id')->unsigned();
			$table->string('address', 48)->nullable()->unique()->index();
			$table->text('label')->nullable();
			$table->bigInteger('balance')->default(0);
			$table->bigInteger('total_received')->default(0);
			$table->integer('num_transactions')->default(0);
            $table->timestamps();
        });

		Schema::table('addresses', function (Blueprint $table) {
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
        Schema::drop('addresses');
    }
}
