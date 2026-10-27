<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'owner_id'];

    /**
     * Загружаем дефолтные справочники при создании проекта.
     */
    protected static function booted(): void
    {
        static::created(function (Project $project) {
            $project->createDefaultDictionaries();
        });
    }

    /**
     * Создание дефолтных статусов, приоритетов и серьёзностей.
     */
    public function createDefaultDictionaries(): void
    {
        // Статусы (BUG-04)
        $statuses = [
            ['name' => 'Новая',       'is_default' => true],
            ['name' => 'Назначена',   'is_default' => false],
            ['name' => 'В работе',    'is_default' => false],
            ['name' => 'Исправлена',  'is_default' => false],
            ['name' => 'Переоткрыта', 'is_default' => false],
            ['name' => 'Закрыта',     'is_default' => false],
        ];
        foreach ($statuses as $status) {
            $this->statuses()->create($status);
        }

        // Приоритеты (Lab 1, п.5)
        $priorities = ['low', 'normal', 'major', 'critical'];
        foreach ($priorities as $name) {
            $this->priorities()->create(['name' => $name]);
        }

        // Серьёзности (стандартная шкала)
        $severities = ['Blocker', 'Critical', 'Major', 'Minor', 'Trivial'];
        foreach ($severities as $name) {
            $this->severities()->create(['name' => $name]);
        }
    }

    // --- Связи ---
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }

    public function priorities(): HasMany
    {
        return $this->hasMany(Priority::class);
    }

    public function severities(): HasMany
    {
        return $this->hasMany(Severity::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }
}
