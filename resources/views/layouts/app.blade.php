<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - KRIK System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-compact th, .table-compact td { padding: 0.4rem 0.75rem !important; }
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
        /* Select2 overrides */
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            display: flex; align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            font-size: 0.875rem;
            color: #334155;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .select2-dropdown { border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; font-size: 0.8rem;
        }
        .select2-results__option { padding: 8px 12px; font-size: 0.82rem; }
        .select2-container--default .select2-results__option--highlighted { background-color: #1e293b; }
    </style>
    @stack('head_scripts')
</head>
<body class="bg-slate-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-900 text-white flex flex-col h-full shrink-0">
            <div class="px-5 py-4.5 text-xl font-black text-rose-500 border-b border-slate-800 shrink-0 tracking-wider">
                KRIK SYSTEM
            </div>
            <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto sidebar-scroll">
                <a href="{{ route('fe.daily.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/daily*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    📋 Bảng công ngày
                </a>
                <a href="{{ route('fe.monthly.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/monthly*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    📊 Tổng quan tháng
                </a>
                
                @if(auth()->user()->can(['manage_all_stores', 'manage_own_store']) || auth()->user()->can('manage_staff') || auth()->user()->role === 'admin')
                <div class="pt-2.5 pb-1 px-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Danh mục</div>
                @if(auth()->user()->can(['manage_all_stores', 'manage_own_store']))
                <a href="{{ route('fe.stores.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/stores*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🏪 Cửa hàng
                </a>
                @endif
                @if(auth()->user()->can('manage_staff'))
                <a href="{{ route('fe.users.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/staff*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    👥 Nhân sự
                </a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('fe.settings.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/settings*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🛠️ Cài đặt catalog
                </a>
                @endif
                @endif

                <div class="pt-2.5 pb-1 px-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Nghiệp vụ</div>
                @if(auth()->user()->can('config_kpi'))
                <a href="{{ route('fe.kpi-config.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/kpi-config*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    ⚙️ Cấu hình KPI
                </a>
                @endif
                @if(auth()->user()->can(['view_payroll_all', 'view_payroll_store']))
                <a href="{{ route('fe.payrolls.index') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/payrolls*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    💰 Bảng lương
                </a>
                @endif
                <a href="{{ route('fe.profile') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/my-profile*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    👤 Hồ sơ của tôi
                </a>

                @if(auth()->user()->role === 'admin')
                <div class="pt-2.5 pb-1 px-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Quản trị</div>
                <a href="{{ route('fe.admin.permissions') }}" class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 transition-colors text-sm {{ request()->is('*/admin/permissions*') ? 'bg-slate-800 text-rose-500' : '' }}">
                    🔐 Phân quyền
                </a>
                @endif

                <div class="pt-2.5 pb-1 px-3.5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Hệ thống</div>
                <a href="{{ url('/staff-shift-kpi/logout') }}"
                   onclick="return confirm('Bạn có chắc chắn muốn đăng xuất khỏi hệ thống KRIK?')"
                   class="block px-3.5 py-2 rounded-lg hover:bg-slate-800 hover:text-rose-400 transition-colors text-sm text-slate-400">
                    🚪 Đăng xuất
                </a>
            </nav>
            <div class="px-4 py-3 border-t border-slate-800 shrink-0">
                <div class="flex items-center gap-3 px-2 py-1">
                    <a href="{{ route('fe.profile') }}" class="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center font-bold text-xs hover:ring-2 hover:ring-rose-300 transition-all" title="Hồ sơ của tôi">
                        {{ substr(Auth::user()->full_name, 0, 1) }}
                    </a>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('fe.profile') }}" class="text-sm font-bold truncate block hover:text-rose-400 transition-colors">{{ Auth::user()->full_name }}</a>
                        <div class="mt-1">
                            <span class="px-1.5 py-0.5 bg-slate-800 text-rose-400 border border-slate-700 rounded text-[9px] font-black uppercase tracking-wider inline-block">
                                {{ Auth::user()->getGroupRoleName() }}
                            </span>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>
</html>
