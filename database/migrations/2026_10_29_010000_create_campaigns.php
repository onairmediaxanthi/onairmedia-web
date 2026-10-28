<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Από πού δόθηκε/ανακλήθηκε η συγκατάθεση (απόδειξη για GDPR)
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('marketing_consent_source', 20)->nullable()->after('marketing_consent_at');
            $table->timestamp('marketing_withdrawn_at')->nullable()->after('marketing_consent_source');
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('subject', 150);
            $table->string('preheader', 200)->nullable();
            $table->text('body')->nullable();
            $table->json('package_ids')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url', 255)->nullable();
            $table->json('audience')->nullable();
            $table->string('status', 12)->default('draft'); // draft | scheduled | sending | sent | cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('status', 10)->default('pending'); // pending | sent | failed | skipped
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn(['marketing_consent_source', 'marketing_withdrawn_at']));
    }
};
