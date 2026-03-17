<?php

namespace EmployeeBonuses;

use Bitrix\Main\Config\Option;

class MenuHelper
{
    private const SITE_ID     = 's1';
    private const ITEM_ID     = 'menu_employee_bonuses';
    private const ITEM_LINK   = '/bonuses/';
    private const ITEM_TEXT   = 'Мои бонусы';

    public static function install(): void
    {
        Option::set('intranet', 'left_menu_preset', 'custom', false, self::SITE_ID);

        $items = self::getItems();

        foreach ($items as $item) {
            if (($item['ID'] ?? '') === self::ITEM_ID) {
                return;
            }
        }

        $items[] = [
            'ID'   => self::ITEM_ID,
            'LINK' => self::ITEM_LINK,
            'TEXT' => self::ITEM_TEXT,
        ];

        self::saveItems($items);
        self::addToSort();
    }

    public static function uninstall(): void
    {
        $items = self::getItems();
        $items = array_values(array_filter(
            $items,
            static fn(array $item): bool => ($item['ID'] ?? '') !== self::ITEM_ID
        ));
        self::saveItems($items);
        self::removeFromSort();
    }

    private static function getItems(): array
    {
        $raw = \COption::GetOptionString('intranet', 'left_menu_custom_preset_items', '', self::SITE_ID);
        if (empty($raw)) {
            return [];
        }
        $items = unserialize($raw, ['allowed_classes' => false]);
        return is_array($items) ? $items : [];
    }

    private static function saveItems(array $items): void
    {
        \COption::SetOptionString('intranet', 'left_menu_custom_preset_items', serialize($items), false, self::SITE_ID);
    }

    private static function addToSort(): void
    {
        $raw  = \COption::GetOptionString('intranet', 'left_menu_custom_preset_sort', '', self::SITE_ID);
        $sort = $raw ? unserialize($raw, ['allowed_classes' => false]) : [];
        $sort = is_array($sort) ? $sort : [];

        if (!isset($sort['show']) || !is_array($sort['show'])) {
            $sort['show'] = [];
        }

        if (!in_array(self::ITEM_ID, $sort['show'], true)) {
            $sort['show'][] = self::ITEM_ID;
        }

        \COption::SetOptionString('intranet', 'left_menu_custom_preset_sort', serialize($sort), false, self::SITE_ID);
    }

    private static function removeFromSort(): void
    {
        $raw  = \COption::GetOptionString('intranet', 'left_menu_custom_preset_sort', '', self::SITE_ID);
        $sort = $raw ? unserialize($raw, ['allowed_classes' => false]) : [];
        if (!is_array($sort)) {
            return;
        }

        foreach (['show', 'hide'] as $key) {
            if (isset($sort[$key]) && is_array($sort[$key])) {
                $sort[$key] = array_values(array_filter(
                    $sort[$key],
                    static fn($id): bool => $id !== self::ITEM_ID
                ));
            }
        }

        \COption::SetOptionString('intranet', 'left_menu_custom_preset_sort', serialize($sort), false, self::SITE_ID);
    }
}
