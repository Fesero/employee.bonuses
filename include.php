<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('employee.bonuses', [
    'EmployeeBonuses\\BonusTable'     => 'lib/BonusTable.php',
    'EmployeeBonuses\\BonusService'   => 'lib/BonusService.php',
    'EmployeeBonuses\\CrmDealHandler' => 'handlers/CrmDealHandler.php',
    'EmployeeBonuses\\RestHandler'    => 'handlers/RestHandler.php',
    'EmployeeBonuses\\MenuHelper'     => 'lib/MenuHelper.php',
]);
