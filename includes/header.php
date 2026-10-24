<?php
/**
 * NEXO WMS - Header Comum
 */
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'NEXO WMS - Sistema de Armazém');
}
$user = currentUser();
$toasts = getToasts();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars(PAGE_TITLE) ?> | NEXO WMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nexo: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            brand: '#0ea5e9',
                            dark: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex flex-col antialiased selection:bg-cyan-500 selection:text-white">

<!-- Container de Notificações Toast Flutuantes -->
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none px-4 sm:px-0">
    <?php foreach ($toasts as $toast): ?>
        <?php
            $bgColor = 'bg-slate-800 border-slate-700 text-slate-200';
            $iconColor = 'text-cyan-400';
            $icon = 'fa-info-circle';
            
            if ($toast['type'] === 'success') {
                $bgColor = 'bg-emerald-950/90 border-emerald-500/50 text-emerald-100';
                $iconColor = 'text-emerald-400';
                $icon = 'fa-circle-check';
            } elseif ($toast['type'] === 'error') {
                $bgColor = 'bg-rose-950/90 border-rose-500/50 text-rose-100';
                $iconColor = 'text-rose-400';
                $icon = 'fa-triangle-exclamation';
            } elseif ($toast['type'] === 'warning') {
                $bgColor = 'bg-amber-950/90 border-amber-500/50 text-amber-100';
                $iconColor = 'text-amber-400';
                $icon = 'fa-triangle-exclamation';
            }
        ?>
        <div class="toast-item pointer-events-auto flex items-start gap-3 p-4 rounded-xl border shadow-2xl backdrop-blur-md transition-all duration-300 transform translate-y-0 opacity-100 <?= $bgColor ?>" role="alert">
            <div class="flex-shrink-0 mt-0.5">
                <i class="fa-solid <?= $icon ?> text-xl <?= $iconColor ?>"></i>
            </div>
            <div class="flex-1 text-sm leading-relaxed">
                <?php if (!empty($toast['title'])): ?>
                    <p class="font-bold text-sm mb-0.5"><?= htmlspecialchars($toast['title']) ?></p>
                <?php endif; ?>
                <p class="font-medium"><?= htmlspecialchars($toast['message']) ?></p>
            </div>
            <button type="button" onclick="dismissToast(this.parentElement)" class="flex-shrink-0 text-slate-400 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>

<div class="flex-1 flex flex-col md:flex-row min-h-0">
