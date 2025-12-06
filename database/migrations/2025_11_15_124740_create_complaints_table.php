<?php

use App\Enums\ComplaintCurrentStatus;
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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->nullable()->constrained('users' , 'id')->onDelete('set null');
            $table->foreignId('agency_id')->constrained('agencies' , 'id')->onDelete('restrict');
            $table->foreignId('complaint_type_id')->constrained('complaint_types' , 'id')->onDelete('restrict');
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users' , 'id');
            $table->string('title' , 100);
            $table->text('description');
            $table->string('location_text');
            $table->enum('current_status' , ComplaintCurrentStatus::convertEnumToArray())->default(ComplaintCurrentStatus::NEW->value);
            $table->unsignedBigInteger('number')->unique();
            $table->text('extra')->nullable();
            $table->boolean('has_extra_info')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(
                ['citizen_id', 'created_at'],
                'idx_complaints_citizen_created'
            );

            $table->unique(
                'number',
                'idx_complaints_number_unique'
            );

            $table->index(
                ['assigned_officer_id', 'created_at'],
                'idx_complaints_officer_created'
            );

            $table->index(
                ['agency_id', 'current_status', 'created_at'],
                'idx_complaints_agency_status_created'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
