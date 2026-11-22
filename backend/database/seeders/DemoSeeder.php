<?php

namespace Database\Seeders;

use App\Models\Bug;
use App\Models\MetricValue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демонстрационные данные для показа работы системы.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    private const PROJECT_SHOP = 'Интернет-магазин';

    private const PROJECT_MOBILE = 'Мобильное приложение';

    /**
     * Названия баг-репортов (по 15 на проект).
     */
    private const BUG_TITLES = [
        'Не открывается корзина',
        'Ошибка 500 при оформлении заказа',
        'Не приходит письмо с подтверждением',
        'Кнопка «Купить» неактивна',
        'Неверная сумма заказа при скидке',
        'Поиск не находит товар по артикулу',
        'Зависает загрузка каталога',
        'Не сохраняется адрес доставки',
        'Дублируются позиции в заказе',
        'Ошибка валидации промокода',
        'Не отображается история заказов',
        'Сбрасывается авторизация',
        'Не работают фильтры категорий',
        'Пустая страница товара',
        'Не отправляется отзыв',
    ];

    private const STATUSES = ['Новый', 'Назначен', 'В работе', 'Исправлен', 'Переоткрыт', 'Закрыт'];

    private const PRIORITIES = ['low', 'normal', 'major', 'critical'];

    private const SEVERITIES = ['Trivial', 'Minor', 'Major', 'Critical', 'Blocker'];

    public function run(): void
    {
        $admin = $this->user('admin', 'admin@example.com');
        $developer = $this->user('developer', 'developer@example.com');
        $tester = $this->user('tester', 'tester@example.com');
        $extra = $this->user('user', 'user@example.com');

        // Идемпотентность: не дублируем демо-данные при повторном запуске.
        if (Project::where('name', self::PROJECT_SHOP)->exists()) {
            return;
        }

        $shop = $this->project(self::PROJECT_SHOP, 'Витрина, корзина и оформление заказа.', $admin);
        $shop->members()->attach($admin->id, ['role' => 'admin']);
        $shop->members()->attach($developer->id, ['role' => 'developer']);
        $shop->members()->attach($tester->id, ['role' => 'tester']);
        $shop->members()->attach($extra->id, ['role' => 'developer']);

        $mobile = $this->project(self::PROJECT_MOBILE, 'Клиентское приложение iOS/Android.', $admin);
        $mobile->members()->attach($admin->id, ['role' => 'admin']);
        $mobile->members()->attach($developer->id, ['role' => 'developer']);

        $this->seedBugs($shop, $admin, [$developer, $tester]);
        $this->seedBugs($mobile, $admin, [$developer]);
        $this->seedMetrics($shop, $admin);
    }

    private function user(string $name, string $email): User
    {
        return User::firstOrCreate(
            ['name' => $name],
            ['email' => $email, 'password' => self::PASSWORD],
        );
    }

    private function project(string $name, string $description, User $owner): Project
    {
        return Project::create([
            'name' => $name,
            'description' => $description,
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Баги, история изменений и комментарии для проекта.
     *
     * @param  array<int, User>  $assignees
     */
    private function seedBugs(Project $project, User $author, array $assignees): void
    {
        foreach (self::BUG_TITLES as $index => $title) {
            $statusName = self::STATUSES[$index % count(self::STATUSES)];
            $assignee = $statusName === 'Новый' ? null : $assignees[$index % count($assignees)];

            // Часть закрытых багов — в пределах недели, часть — старше (для DASH/REPT).
            $recentClose = intdiv($index, count(self::STATUSES)) % 2 === 0;
            $closedAt = $statusName === 'Закрыт'
                ? now()->subDays($recentClose ? 2 : 20)
                : null;

            /** @var Bug $bug */
            $bug = $project->bugs()->create([
                'status_id' => $project->statuses()->where('name', $statusName)->value('id'),
                'priority_id' => $project->priorities()->where('name', self::PRIORITIES[$index % 4])->value('id'),
                'severity_id' => $project->severities()->where('name', self::SEVERITIES[$index % 5])->value('id'),
                'assignee_id' => $assignee?->id,
                'created_by' => $author->id,
                'title' => $title,
                'description' => "Описание дефекта: {$title}.",
                'steps_to_reproduce' => "1. Открыть раздел.\n2. Воспроизвести: {$title}.",
                'closed_at' => $closedAt,
            ]);

            $bug->histories()->create(['user_id' => $author->id, 'action' => 'created']);

            if ($statusName !== 'Новый') {
                $bug->histories()->create([
                    'user_id' => $author->id,
                    'action' => 'status_changed',
                    'field' => 'status',
                    'old_value' => 'Новый',
                    'new_value' => $statusName,
                ]);
            }

            if ($assignee) {
                $bug->histories()->create([
                    'user_id' => $author->id,
                    'action' => 'assignee_assigned',
                    'field' => 'assignee',
                    'new_value' => $assignee->name,
                ]);
            }

            if ($index % 3 === 0) {
                $bug->comments()->create([
                    'user_id' => $assignees[$index % count($assignees)]->id,
                    'body' => 'Проверил — воспроизводится регулярно.',
                ]);
            }
        }
    }

    /**
     * Метрики и значения (METR-01..METR-03) для проекта.
     */
    private function seedMetrics(Project $project, User $author): void
    {
        $timeMetric = $project->metrics()->create([
            'name' => 'Время исправления, ч',
            'type' => 'numeric',
        ]);

        $componentMetric = $project->metrics()->create([
            'name' => 'Компонент',
            'type' => 'categorical',
            'options' => ['UI', 'API', 'DB'],
        ]);

        $components = ['UI', 'API', 'DB'];

        foreach ($project->bugs()->orderBy('id')->limit(8)->get() as $offset => $bug) {
            MetricValue::create([
                'metric_id' => $timeMetric->id,
                'bug_id' => $bug->id,
                'user_id' => $author->id,
                'numeric_value' => 2 + $offset * 3,
                'recorded_at' => now()->subDays(1 + $offset),
            ]);

            MetricValue::create([
                'metric_id' => $componentMetric->id,
                'bug_id' => $bug->id,
                'user_id' => $author->id,
                'categorical_value' => $components[$offset % 3],
                'recorded_at' => now()->subDays(1 + $offset),
            ]);
        }
    }
}
