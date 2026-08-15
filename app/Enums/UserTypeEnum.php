<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case USER = 'user';
    case SYS_ADMIN = 'sys_admin';

    public function label(): string
    {
        return match ($this) {
            static::USER => 'Usuário',
            static::SYS_ADMIN => 'Administrador do Sistema',
        };
    }
}