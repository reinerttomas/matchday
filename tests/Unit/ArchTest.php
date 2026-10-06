<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();

arch('strict types')
    ->expect('App')
    ->toUseStrictTypes()
    ->toUseStrictEquality();

arch('avoid open for extension')
    ->expect('App')
    ->classes()
    ->toBeFinal();

arch('ensure no extends')
    ->expect('App')
    ->classes()
    ->not->toBeAbstract();

/**
 * Framework base classes and queue traits rely on mutable state, so only our own classes are readonly.
 */
arch('avoid mutation')
    ->expect('App')
    ->classes()
    ->toBeReadonly()
    ->ignoring([
        'App\Console\Commands',
        'App\Http\Middleware',
        'App\Http\Requests',
        'App\Jobs',
        'App\Mail',
        'App\Models',
        'App\Providers',
    ]);

arch('avoid inheritance')
    ->expect('App')
    ->classes()
    ->toExtendNothing()
    ->ignoring([
        'App\Console\Commands',
        'App\Http\Middleware',
        'App\Http\Requests',
        'App\Mail',
        'App\Models',
        'App\Providers',
    ]);

arch('annotations')
    ->expect('App')
    ->toHavePropertiesDocumented()
    ->toHaveMethodsDocumented();

arch('controllers validate through form requests')
    ->expect('App\Http\Controllers')
    ->not->toUse(Validator::class);

arch('validation concerns are shared only by actions and form requests')
    ->expect('App\Concerns')
    ->toOnlyBeUsedIn(['App\Actions', 'App\Http\Requests']);
