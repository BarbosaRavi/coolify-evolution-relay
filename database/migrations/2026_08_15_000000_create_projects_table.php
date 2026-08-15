<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('github_repository')->nullable()->unique();
            $table->string('coolify_project')->nullable();
            $table->string('whatsapp_group_jid')->nullable();
            $table->boolean('notify_push')->default(true);
            $table->boolean('notify_deploy')->default(true);
            $table->json('branches')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('coolify_project');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
