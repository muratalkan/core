<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateExtensionFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*$query = "CREATE TABLE public.extension_files (
            id uuid NULL,
            extension_id uuid NULL,
            name varchar NULL,
            sha256sum varchar NULL,
            extension_data bytea NULL,
            created_at timestamp(0) NULL,
            updated_at timestamp(0) NULL,
            CONSTRAINT extension_files_fk_1 FOREIGN KEY (extension_id) REFERENCES public.extensions(id) ON DELETE CASCADE
        );";
        DB::statement($query);*/
        Schema::create("extension_files", function (Blueprint $table) {
            $table->uuid("id");
            $table->uuid("extension_id");
            $table
                ->foreign("extension_id")
                ->references("id")
                ->on("extensions")
                ->onDelete("cascade");
            $table->string('name');
            $table->string('sha256sum');
            $table->binary("extension_data");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extension_files');
    }
}
