<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('tx_id', 64)->index();
			$table->integer('user_id')->unsigned();
			$table->string('address_to', 48)->nullable();
			$table->string('address_from', 48)->nullable();
			$table->bigInteger('crypto_amount')->default(0);
			$table->integer('confirmations')->default(0);
			$table->text('response_callback')->nullable();
			$table->boolean('callback_status')->default(false);
			$table->text('callback_url')->nullable();
			$table->text('block_hash')->nullable();
			$table->integer('block_index')->nullable();
			$table->integer('block_time')->nullable();
			$table->integer('tx_time')->default(0);
			$table->integer('tx_timereceived')->default(0);
			$table->string('tx_category', 24)->nullable();
			$table->integer('network_fee')->nullable();
			$table->integer('merchant_fee')->nullable();
			$table->bigInteger('address_balance')->default(0);
			$table->bigInteger('user_balance')->default(0);
			$table->decimal('bitcoind_balance', 16, 8)->nullable();
			$table->text('note')->nullable();
			$table->string('transaction_type', 24);
            $table->timestamps();
        });

		Schema::table('transactions', function (Blueprint $table) {
			$table->unique(['tx_id', 'address_to', 'address_from', 'tx_time', 'transaction_type'], 'tx_uniq');
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
        Schema::drop('transactions');
    }
}
