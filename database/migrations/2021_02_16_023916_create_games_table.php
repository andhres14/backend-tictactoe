<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->unsignedBigInteger('first_player_id');
            $table->unsignedBigInteger('second_player_id');
            $table->string('box_1', 1)->nullable();
            $table->string('box_2', 1)->nullable();
            $table->string('box_3', 1)->nullable();
            $table->string('box_4', 1)->nullable();
            $table->string('box_5', 1)->nullable();
            $table->string('box_6', 1)->nullable();
            $table->string('box_7', 1)->nullable();
            $table->string('box_8', 1)->nullable();
            $table->string('box_9', 1)->nullable();
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->timestamps();

            $table->foreign('first_player_id')
                ->references('id')
                ->on('players');
            $table->foreign('second_player_id')
                ->references('id')
                ->on('players');
            $table->foreign('winner_id')
                ->references('id')
                ->on('players')
                ->onDelete('cascade');
            $table->enum('status', ['EN_PROCESO', 'FINALIZADO']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
