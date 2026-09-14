<?php

use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(GradeItem::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->decimal('points', 8, 2);
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->useCurrent();
            $table->foreignIdFor(User::class, 'graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grade_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_grades');
    }
};
