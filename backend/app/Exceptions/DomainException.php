<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Доменная ошибка с HTTP-кодом ответа.
 *
 * Используется бизнес-логикой (слой Services) для сигнализации о нарушении
 * правил. Рендерится в JSON {"message": ...} с указанным статусом.
 */
class DomainException extends RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 422)
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
