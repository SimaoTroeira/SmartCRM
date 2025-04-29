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
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
    
            // Relacionamentos obrigatórios
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
    
            // Dados Essenciais Genéricos (Cliente ou Pessoa)
            $table->string('customer_identifier', 100)->index()->nullable(); // Identificação genérica do cliente/pessoa
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
    
            // Dados do Produto ou Serviço (pode ser produto, serviço, comportamento, característica)
            $table->string('item_identifier', 100)->index()->nullable(); // Identificação genérica do item (produto ou serviço ou característica)
            $table->string('item_name')->nullable();
            $table->string('item_category')->nullable();
            $table->string('item_subcategory')->nullable();
            $table->decimal('item_price', 12, 2)->nullable();
    
            // Dados relacionados à aquisição/ação
            $table->string('transaction_identifier', 100)->nullable()->index(); // Agrupa múltiplos itens adquiridos juntos
            $table->dateTime('transaction_date')->nullable();
            $table->integer('quantity')->default(1)->nullable();;
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('purchase_channel')->nullable(); // online, loja física, telefone
            $table->string('payment_method')->nullable();
    
            // Dados Genéricos Adicionais para contexto amplo (útil para recomendações flexíveis)
            $table->string('attribute_1')->nullable();
            $table->string('attribute_2')->nullable();
            $table->string('attribute_3')->nullable();
            $table->string('attribute_4')->nullable();
            $table->string('attribute_5')->nullable();
    
            // Campos numéricos genéricos para clustering e segmentação (adaptáveis ao cenário do negócio)
            $table->decimal('numeric_attribute_1', 15, 4)->nullable();
            $table->decimal('numeric_attribute_2', 15, 4)->nullable();
            $table->decimal('numeric_attribute_3', 15, 4)->nullable();
    
            // Prevenção de duplicação de dados (únicos por empresa, campanha e identificação de cliente/item)
            $table->unique(['company_id', 'campaign_id', 'customer_identifier', 'item_identifier', 'transaction_identifier'], 'unique_import_entry');
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};
