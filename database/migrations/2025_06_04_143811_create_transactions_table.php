<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade'); // A carteira que originou/recebeu a transação
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets')->onDelete('set null'); // Para transferências: a carteira de destino/origem
            $table->string('type'); // Armazenará o valor do Enum TransactionType
            $table->decimal('amount', 15, 2); // O valor da transação
            $table->text('description')->nullable(); // Descrição opcional da transação
            $table->boolean('is_reversal')->default(false); // Indica se esta transação é um estorno
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions')->onDelete('set null'); // Referência à transação original se for um estorno
            $table->timestamps();

            // Adiciona um índice para o tipo de transação para buscas mais rápidas
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
