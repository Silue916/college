<?php

namespace Epaphrodites\controllers\switchers;

use Epaphrodites\services\AuthGuard;

class ControllersSwitchers extends MergeControllers
{

    /**
     * @param mixed $controller
     * @param mixed $provider
     * @param null|bool $switch
     * @return bool
     */
    public static function GetController(
        $controller, 
        $provider, 
        bool $switch = false
    ):bool
    {
        if ($controller !== ($provider[0] ?? null)) {
            return false;
        }

        if ($switch === false) {
            return true;
        }

        AuthGuard::requireAuth();
        return true;
    }
}
