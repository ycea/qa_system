<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('database migrations run successfully', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('projects'))->toBeTrue();
    expect(Schema::hasTable('bugs'))->toBeTrue();
});
