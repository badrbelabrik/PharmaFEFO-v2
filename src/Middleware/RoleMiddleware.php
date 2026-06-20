<?php

namespace PharmaFEFOV2\Middleware;

class RoleMiddleware
{
    public static function hasRole(string $requiredRole): bool
    {
        if (!AuthMiddleware::isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;

        if (!$userRole) {
            return false;
        }

        return $userRole === $requiredRole;
    }

    public static function hasAnyRole(array $requiredRoles): bool
    {
        if (!AuthMiddleware::isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;

        if (!$userRole) {
            return false;
        }

        return in_array($userRole, $requiredRoles);
    }

    public static function requireRole(string $requiredRole): void
    {
        if (!self::hasRole($requiredRole)) {
            http_response_code(403);
            require_once __DIR__ . '/../../templates/errors/403.php';
            exit();
        }
    }

    public static function requireAnyRole(array $requiredRoles): void
    {
        if (!self::hasAnyRole($requiredRoles)) {
            http_response_code(403);
            require_once __DIR__ . '/../../templates/errors/403.php';
            exit();
        }
    }

    public static function getCurrentRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::getCurrentRole() === 'admin';
    }

    public static function isPharmacist(): bool
    {
        return self::getCurrentRole() === 'pharmacist';
    }

    public static function isPreparer(): bool
    {
        return self::getCurrentRole() === 'preparer';
    }

    public static function isAuthenticated(): bool
    {
        return AuthMiddleware::isAuthenticated();
    }
}