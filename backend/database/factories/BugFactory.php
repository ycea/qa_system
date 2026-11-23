<?php

namespace Database\Factories;

use App\Models\Bug;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Severity;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class BugFactory extends Factory
{
    protected $model = Bug::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Bug $bug) {
            if (! $bug->project_id) {
                $bug->project_id = Project::factory()->create()->id;
            }
            if (! $bug->status_id) {
                $bug->status_id = Status::where('project_id', $bug->project_id)->first()?->id
                    ?? Status::factory()->create(['project_id' => $bug->project_id])->id;
            }
            if (! $bug->priority_id) {
                $bug->priority_id = Priority::where('project_id', $bug->project_id)->first()?->id
                    ?? Priority::factory()->create(['project_id' => $bug->project_id])->id;
            }
            if (! $bug->severity_id) {
                $bug->severity_id = Severity::where('project_id', $bug->project_id)->first()?->id
                    ?? Severity::factory()->create(['project_id' => $bug->project_id])->id;
            }
            $bug->save();
        });
    }
}
