<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('estudios', function (Blueprint $table) {
        $table->text('leyenda')->nullable()->after('nombre');
    });
}

public function down()
{
    Schema::table('estudios', function (Blueprint $table) {
        $table->dropColumn('leyenda');
    });
}

};
