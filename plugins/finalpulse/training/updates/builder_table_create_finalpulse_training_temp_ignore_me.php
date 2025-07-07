<?php namespace Finalpulse\Training\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateFinalpulseTrainingTempIgnoreMe extends Migration
{
    public function up()
    {
        Schema::create('finalpulse_training_temp_ignore_me', function($table)
        {
            $table->engine = 'InnoDB';
            $table->bigInteger('teste');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('finalpulse_training_temp_ignore_me');
    }
}
