<?php

namespace Combindma\YotpoApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Combindma\YotpoApi\YotpoApi
 */
class YotpoApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'yotpoapi';
    }
}
