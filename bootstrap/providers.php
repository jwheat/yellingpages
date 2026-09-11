<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ScrubServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ScrubServiceProvider::class,
];
