<?php

namespace Core\Front;

use Illuminate\Support\ServiceProvider;

class Boot extends ServiceProvider
{
    public static function middleware($middleware): void {}
}
