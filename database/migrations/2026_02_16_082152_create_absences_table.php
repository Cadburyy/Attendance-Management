public function up(): void
{
    Schema::create('absences', function (Table $table) {
        $table->id();
        $table->string('employee_name');
        $table->date('date');
        $table->string('reason');
        $table->text('details')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });
}