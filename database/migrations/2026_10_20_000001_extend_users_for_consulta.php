<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('usuario', 100)->unique()->after('id');
            $table->string('perfil', 40)->default('consulta')->after('password');
            $table->string('setor', 100)->nullable()->after('perfil');
            $table->boolean('bloqueado')->default(false)->after('setor');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['usuario', 'perfil', 'setor', 'bloqueado']);
        });
    }
};
