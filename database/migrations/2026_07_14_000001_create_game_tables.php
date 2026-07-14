<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('state', 20)->default('lobby'); // lobby | playing | finished
            $table->string('pack', 30)->nullable();
            $table->boolean('ai')->default(false);
            $table->unsignedTinyInteger('total_rounds')->default(10);
            $table->json('questions')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('num'); // 1 ou 2
            $table->string('name', 30);
            $table->string('gender', 1)->default('m'); // m ou f, pour les accords
            $table->string('token', 64);
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
            $table->unique(['room_id', 'num']);
        });

        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('num');
            $table->string('question', 300);
            $table->unsignedTinyInteger('target_num'); // le joueur dont on devine la réponse
            $table->text('target_answer')->nullable();
            $table->text('guess_answer')->nullable();
            $table->boolean('correct')->nullable();
            $table->string('status', 20)->default('answering'); // answering | reveal | done
            $table->timestamps();
            $table->unique(['room_id', 'num']);
        });

        Schema::create('daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->string('question', 300);
            $table->text('answer1')->nullable();
            $table->text('answer2')->nullable();
            $table->timestamps();
            $table->unique(['room_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_entries');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('players');
        Schema::dropIfExists('rooms');
    }
};
