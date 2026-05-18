<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống KRIK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="glass-effect w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-slate-800 mb-2">KRIK</h1>
            <p class="text-slate-500">Hệ thống quản lý công & KPI</p>
        </div>

        <form id="login-form" action="{{ url('/staff-shift-kpi/login') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Validation Errors (Top of form) -->
            @if($errors->any())
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-lg mb-6">
                    <p class="text-sm text-rose-700 font-medium">{{ $errors->first() }}</p>
                </div>
            @endif
            
            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tên đăng nhập</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all"
                    placeholder="admin hoặc manager" required autofocus oninput="checkInputs()">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Mật khẩu</label>
                <div class="relative">
                    <input type="password" id="password" name="password" 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all"
                        placeholder="••••••••" required oninput="checkInputs()">
                    
                    <!-- Toggle Password Button -->
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" id="submit-btn" disabled
                class="w-full bg-gradient-to-r from-rose-500 to-rose-600 text-white font-semibold py-3 rounded-xl shadow-lg shadow-rose-200 transition-all transform
                       disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:scale-100
                       hover:enabled:from-rose-600 hover:enabled:to-rose-700 hover:enabled:scale-[1.02] active:enabled:scale-[0.98]">
                <span id="btn-text">Đăng nhập hệ thống</span>
                <span id="btn-loading" class="hidden items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Đang đăng nhập...
                </span>
            </button>
        </form>

        <p class="text-center mt-8 text-sm text-slate-400">
            © 2026 KRIK Fashion Group. All rights reserved.
        </p>
    </div>

    <script>
        // Flag: đang submit thì khóa cứng, không cho bất kỳ thứ gì enable lại
        let isSubmitting = false;

        function checkInputs() {
            if (isSubmitting) return; // Khóa cứng khi đang submit
            const u = document.getElementById('username').value.trim();
            const p = document.getElementById('password').value;
            document.getElementById('submit-btn').disabled = !(u.length > 0 && p.length > 0);
        }

        // Lắng nghe form submit (không dùng onclick — tránh browser block submission)
        document.getElementById('login-form').addEventListener('submit', function () {
            isSubmitting = true;
            document.getElementById('btn-text').classList.add('hidden');
            const loading = document.getElementById('btn-loading');
            loading.classList.remove('hidden');
            loading.classList.add('inline-flex');
            document.getElementById('submit-btn').disabled = true;
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.888 9.888L3 3m18 18l-6.888-6.888" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
            // checkInputs() vẫn gọi, nhưng nếu isSubmitting=true thì sẽ không thực hiện gì cả
            checkInputs();
        }

        // Khởi tạo state khi trang load (xử lý browser autofill)
        document.addEventListener('DOMContentLoaded', checkInputs);
    </script>
</body>
</html>
