<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policlinica_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id')->nullable()->unique();
            $table->string('paciente_nome')->nullable();
            $table->string('paciente_cpf', 11)->nullable()->index();
            $table->string('paciente_cns', 15)->nullable()->index();
            $table->string('tipo', 20)->nullable();
            $table->string('procedimento')->nullable();
            $table->string('telefone', 40)->nullable();
            $table->string('unidade')->nullable();
            $table->string('profissional')->nullable();
            $table->date('data_agendamento')->nullable()->index();
            $table->string('status', 40)->nullable();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('policlinica_cache_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('janela_meses');
            $table->unsignedInteger('linhas')->default(0);
            $table->boolean('sucesso')->default(false);
            $table->text('mensagem')->nullable();
            $table->timestamp('iniciado_em')->useCurrent();
            $table->timestamp('terminado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policlinica_cache_syncs');
        Schema::dropIfExists('policlinica_cache');
    }
};
