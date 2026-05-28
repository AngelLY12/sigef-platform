<?php

use App\Core\Domain\Enum\User\UserStatus;
use App\Models\Career;
use App\Models\User;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('name')->index();
            $table->string('phone_number',15)->after('last_name')->unique();
            $table->date('birthdate')->nullable()->after('phone_number');
            $table->string('gender',50)->nullable()->after('birthdate');
            $table->char('curp',18)->after('gender')->unique();
            $table->json('address')->nullable()->after('curp');
            $table->string('stripe_customer_id',100)->nullable()->unique();
            $table->char('blood_type',4)->nullable();
            $table->date('registration_date');
            $table->string('status',50)->default(UserStatus::ACTIVO->value)->index();
            $table->timestamp('mark_as_deleted_at')->nullable()->index();
            $table->index('created_at');
            $table->index(['status','created_at']);
            $table->index(['status','curp']);
            $table->index(['status','email']);
            $table->index(['id', 'status']);
            $table->index(['name','last_name']);
        });
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained('users')->onDelete('cascade');
            $table->foreignIdFor(Career::class)->nullable()->constrained('careers')->onDelete('set null');
            $table->string('n_control',30)->nullable()->unique();
            $table->tinyInteger('semestre')->nullable();
            $table->string('group', 10)->nullable();
            $table->string('workshop')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index(['career_id']);
            $table->index('semestre');
            $table->index('group');
            $table->index(['user_id', 'career_id']);
            $table->index(['career_id', 'semestre', 'group']);
            $table->index(['semestre', 'career_id']);
            $table->index(['semestre', 'user_id']);
            $table->index(['career_id', 'semestre', 'user_id']);

        });

    }





    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_details');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_name',
                'phone_number',
                'birthdate',
                'gender',
                'curp',
                'address',
                'registration_date',
                'status',
                'mark_as_deleted_at'
            ]);
        });
    }
};
