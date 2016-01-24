<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
			$table->string('guid')->unique();
			$table->string('password', 64);
			$table->string('email', 64);
			$table->string('name');
			$table->text('callback_url');
			$table->text('blocknotify_url');
			$table->text('rpc_connection');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
