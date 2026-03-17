<?php
declare(strict_types=1);

namespace EmployeeBonuses;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type\DateTime;

class BonusTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_employee_bonus';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary'      => true,
                'autocomplete' => true,
            ]),
            new IntegerField('USER_ID', [
                'required' => true,
            ]),
            (new FloatField('AMOUNT', [
                'required' => true,
            ]))->addValidator(static function (mixed $value): bool|string {
                return $value > 0 ?: 'Сумма должна быть больше нуля';
            }),
            new StringField('REASON', [
                'required'   => true,
                'validation' => static fn() => [new LengthValidator(1, 512)],
            ]),
            new StringField('SOURCE_TYPE', [
                'default_value' => 'MANUAL',
            ]),
            new DatetimeField('CREATED_AT', [
                'required'      => true,
                'default_value' => static fn() => new DateTime(),
            ]),
            new IntegerField('SOURCE_ID', [
                'nullable' => true,
            ]),
            new IntegerField('CREATED_BY', [
                'nullable' => true,
            ]),
        ];
    }
}
