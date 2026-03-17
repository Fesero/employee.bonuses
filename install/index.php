<?php

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use EmployeeBonuses\BonusTable;
use EmployeeBonuses\MenuHelper;

class employee_bonuses extends CModule
{
    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $this->MODULE_ID          = 'employee.bonuses';
        $this->MODULE_NAME        = 'Модуль начисления бонусов сотрудникам';
        $this->MODULE_DESCRIPTION = 'Тестовый модуль для начисления бонусов сотрудникам';

        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION      = $arModuleVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '2026-03-16 00:00:00';
    }

    public function DoInstall(): void
    {
        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        
    ModuleManager::registerModule($this->MODULE_ID);
    
    if (Loader::includeModule($this->MODULE_ID)) {
        MenuHelper::install();
    }
}

    public function DoUninstall(): void
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            MenuHelper::uninstall();
        }

        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    private function getConnection(): Connection
    {
        require_once dirname(__DIR__) . '/lib/BonusTable.php';
        return BonusTable::getEntity()->getConnection();
    }

    public function InstallDB(): void
    {
        $connection = $this->getConnection();
        $tableName  = BonusTable::getTableName();

        if (!$connection->isTableExists($tableName)) {
            BonusTable::getEntity()->createDbTable();
        }
    }

    public function UnInstallDB(): void
    {
        $connection = $this->getConnection();
        $tableName  = BonusTable::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }
    }

    public function InstallEvents(): void
    {
        $em = EventManager::getInstance();
        $em->registerEventHandler('crm',  'OnAfterCrmDealUpdate',          $this->MODULE_ID, '\\EmployeeBonuses\\CrmDealHandler', 'onAfterDealUpdate');
        $em->registerEventHandler('rest', 'OnRestServiceBuildDescription', $this->MODULE_ID, '\\EmployeeBonuses\\RestHandler',    'onBuildDescription');
    }

    public function UnInstallEvents(): void
    {
        $em = EventManager::getInstance();
        $em->unRegisterEventHandler('crm',  'OnAfterCrmDealUpdate',          $this->MODULE_ID, '\\EmployeeBonuses\\CrmDealHandler', 'onAfterDealUpdate');
        $em->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', $this->MODULE_ID, '\\EmployeeBonuses\\RestHandler',    'onBuildDescription');
    }

    public function InstallFiles(): void
    {
        CopyDirFiles(__DIR__ . '/public/bonuses', Application::getDocumentRoot() . '/bonuses', true, true);
    }

    public function UnInstallFiles(): void
    {
        $path = Application::getDocumentRoot() . '/bonuses';
        if (is_dir($path)) {
            $this->removeDirectory($path);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $file;
            is_dir($fullPath) ? $this->removeDirectory($fullPath) : unlink($fullPath);
        }
        rmdir($path);
    }
}
