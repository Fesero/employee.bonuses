<?php
declare(strict_types=1);

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use EmployeeBonuses\BonusService;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$request = Context::getCurrent()->getRequest();
$currentUser = CurrentUser::get();

if ((int)$currentUser->getId() <= 0) {
    LocalRedirect(SITE_LOGIN_PAGE . '?backurl=' . urlencode($request->getRequestUri()));
}

if (!Loader::includeModule('employee.bonuses')) {
    ShowError('Модуль employee.bonuses не установлен');
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
    return;
}

$APPLICATION->SetTitle('Мои бонусы');

$currentUserId = (int)$currentUser->getId();
$gridId = 'EMPLOYEE_BONUSES_GRID';
$navId = $gridId . '_NAV';

$gridOptions = new GridOptions($gridId);
$navParams = $gridOptions->GetNavParams(['nPageSize' => 20]);
$pageSize = max(1, min(50, (int)($navParams['nPageSize'] ?? 20)));

$nav = new PageNavigation($navId);
$nav->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();

$data = BonusService::getListForUser($currentUserId, $nav->getCurrentPage(), $pageSize);
$nav->setRecordCount((int)($data['total'] ?? 0));

$userRow = UserTable::getList([
    'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN'],
    'filter' => ['=ID' => $currentUserId],
    'limit' => 1,
])->fetch();

$userNameParts = array_filter([
    trim((string)($userRow['NAME'] ?? '')),
    trim((string)($userRow['LAST_NAME'] ?? '')),
], static fn(string $value): bool => $value !== '');

$userName = $userNameParts !== []
    ? implode(' ', $userNameParts)
    : ((string)($userRow['LOGIN'] ?? '') !== '' ? (string)$userRow['LOGIN'] : '#' . $currentUserId);

$userName = HtmlFilter::encode($userName);

$items = is_array($data['items'] ?? null) ? $data['items'] : [];

$rows = array_map(
    static function (array $item) use ($userName): array {
        $createdAt = $item['CREATED_AT'] ?? null;
        $createdAtValue = $createdAt instanceof DateTime
            ? $createdAt->format('d.m.Y H:i')
            : HtmlFilter::encode((string)$createdAt);

        return [
            'id' => (int)($item['ID'] ?? 0),
            'columns' => [
                'USER_NAME' => $userName,
                'CREATED_AT' => $createdAtValue,
                'AMOUNT' => number_format((float)($item['AMOUNT'] ?? 0), 2, '.', ' ') . ' ₽',
                'REASON' => HtmlFilter::encode((string)($item['REASON'] ?? '')),
            ],
        ];
    },
    $items
);

$totalAmount = BonusService::getTotalByUser($currentUserId);
?>

<div class="pagetitle-container pagetitle-align-right-container">
    <div class="pagetitle">
        <h1 class="pagetitle-title"><?= HtmlFilter::encode($APPLICATION->GetTitle(false)) ?></h1>
    </div>
    <div class="pagetitle-toolbar">
        <span class="pagetitle-toolbar-text">
            Итого: <strong><?= number_format($totalAmount, 2, '.', ' ') ?> ₽</strong>
        </span>
    </div>
</div>

<?php
$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $gridId,
    'COLUMNS' => [
        ['id' => 'USER_NAME', 'name' => 'Сотрудник', 'default' => true],
        ['id' => 'CREATED_AT', 'name' => 'Дата начисления', 'default' => true],
        ['id' => 'AMOUNT', 'name' => 'Сумма бонуса', 'default' => true],
        ['id' => 'REASON', 'name' => 'Причина', 'default' => true],
    ],
    'ROWS' => $rows,
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_ROW_CHECKBOXES' => false,
    'SHOW_CHECK_ALL' => 'N',
    'SHOW_ACTION_PANEL' => 'N',
    'SHOW_PAGINATION' => 'Y',
    'SHOW_PAGESIZE' => 'Y',
    'PAGE_SIZES' => [
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
    ],
    'SHOW_TOTAL_ROWS_COUNT' => 'Y',
    'TOTAL_ROWS_COUNT_HTML' => 'Всего записей: ' . (int)($data['total'] ?? 0),
], false);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';