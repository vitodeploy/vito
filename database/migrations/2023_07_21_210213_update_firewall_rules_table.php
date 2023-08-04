<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE firewall_rules MODIFY mask varchar(10) null');
    }

    public function down(): void
    {
        //
    }
};
