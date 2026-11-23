<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per greeting card page visit or card download.
        Schema::create('greeting_card_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);                    // visit | download
            $table->string('visitor_id', 36);              // random UUID from a 1-year cookie (no IP stored)
            $table->string('device', 16)->nullable();      // mobile | tablet | desktop
            $table->boolean('in_app_browser')->default(false); // Facebook / Messenger / Instagram app browser
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['type', 'created_at']);
            $table->index('visitor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greeting_card_events');
    }
};
