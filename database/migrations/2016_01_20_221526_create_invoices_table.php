<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
		    $table->integer('user_id')->unsigned();
		    $table->bigInteger('address_id')->unsigned();
		    $table->string('destination_address', 48)->nullable();
		    $table->text('label')->nullable();
		    $table->bigInteger('invoice_amount')->default(0);
		    $table->text('callback_url')->nullable();
		    $table->boolean('received')->default(false);
		    $table->boolean('forward')->default(false);
		    $table->bigInteger('received_amount')->default(0);
            $table->timestamps();
        });

		Schema::table('invoices', function (Blueprint $table) {
			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('address_id')->references('id')->on('addresses');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invoices');
    }
}
