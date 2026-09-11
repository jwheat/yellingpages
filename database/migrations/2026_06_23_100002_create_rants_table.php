<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rants', function (Blueprint $table) {
            $table->id();
            // Nullable: rants emailed in are authored anonymously (no site account).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            // Raw inbound text. NEVER exposed publicly — admin/audit only.
            $table->longText('original_body');
            // Scrubbed, alias-swapped text shown on the site (null until the job runs).
            $table->longText('scrubbed_body')->nullable();
            $table->string('excerpt', 320)->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('source')->default('web');
            // SHA-256 of the submitter's email — lets us rate-limit/ban without storing PII.
            $table->string('submitter_email_hash')->nullable()->index();
            // What the scrubber changed: [{type, alias, count}, ...]. No raw PII.
            $table->json('scrub_report')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rants');
    }
};
