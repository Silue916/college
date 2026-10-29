<?php

namespace Epaphrodites\controllers\controllerMap;

use Epaphrodites\controllers\controllers\api;
use Epaphrodites\controllers\controllers\dashboard;
use Epaphrodites\controllers\controllers\main;
use Epaphrodites\controllers\controllers\users;
use Epaphrodites\controllers\controllers\setting;
use Epaphrodites\controllers\controllers\SiteController;
use Epaphrodites\controllers\controllers\students;

trait controllerMap
{

    /**
     * Returns an instance of the 'main' controller.
     *
     * @return object An instance of the 'main' controller.
     */
    private function mainController():object
    {
        return new main;
    }    

    /**
     * Returns an array mapping controllers to their respective instances and methods.
     *
     * @return array The mapping of controllers with their instances and methods.
     */
    private function controllerMap(): array
    {
        return [
            'dashboard' => [ new dashboard, 'SwitchControllers', true, 'dashboardFolder', _DIR_ADMIN_TEMP_, ['admin'] ],
			'students' => [ new students, 'SwitchControllers', true, 'studentsFolder', _DIR_ADMIN_TEMP_, ['admin', 'scolarite'] ],
            "api" => [ new api, 'SwitchApiControllers', false ],
			"users" => [ new users, 'SwitchControllers', true, 'usersFolder', _DIR_ADMIN_TEMP_, ['admin'] ],
			"setting" => [ new setting, 'SwitchControllers', true, 'settingFolder', _DIR_ADMIN_TEMP_, ['admin'] ],
            "site" => [ new SiteController, 'SwitchControllers', false, 'main', _DIR_MAIN_TEMP_ ],
        ];
    }
}
