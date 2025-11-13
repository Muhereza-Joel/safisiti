<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamMembersTable extends Migration
{
    public function up()
    {
        Schema::create('team_members', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid');
            $table->uuid('provider_user_uuid');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('designation');

            $table->unsignedBigInteger('organisation_id')->nullable();
            $table->uuid('organisation_uuid')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_members');
    }
}
