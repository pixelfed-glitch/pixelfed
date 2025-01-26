<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Status;

return new class extends Migration
{
    public function up(): void
    {
        Status::query()
            ->where('local', true)
            ->where('type', 'share')
            ->whereHas('profile', function($query) {
                $query->whereDoesntHave('user');
            })
            ->chunkById(100, function($statuses) {
                foreach($statuses as $status) {
                    $status->local = false;
                    $status->save();
                }
            });
    }

    public function down(): void
    {
        // No down migration needed since this is a data fix
    }
};
