<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->bigIncrements('conversation_id');
            $table->bigInteger('user_id');
            $table->text('subject')->nullable();
            $table->float('lat', 10, 6)->nullable();
            $table->float('lon', 10, 6)->nullable();
            $table->timestamp('last_active')->default(\Carbon\Carbon::now());
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}
