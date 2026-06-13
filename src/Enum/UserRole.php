<?php
namespace PharmaFEFOV2\Enum;

enum UserRole: string{
    case ADMIN = 'admin';
    case PHARMACIST = 'pharmacist';
    case PREPARATOR = 'preparator';

    public function getLabel():string{
        return match($this){
            self::ADMIN => 'admin',
            self::PHARMACIST => 'pharmacist',
            self::PREPARATOR => 'preparator',
        };
    }
}