<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill skills from notes field for existing candidates
        // Notes format: "Skills: React, Node.js, Python" on its own line
        $candidates = DB::table('candidates')
            ->where(function ($q) {
                $q->whereNull('skills')->orWhere('skills', '[]')->orWhere('skills', 'null');
            })
            ->whereNotNull('notes')
            ->where('notes', 'like', '%Skills:%')
            ->get(['id', 'notes']);

        foreach ($candidates as $candidate) {
            // Extract "Skills: X, Y, Z" from notes
            if (preg_match('/Skills:\s*(.+)/i', $candidate->notes, $matches)) {
                $skillsLine = trim($matches[1]);
                // Split by comma and clean up
                $skills = array_values(array_filter(array_map('trim', explode(',', $skillsLine))));
                if (!empty($skills)) {
                    DB::table('candidates')
                        ->where('id', $candidate->id)
                        ->update(['skills' => json_encode($skills)]);
                }
            }
        }
    }

    public function down(): void
    {
        // No reversal needed — skills column remains, data just gets cleared
    }
};
