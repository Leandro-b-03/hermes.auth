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
        Schema::create('shippers', function (Blueprint $table) {
            $table->id();
            $table->string('tax_id')->unique()->nullable(false);
            $table->string('name')->nullable(false);
            $table->string('address')->nullable(false);
            $table->string('city')->nullable(false);
            $table->string('state')->nullable(false);
            $table->string('country')->nullable(false);
            $table->string('zip_code')->nullable(false);
            $table->string('contact_name')->nullable(false);
            $table->string('contact_email')->nullable(false);
            $table->string('contact_phone')->nullable();
            $table->string('contact_document')->nullable();
            $table->string('logo_image_url')->nullable();
            $table->unsignedBigInteger('shipper_matrix_id')->nullable();
            $table->timestamps();

            $table->foreign('shipper_matrix_id')->references('id')->on('shippers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shippers');
    }
};
