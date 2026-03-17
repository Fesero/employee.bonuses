<?php
declare(strict_types=1);

namespace EmployeeBonuses;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;

class BonusService
{
    /**
     * @param int $userId
     * @param float $amount
     * @param string $reason
     * @param string $sourceType
     * @param int|null $sourceId
     * @param int|null $createdBy
     * @throws \RuntimeException
     * @return array|int
     */
    public static function accrue(
        int    $userId,
        float  $amount,
        string $reason,
        string $sourceType = 'MANUAL',
        ?int   $sourceId = null,
        ?int   $createdBy = null
    ): int {
        if ($sourceId !== null && $sourceType !== 'MANUAL') {
            $isDuplicate = BonusTable::getCount([
                '=SOURCE_TYPE' => $sourceType,
                '=SOURCE_ID'   => $sourceId,
                '=USER_ID'     => $userId,
            ]) > 0;

            if ($isDuplicate) {
                return 0;
            }
        }

        $result = BonusTable::add([
            'USER_ID'     => $userId,
            'AMOUNT'      => round($amount, 2),
            'REASON'      => $reason,
            'SOURCE_TYPE' => $sourceType,
            'SOURCE_ID'   => $sourceId,
            'CREATED_AT'  => new DateTime(),
            'CREATED_BY'  => $createdBy,
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode(', ', $result->getErrorMessages()));
        }

        return $result->getId();
    }

    /**
     * @param int $userId
     * @return float
     */
    public static function getTotalByUser(int $userId): float
    {
        $row = BonusTable::query()
            ->addSelect(new ExpressionField('TOTAL', 'SUM(%s)', 'AMOUNT'))
            ->setFilter(['=USER_ID' => $userId])
            ->fetch();

        return round((float)($row['TOTAL'] ?? 0.0), 2);
    }

    /**
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int}
     */
    public static function getListForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $items = BonusTable::query()
            ->setSelect(['ID', 'USER_ID', 'AMOUNT', 'REASON', 'CREATED_AT'])
            ->setFilter(['=USER_ID' => $userId])
            ->setOrder(['CREATED_AT' => 'DESC'])
            ->setLimit($perPage)
            ->setOffset(($page - 1) * $perPage)
            ->fetchAll();

        return [
            'items' => $items,
            'total' => BonusTable::getCount(['=USER_ID' => $userId]),
        ];
    }
}
