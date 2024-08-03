<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['assistant_id']);
            $table->dropColumn('assistant_id');
        });

        Schema::create('assistant_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['assistant_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_project');

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('assistant_id')->constrained('assistants');
        });
    }
};
