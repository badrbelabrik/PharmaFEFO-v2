<?php
$userRole = $_SESSION['user_role'] ?? 'preparer';
$currentUser = $_SESSION['user_firstname'] . ' ' . ($_SESSION['user_lastname'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'PharmaFEFO' ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="h-full font-sans antialiased text-slate-900">
<div class="flex min-h-screen">
    <aside class="w-64 bg-slate-900 text-white flex flex-col h-screen sticky top-0 p-4 shrink-0">
        <!-- Logo -->
        <div class="flex items-center space-x-2 px-2 py-3 mb-6 border-b border-slate-800">
            <span class="text-emerald-500 text-2xl">⚕️</span>
            <span class="text-lg font-bold">PharmaFEFO</span>
        </div>

        <!-- Navigation -->
        <nav class="space-y-1 flex-1">
            <a href="index.php?route=dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg <?= ($currentPage ?? '') === 'dashboard' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?> transition-colors">
                <span>📊</span> <span>Dashboard</span>
            </a>

            <a href="index.php?route=stock-receive" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg <?= ($currentPage ?? '') === 'receive' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?> transition-colors">
                <span>📥</span> <span>Stock Ingestion</span>
            </a>

            <a href="index.php?route=stock-dispatch" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg <?= ($currentPage ?? '') === 'dispatch' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?> transition-colors">
                <span>📤</span> <span>Dispense Medicine</span>
            </a>

            <!-- Only for Pharmacist and Admin -->
            <?php if (in_array($userRole, ['pharmacist', 'admin'])): ?>
                <a href="index.php?route=stock-alerts" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg <?= ($currentPage ?? '') === 'alerts' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?> transition-colors">
                    <span>⚠️</span> <span>Expiry Alerts</span>
                </a>
                <a href="index.php?route=reports" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg <?= ($currentPage ?? '') === 'reports' ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?> transition-colors">
                    <span>📉</span> <span>Reports</span>
                </a>
            <?php endif; ?>

            <!-- Only for Admin -->
            <?php if ($userRole === 'admin'): ?>
                <div class="pt-4 mt-4 border-t border-slate-800">
                    <p class="px-3 py-2 text-xs text-slate-500 uppercase">Admin</p>
                    <a href="index.php?route=admin-users" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-800 transition-colors">
                        <span>👥</span> <span>Manage Users</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="border-t border-slate-800 pt-4 px-2 space-y-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center text-sm font-bold text-white uppercase shrink-0">
                    <?= htmlspecialchars(substr($currentUser ?? 'U', 0, 2)) ?>
                </div>
                <div class="truncate">
                    <p class="text-sm font-semibold truncate max-w-[150px]"><?= htmlspecialchars($currentUser ?? 'User') ?></p>
                    <p class="text-xs text-slate-400 capitalize"><?= htmlspecialchars($userRole ?? 'Preparator') ?></p>
                </div>
            </div>

            <a href="index.php?route=logout"
               class="flex items-center justify-center w-full px-3 py-2 text-sm font-medium text-slate-400 hover:text-red-400 bg-slate-800/50 hover:bg-slate-800 rounded-lg transition-colors duration-150 gap-2">
                <span>🚪</span> Logout
            </a>
        </div>
    </aside>