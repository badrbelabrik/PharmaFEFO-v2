<?php



namespace PharmaFEFOV2\Middleware;

class RoleMiddleware
{
    private static array $roleHierarchy = [
        'preparer' => 1,
        'pharmacist' => 2,
        'admin' => 3
    ];

    public static function hasRole($requiredRoles): bool
    {
        if (!AuthMiddleware::isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;

        if (!$userRole) {
            return false;
        }

        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }

        return in_array($userRole, $requiredRoles);
    }

    public static function requireRole($requiredRoles): void
    {
        if (!self::hasRole($requiredRoles)) {
            header('Location: index.php?route=403.php');
            exit();
        }
    }

    public static function hasRoleHierarchy(string $requiredRole): bool
    {
        if (!AuthMiddleware::isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? null;

        if (!$userRole || !isset(self::$roleHierarchy[$userRole])) {
            return false;
        }

        $requiredLevel = self::$roleHierarchy[$requiredRole] ?? 0;
        $userLevel = self::$roleHierarchy[$userRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }


    public static function requireRoleHierarchy(string $requiredRole): void
    {
        if (!self::hasRoleHierarchy($requiredRole)) {
            header('Location: index.php?route=dashboard');
            exit();
        }
    }

    public static function getCurrentRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }
}