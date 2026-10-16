<?php

use App\Services\PollResetAudit;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if ($report = PollResetAudit::read()) {
            PollResetAudit::write($report);
        }
    }

    public function down(): void
    {
    }
};
