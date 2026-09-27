<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('request_type')->default('UPDATE_PROFILE'); // UPDATE_PROFILE, CHANGE_PASSWORD
            $table->string('new_name')->nullable();
            $table->string('new_email')->nullable();
            $table->string('new_password')->nullable();
            $table->string('new_nip_nrp')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_requests');
    }
};
