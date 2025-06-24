<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileAccessTokensTable extends Migration
{
    public function up()
    {
        Schema::create('file_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_access_tokens');
    }
}
