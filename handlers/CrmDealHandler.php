<?php
declare(strict_types=1);

namespace EmployeeBonuses;

use Bitrix\Main\Loader;

class CrmDealHandler
{
    private const BONUS_RATE  = 0.01;
    private const SOURCE_TYPE = 'CRM_DEAL';
    private const WON_STAGE   = 'WON';

    /**
     * @param array<string, mixed> $fields
     * @return void
     */
    public static function onAfterDealUpdate(array $fields): void
    {
        if (($fields['STAGE_ID'] ?? '') !== self::WON_STAGE) {
            return;
        }

        if (!Loader::includeModule('crm') || !Loader::includeModule('employee.bonuses')) {
            return;
        }

        $dealId = (int)($fields['ID'] ?? 0);
        if ($dealId <= 0) {
            return;
        }

        $deal = \CCrmDeal::GetByID($dealId);
        if (empty($deal)) {
            return;
        }

        $amount        = (float)($deal['OPPORTUNITY'] ?? 0);
        $responsibleId = (int)($deal['ASSIGNED_BY_ID'] ?? 0);

        if ($amount <= 0 || $responsibleId <= 0) {
            return;
        }

        try {
            BonusService::accrue(
                userId:     $responsibleId,
                amount:     $amount * self::BONUS_RATE,
                reason:     "Бонус за закрытую сделку №{$dealId}",
                sourceType: self::SOURCE_TYPE,
                sourceId:   $dealId,
                createdBy:  $responsibleId
            );
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                date('[Y-m-d H:i:s] ') . $e->getMessage() . PHP_EOL,
                '',
                '/upload/employee_bonuses.log'
            );
        }
    }
}
