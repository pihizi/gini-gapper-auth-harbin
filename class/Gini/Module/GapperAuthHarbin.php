<?php

namespace Gini\Module;

class GapperAuthHarbin
{
    public static function setup()
    {
    }

    public static function diagnose()
    {
        $secret = \Gini\Config::get('app.harbin_secret');
        if (!$secret) {
            return ['需要提供app.harbin_secret'];
        }
    }
}
