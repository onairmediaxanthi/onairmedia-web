<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Προσφορές που φτιάχνουν οι πελάτες στο Studio Καμπάνιας
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 20)->unique()->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('source', 20); // wizard | manual | package
            $table->json('answers')->nullable();
            $table->json('items');
            $table->date('starts_on')->nullable();
            $table->unsignedSmallInteger('days')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('vat', 10, 2);
            $table->decimal('total', 10, 2);
            $table->date('valid_until');
            $table->string('status', 20)->default('saved'); // saved | submitted | ordered | expired
            $table->timestamp('submitted_at')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
            $table->boolean('reminders_off')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'deleted_at']);
        });

        // Τι κάνουν οι πελάτες (για ενημέρωση της εταιρείας)
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('summary', 255);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
        Schema::dropIfExists('quotes');
    }
};
