<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - KRIK System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-compact th, .table-compact td { padding: 0.4rem 0.75rem !important; }
    </style>
</head>
<body class="bg-slate-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-900 text-white flex flex-col">
            <div class="p-6 text-2xl font-bold text-rose-500 border-b border-slate-800">
                KRIK SYSTEM
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="{{ route('fe.daily.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/daily*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    📋 Bảng công ngày
                </a>
                <a href="{{ route('fe.monthly.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/monthly*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    📊 Tổng quan tháng
                </a>
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Danh mục</div>
                <a href="{{ route('fe.stores.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/stores*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🏪 Cửa hàng
                </a>
                <a href="{{ route('fe.users.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/staff*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    👥 Nhân sự
                </a>
                <a href="{{ route('fe.settings.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/settings*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🛠️ Cài đặt catalog
                </a>

                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nghiệp vụ</div>
                <a href="{{ route('fe.kpi-config.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/kpi-config*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    ⚙️ Cấu hình KPI
                </a>
                <a href="{{ route('fe.payrolls.index') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/payrolls*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    💰 Bảng lương
                </a>
                <a href="{{ route('fe.profile') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/my-profile*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    👤 Hồ sơ của tôi
                </a>

                @if(auth()->user()->role === 'admin')
                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Quản trị</div>
                <a href="{{ route('fe.admin.permissions') }}" class="block px-4 py-2.5 rounded-lg hover:bg-slate-800 transition-colors {{ request()->is('*/admin/permissions*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🔐 Phân quyền
                </a>
                @endif
            </nav>
            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-3 px-4 py-2">
                    <a href="{{ route('fe.profile') }}" class="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center font-bold text-xs hover:ring-2 hover:ring-rose-300 transition-all" title="Hồ sơ của tôi">
                        {{ substr(Auth::user()->full_name, 0, 1) }}
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('fe.profile') }}" class="text-sm font-medium truncate block hover:text-rose-400 transition-colors">{{ Auth::user()->full_name }}</a>
                        <a href="{{ url('/staff-shift-kpi/logout') }}" class="text-xs text-slate-500 hover:text-rose-400">Đăng xuất</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white border-b flex items-center justify-between px-6">
                <h2 class="text-lg font-semibold text-slate-800">@yield('title')</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-500">{{ now()->format('d/m/Y') }}</span>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-5">
                @if(session('success') && !View::hasSection('has_local_alert'))
                    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
