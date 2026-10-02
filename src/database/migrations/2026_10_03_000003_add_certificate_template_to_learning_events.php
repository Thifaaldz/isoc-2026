<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->foreignId('certificate_template_id')
                ->nullable()
                ->after('module_template_id')
                ->constrained('certificate_templates')
                ->nullOnDelete();
        });

        $defaultTemplateId = DB::table('certificate_templates')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        DB::table('learning_events')
            ->orderBy('id')
            ->select(['id'])
            ->get()
            ->each(function (object $event) use ($defaultTemplateId): void {
                $eventTemplateId = DB::table('certificate_templates')
                    ->where('learning_event_id', $event->id)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->value('id');

                DB::table('learning_events')
                    ->where('id', $event->id)
                    ->update(['certificate_template_id' => $eventTemplateId ?: $defaultTemplateId]);
            });

        DB::table('certificates')
            ->whereNull('certificate_template_id')
            ->orderBy('id')
            ->select(['id', 'learning_event_id'])
            ->get()
            ->each(function (object $certificate) use ($defaultTemplateId): void {
                $templateId = DB::table('learning_events')
                    ->where('id', $certificate->learning_event_id)
                    ->value('certificate_template_id');

                DB::table('certificates')
                    ->where('id', $certificate->id)
                    ->update(['certificate_template_id' => $templateId ?: $defaultTemplateId]);
            });

        DB::table('certificate_templates')->update(['learning_event_id' => null]);
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('certificate_template_id');
        });
    }
};
