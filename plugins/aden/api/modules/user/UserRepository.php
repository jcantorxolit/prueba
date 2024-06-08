<?php

namespace AdeN\Api\Modules\User;

use AdeN\Api\Classes\BaseRepository;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new UserModel());
    }

}
