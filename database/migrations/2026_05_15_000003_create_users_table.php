<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('full_name');
            $table->string('role');
            
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            
            $table->string('contract_type')->nullable();
            $table->decimal('hourly_rate', 15, 2)->default(0);
            
            $table->integer('status')->default(1)->comment('1: active, 2: nghỉ việc');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
