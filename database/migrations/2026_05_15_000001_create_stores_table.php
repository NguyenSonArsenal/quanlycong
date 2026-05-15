<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration {
    public function up()
    {
        Schema::create('stores', function (Blueprint $table)
        {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('area_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stores');
    }
};
