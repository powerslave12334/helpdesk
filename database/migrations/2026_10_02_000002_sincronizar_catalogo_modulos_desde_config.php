<?php

use App\Support\Modulos;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Modulos::sync();
    }

    public function down(): void
    {
        // Sincronización desde config/modulos.php: no reversible.
    }
};
