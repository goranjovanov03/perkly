<?php

namespace App\Enums;

enum TargetType: string
{
    case Assignment = 'assignment';
    case Organization = 'organization';
    case OrganizationUnit = 'organization_unit';
    case Person = 'person';
    case File = 'file';
    case Test = 'test';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}