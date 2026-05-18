# KRIK Staff Shift & KPI System

> Hệ thống quản lý chấm công, KPI và bảng lương nhân viên cửa hàng bán lẻ thời trang KRIK.

---

## 1. Tech Stack & Lý do chọn

| Layer | Công nghệ | Lý do |
|---|---|---|
| **Backend** | Laravel 8 (PHP 8.x) | Framework quen thuộc, ORM Eloquent mạnh, middleware/policy sẵn có |
| **Database** | MySQL | Quan hệ phức tạp (users ↔ shifts ↔ kpi_configs), cần transaction & foreign key |
| **Frontend** | Blade + TailwindCSS CDN | Server-side render, không cần build step, deploy nhanh |
| **Auth** | Laravel Session Auth | Đủ dùng cho internal tool, không cần JWT |
| **Charts/UI** | Select2, Vanilla JS | Nhẹ, không phụ thuộc SPA framework |
| **Testing** | PHPUnit | Tích hợp sẵn Laravel, CI-friendly |

---

## 2. Setup & Run Local

### Yêu cầu
- PHP >= 8.0
- MySQL >= 5.7
- Composer

### Cài đặt một lệnh
```bash
git clone <repo-url> && cd quanlycong
composer install
cp .env.example .env
# Cập nhật DB_DATABASE, DB_USERNAME, DB_PASSWORD trong .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Truy cập: **http://localhost:8000/staff-shift-kpi/login**

---

## 3. DB Migration & Seed

```bash
# Reset và seed toàn bộ (recommended cho reviewer)
php artisan migrate:fresh --seed

# Seed riêng dữ liệu tháng 5/2026 cho K01 (nếu cần thêm ca làm)
php artisan db:seed --class=May2026K01Seeder
```

### Tài khoản mẫu sau khi seed

| Username | Password | Vai trò | Ghi chú |
|---|---|---|---|
| `admin` | `password` | Admin | Toàn quyền |
| `k01_qlch` | `password` | Quản lý CH | Store K01 |
| `k01_chp` | `password` | Phó quản lý | Store K01 |
| `k01_nvbh_ft1` | `password` | Nhân viên bán hàng FT | Store K01 |
| `k01_nvbv` | `password` | Bảo vệ | Store K01 |
| `k01_nvk` | `password` | Nhân viên kho | Store K01 |
| `k01_nvtn` | `password` | Thu ngân | Store K01 |

---

## 4. Cách chạy Test

```bash
vendor/bin/phpunit
# Hoặc
php artisan test
```

**Kết quả mong đợi:** `OK (7 tests, 21 assertions)`

### Test coverage
- `AuthTest` — Login/logout, sai mật khẩu, blocked user
- `PermissionTest` — RBAC: admin vs staff vs store_manager access
- `PayrollCalculationTest` — Tính lương cứng, hoa hồng, thưởng team theo KPI bracket

---

## 5. Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────────┐
│                        Browser (Blade)                       │
│  /daily  │  /monthly  │  /payrolls  │  /staff  │  /settings │
└────────────────────────────┬────────────────────────────────┘
                             │ HTTP (Session Auth)
┌────────────────────────────▼────────────────────────────────┐
│                    Laravel 8 Application                     │
│                                                              │
│  Controllers:                                                │
│  DailyWorkController  ← save-on-blur via fetch() + debounce │
│  MonthlyController    ← aggregate ShiftRecord by date/user   │
│  PayrollController    ← on-the-fly salary calculation        │
│  KpiController        ← KpiConfig + DailyTarget management  │
│  UserController       ← CRUD nhân sự (role-scoped)          │
│                                                              │
│  Middleware:  auth  │  RoleCheck (via Permission model)      │
└────────────────────────────┬────────────────────────────────┘
                             │ Eloquent ORM
┌────────────────────────────▼────────────────────────────────┐
│                        MySQL Database                        │
│                                                              │
│  stores ─── users ─── shift_records                         │
│    │           │                                             │
│    └── kpi_configs ── daily_targets                         │
│              │                                               │
│              └── employee_daily_kpi                         │
│                                                              │
│  positions ── commission_brackets                            │
│  roles ── permissions ── role_permissions ── user_roles     │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Trade-offs & Những gì bỏ qua

### Bỏ qua (do giới hạn thời gian)
| Item | Lý do bỏ qua |
|---|---|
| **Lịch sử lương** (`user_salary_histories`) | Cần thêm 1 table + trigger; thay bằng sync `hourly_rate` từ Position khi cập nhật chức danh |
| **API JSON** | Internal tool, Blade đủ dùng; API có thể thêm sau nếu cần mobile |
| **Real-time updates** (WebSocket) | Dùng debounce + save-on-blur thay vì WebSocket — đủ cho use case |
| **Export PDF/Excel** | Reviewer có thể xem trực tiếp trên web |

### Trade-offs đã chọn
- **Tính lương on-the-fly** thay vì lưu cache: Đảm bảo số luôn mới nhất, chấp nhận query nặng hơn một chút khi tháng có nhiều dữ liệu.
- **Permission DB-driven** thay vì hardcode: Linh hoạt cho admin điều chỉnh, nhưng cần seeder cẩn thận để tránh inconsistency.

---

## 7. Demo

- **Demo URL**: http://dev.quanlycong.jp/staff-shift-kpi/login
- **Login nhanh**: `admin` / `password`

> Dữ liệu mẫu: Store K01 — tháng 5/2026 — đầy đủ KPI config, ca làm và bảng lương.
