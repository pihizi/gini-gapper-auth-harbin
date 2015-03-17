<?php

namespace Gini\Module;

class GapperAuthHarbin
{
    public static function setup()
    {
    }

    public static function diagnose()
    {
        $home = \Gini\Config::get('app.home');
        if (!$home) {
            return ['请配置app.home'];
        }
        $secret = \Gini\Config::get('app.harbin_secret');
        if (!$secret) {
            return ['需要提供app.harbin_secret'];
        }
    }
}
