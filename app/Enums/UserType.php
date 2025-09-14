<?php

namespace App\Enums;

enum UserType: int
{
    case Owner = 10;
    case Senior = 11;
    case Agent = 20;
    case SubAgent = 30;
    case Player = 40;
    case SystemWallet = 50;

    public static function usernameLength(UserType $type): int
    {
        return match ($type) {
            self::Owner => 1,
            self::Senior=>2,
            self::Agent => 3,
            self::SubAgent => 4,
            self::Player => 5,
            self::SystemWallet => 6,
        };
    }

    public static function childUserType(UserType $type): UserType
    {
        return match ($type) {
            self::Owner =>self::Senior,
            self::Senior=>self::Agent,
            self::Agent => self::SubAgent,
            self::SubAgent => self::Player,
            self::Player, self::SystemWallet => self::Player,
        };
    }

    public static function canHaveChild(UserType $parent, UserType $child): bool
    {
        return match ($parent) {
            self::Owner => $child === self::Agent,
            self::Agent => $child === self::SubAgent || $child === self::Player,
            self::SubAgent => $child === self::Player,
            self::Player, self::SystemWallet => false,
        };
    }
}
