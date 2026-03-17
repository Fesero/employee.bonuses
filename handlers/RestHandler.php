<?php
declare(strict_types=1);

namespace EmployeeBonuses;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Rest\RestException;

class RestHandler
{
    /**
     * @return array{offgroup: array}|null
     */
    public static function onBuildDescription(): ?array
    {
        if (!Loader::includeModule('employee.bonuses')) {
            return [];
        }

        return [
            'offgroup' => [
                'bonuses.gettotal' => [
                    'callback' => [static::class, 'getTotal'],
                    'options'  => [],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @param int $start
     * @param \CRestServer $server
     * @throws RestException
     * @return array{total: float}
     */
    public static function getTotal(array $params, int $start, \CRestServer $server): array
    {
        $employeeId = (int)($params['employee_id'] ?? 0);

        if ($employeeId <= 0) {
            throw new RestException(
                'Parameter employee_id is required and must be a positive integer',
                RestException::ERROR_ARGUMENT,
                400
            );
        }

        if (!UserTable::getById($employeeId)->fetch()) {
            throw new RestException(
                "Employee #{$employeeId} not found",
                RestException::ERROR_NOT_FOUND,
                404
            );
        }

        return ['total' => BonusService::getTotalByUser($employeeId)];
    }
}
