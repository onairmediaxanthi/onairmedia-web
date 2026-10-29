<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number')->nullable()->unique();
            $table->foreignId('quote_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage', 30)->default('received')->index();
            $table->string('payment_method', 20)->default('bank');
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->date('payment_due_on')->nullable();
            $table->timestamp('deposit_declared_at')->nullable();
            $table->string('deposit_note', 500)->nullable();
            $table->string('receipt_path')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('invoice_number', 60)->nullable();
            $table->string('invoice_path')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30)->nullable();
            $table->string('title');
            $table->text('note')->nullable();
            $table->boolean('internal')->default(false);
            $table->boolean('notified')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('from', 10)->default('customer'); // customer | staff
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('credit_allowed')->default(false)->after('admin_notes');
        });

        // Ό,τι είχε ήδη σταλεί για παραγγελία μπαίνει στη νέα ενότητα
        $quotes = DB::table('quotes')->whereIn('status', ['submitted', 'ordered'])->whereNull('deleted_at')->get();
        foreach ($quotes as $q) {
            $id = DB::table('orders')->insertGetId([
                'ulid' => (string) \Illuminate\Support\Str::ulid(),
                'quote_id' => $q->id, 'user_id' => $q->user_id,
                'business_id' => DB::table('businesses')->where('user_id', $q->user_id)->value('id'),
                'stage' => $q->status === 'ordered' ? 'awaiting_payment' : 'received',
                'payment_method' => 'bank', 'total' => $q->total,
                'starts_on' => $q->starts_on,
                'created_at' => $q->submitted_at ?? $q->updated_at, 'updated_at' => now(),
            ]);
            $year = \Illuminate\Support\Carbon::parse($q->submitted_at ?? $q->updated_at)->format('Y');
            DB::table('orders')->where('id', $id)->update(['number' => 'ΠΑΡ-'.$year.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT)]);
            DB::table('order_events')->insert(['order_id' => $id, 'stage' => 'received', 'title' => 'Λάβαμε την παραγγελία', 'created_at' => $q->submitted_at ?? $q->updated_at]);
        }
    }

    public function down(): void
    {
        Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn('credit_allowed'));
        Schema::dropIfExists('order_files');
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('orders');
    }
};
