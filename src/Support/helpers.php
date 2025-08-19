<?php

use Nwidart\Modules\Facades\Module;

if (!function_exists('snakeToCamel')) {
    function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}

if (!function_exists('camelToSnake')) {
    function camelToSnake(string $input): string
    {
        $pattern = '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!';
        preg_match_all($pattern, $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match)
                ? strtolower($match)
                : lcfirst($match);
        }
        return implode('_', $ret);
    }
}

if (!function_exists('isModuleEnabled')) {

    function isModuleEnabled(string $moduleName): bool
    {
        return Module::isEnabled($moduleName);
    }
}

if (!function_exists('iconfig')) {
    function iconfig($configName = null, $byModule = false, $onlyEnableModules = true)
    {
        //Init response
        $response = config();

        if ($configName && strlen($configName)) {
            $modules = app('modules'); //Init modules
            $enabledModules = $onlyEnableModules ? $modules->allEnabled() : $modules->all(); //Get all enable modules


            //Get config by name to each module
            if ($byModule) {
                $response = [];
                foreach (array_keys($enabledModules) as $moduleName) {
                    $response[$moduleName] = config(strtolower($moduleName) . "." . $configName);
                }
            } else {
                $configNameExplode = explode('.', $configName);
                $response = config(strtolower(array_shift($configNameExplode)) . "." . implode('.', $configNameExplode));
                //dd($configNameExplode, $response, strtolower(array_shift($configNameExplode)) . "." . implode('.', $configNameExplode));
            }
        }

        return $response;
    }
}
