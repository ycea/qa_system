<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database migrations run successfully', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('users'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('projects'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('bugs'))->toBeTrue();
});
