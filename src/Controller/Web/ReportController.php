<?php

namespace Controller\Web;

class ReportController
{
    /**
     * US 4.2: Show financial reports page (HTML only)
     * Data is loaded via API by JavaScript
     */
    public function financial(): void
    {
        // Check role - Admin only
        if ($_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            require_once __DIR__ . '/../../../templates/errors/403.php';
            exit();
        }

        $currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? '');
        $userRole = $_SESSION['user_role'] ?? 'admin';
        $currentPage = 'reports';

        // ✅ Only serve HTML skeleton - data loaded via JavaScript
        require_once __DIR__ . '/../../../templates/dashboard/financial.php';
    }
}