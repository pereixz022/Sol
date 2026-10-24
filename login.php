<?php
/**
 * NEXO WMS - Tela de Login Responsiva
 */
define('PAGE_TITLE', 'Acesso ao Sistema');
require_once __DIR__ . '/includes/auth.php';

// Se já estiver autenticado, redireciona para o dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$toasts = getToasts();
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | NEXO WMS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nexo: {
                            brand: '#0ea5e9',
                            dark: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex flex-col justify-center py-8 px-4 sm:px-6 lg:px-8 selection:bg-cyan-500 selection:text-white">

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
            <button type="button" onclick="this.parentElement.remove()" class="flex-shrink-0 text-slate-400 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>

<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <!-- Logotipo Principal -->
    <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-500 via-blue-600 to-indigo-600 flex items-center justify-center font-black text-white text-3xl shadow-xl shadow-cyan-500/20 ring-4 ring-cyan-500/10">
            <i class="fa-solid fa-warehouse"></i>
        </div>
    </div>
    <h2 class="text-center text-3xl font-extrabold tracking-tight text-white">
        NEXO<span class="text-cyan-400">WMS</span>
    </h2>
    <p class="mt-2 text-center text-sm text-slate-400">
        Sistema de Gerenciamento e Controle de Armazém
    </p>
</div>

<div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-slate-900/90 border border-slate-800/80 py-8 px-6 shadow-2xl rounded-2xl sm:px-10 backdrop-blur-sm">
        
        <form class="space-y-5" action="actions/auth_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <!-- Campo E-mail -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    E-mail de Acesso
                </label>
                <div class="mt-1 relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           placeholder="seu.email@nexo.com"
                           class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl bg-slate-950/60 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm transition-colors">
                </div>
            </div>

            <!-- Campo Senha -->
            <div>
                <div class="flex items-center justify-between">
                    <label for="senha" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Senha
                    </label>
                </div>
                <div class="mt-1 relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input id="senha" name="senha" type="password" autocomplete="current-password" required
                           placeholder="••••••••"
                           class="block w-full pl-10 pr-10 py-3 border border-slate-700 rounded-xl bg-slate-950/60 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm transition-colors">
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200">
                        <i id="toggle-pwd-icon" class="fa-solid fa-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Botão de Login -->
            <div>
                <button type="submit"
                        class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-lg shadow-cyan-600/30 text-sm font-bold text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all transform active:scale-95">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Entrar no Sistema</span>
                </button>
            </div>
        </form>

        <!-- Divisor com Acesso Rápido para Testes -->
        <div class="mt-8 pt-6 border-t border-slate-800">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Acesso Rápido (Demonstração)</span>
                <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full font-mono">1-Clique</span>
            </div>
            
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <!-- Botão Demo: Gestor -->
                <button type="button" onclick="fillCredentials('admin@nexo.com', 'admin123')"
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border border-purple-800/60 bg-purple-950/40 hover:bg-purple-900/40 text-left transition-all">
                    <div class="w-7 h-7 rounded-lg bg-purple-600/30 text-purple-300 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-purple-200 truncate">Gestor Geral</p>
                        <p class="text-[10px] text-purple-400 truncate">Acesso Total</p>
                    </div>
                </button>

                <!-- Botão Demo: João Silva (Funcionário Geral) -->
                <button type="button" onclick="fillCredentials('joao@nexo.com', 'admin123')"
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border border-cyan-800/60 bg-cyan-950/40 hover:bg-cyan-900/40 text-left transition-all">
                    <div class="w-7 h-7 rounded-lg bg-cyan-600/30 text-cyan-300 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-cyan-200 truncate">João Silva</p>
                        <p class="text-[10px] text-cyan-400 truncate">Entrada & Saída</p>
                    </div>
                </button>

                <!-- Botão Demo: Mariana (Apenas Saída) -->
                <button type="button" onclick="fillCredentials('mariana@nexo.com', 'admin123')"
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border border-blue-800/60 bg-blue-950/40 hover:bg-blue-900/40 text-left transition-all">
                    <div class="w-7 h-7 rounded-lg bg-blue-600/30 text-blue-300 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fa-solid fa-arrow-up"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-blue-200 truncate">Mariana Costa</p>
                        <p class="text-[10px] text-blue-400 truncate">Apenas Saída</p>
                    </div>
                </button>

                <!-- Botão Demo: Lucas (Apenas Entrada) -->
                <button type="button" onclick="fillCredentials('lucas@nexo.com', 'admin123')"
                        class="flex items-center gap-2.5 p-2.5 rounded-xl border border-emerald-800/60 bg-emerald-950/40 hover:bg-emerald-900/40 text-left transition-all">
                    <div class="w-7 h-7 rounded-lg bg-emerald-600/30 text-emerald-300 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fa-solid fa-arrow-down"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-emerald-200 truncate">Lucas Pereira</p>
                        <p class="text-[10px] text-emerald-400 truncate">Apenas Entrada</p>
                    </div>
                </button>
            </div>
            
            <p class="mt-3 text-[11px] text-center text-slate-400">
                Senha padrão para todos os usuários: <span class="text-slate-300 font-mono font-bold">admin123</span>
            </p>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, senha) {
    document.getElementById('email').value = email;
    document.getElementById('senha').value = senha;
}

function togglePasswordVisibility() {
    const input = document.getElementById('senha');
    const icon = document.getElementById('toggle-pwd-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash text-sm';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye text-sm';
    }
}

// Auto dismiss toasts
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toast-item').forEach(el => {
        setTimeout(() => el.remove(), 4500);
    });
});
</script>
</body>
</html>
