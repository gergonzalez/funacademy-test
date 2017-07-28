<?php
/**
 * Users Migration.
 *
 * @author     German Gonzalez Rodriguez <ger@gergonzalez.com>
 * @copyright  German Gonzalez Rodriguez
 *
 * @version    1.0
 */
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        //Create Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');

            $table->integer('userable_id');
            $table->string('userable_type');
            $table->boolean('active')->default(0);

            $table->timestamps();
        });

        //Create Admins Table
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        //Create Website Users Table
        Schema::create('website_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('mobile');
        });

        //Create Providers Table
        Schema::create('providers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_name');
            $table->string('phone')->default('');
            $table->string('iban');
            $table->string('company_description', 511);
            $table->integer('discount');
        });

        //Create Retailers Table
        Schema::create('retailers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('responsible_name');
            $table->string('phone');
            $table->string('mobile');
            $table->string('address');
            $table->string('iban');
        });

        //Create Providers-Retailers Intermediate Table
        Schema::create('provider_retailer', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_id')->unsigned();
            $table->integer('retailer_id')->unsigned();
            $table->boolean('accepted')->default(0);
            $table->foreign('provider_id')->references('id')->on('providers');
            $table->foreign('retailer_id')->references('id')->on('retailers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('provider_retailer');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('retailers');
        Schema::dropIfExists('admin');
        Schema::dropIfExists('website_users');
    }
}
