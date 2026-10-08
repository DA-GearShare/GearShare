<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserRolesDto
{
    public function __construct(
        #[Assert\Type('boolean')]
        public bool $is_admin,

        #[Assert\Type('boolean')]
        public bool $is_teacher,
    )
    {
    }
}

