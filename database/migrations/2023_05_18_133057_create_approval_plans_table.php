<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payreq_id');
            $table->foreignId('approver_id');
            $table->string('status')->default('pending'); // pending | approved | rejected | revised
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_plans');
    }
};
