<?php

use Illuminate\Support\Facades\Schema;
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
            $table->string('role')->default("user");
            $table->string('type');
            $table->string('firstName');
            $table->string('lastName');
            $table->date("birth");
            $table->string('gender');
            $table->string('address');
            $table->string('district');
            $table->string('county');
            $table->string('nickname')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('isAdmin')->default(0);
            $table->float("card")->nullable();
            $table->string('avatar')->default('user.jpg');
            $table->float('points')->default(0);
            $table->integer('timesRated')->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
