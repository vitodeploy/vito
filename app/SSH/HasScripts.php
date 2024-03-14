<?php

namespace App\SSH;

use ReflectionClass;

trait HasScripts
{
    private function getScript(string $name, array $vars = []): string
    {
        $reflector = new ReflectionClass($this);
        $scriptsDir = dirname($reflector->getFileName()).'/scripts';
        $script = file_get_contents($scriptsDir.'/'.$name);
        foreach ($vars as $key => $value) {
            $script = str_replace('__'.$key.'__', $value, $script);
        }

        return $script;
    }
}
