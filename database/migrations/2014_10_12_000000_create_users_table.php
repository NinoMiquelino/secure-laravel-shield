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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            // Índices para performance
            $table->index('email');
            $table->index('created_at');
        });

        // Tabela para logs de segurança (opcional)
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('ip_address');
            $table->text('user_agent')->nullable();
            $table->text('details')->nullable();
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('severity');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('users');
    }
};