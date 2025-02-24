<?php

declare(strict_types=1);

namespace Akira\Followable\Tests\Fixtures\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });
    }
};
