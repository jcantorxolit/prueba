<?php

namespace AdeN\Api\Classes\Override;

use Illuminate\Pagination\Paginator;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    //@Override
    public function query()
    {
        return new LaravelQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
