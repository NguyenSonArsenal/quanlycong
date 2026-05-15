<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KRIK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 font-[Inter]">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-rose-600">KRIK</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-slate-600">Chào, <strong>{{ Auth::user()->full_name }}</strong></span>
                    <a href="{{ url('/staff-shift-kpi/logout') }}" class="text-sm font-medium text-rose-600 hover:text-rose-700 bg-rose-50 px-4 py-2 rounded-lg transition-colors">
                        Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-sm p-8 border border-slate-100">
            <h2 class="text-2xl font-semibold text-slate-800 mb-4">Chào mừng đến với Bảng công ngày!</h2>
            <p class="text-slate-500">Đây là trang Dashboard tạm thời. Anh em mình sẽ bắt đầu xây dựng các tính năng quản lý tại đây.</p>
        </div>
    </main>
</body>
</html>
