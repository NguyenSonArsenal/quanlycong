# Design Document — KRIK Staff Shift & KPI System

---

## 1. ERD — Entity Relationship Diagram

```mermaid
erDiagram
    stores {
        int id PK
        varchar code "K01, K02..."
        varchar name
        int area_id
    }

    positions {
        int id PK
        varchar code "QLCH, CHP, NVBH_FT..."
        varchar name
        boolean is_sales
        decimal default_hourly_rate
        decimal team_bonus_base
    }

    users {
        int id PK
        varchar username UK
        varchar password
        varchar full_name
        enum role "admin|area_manager|store_manager|staff"
        int store_id FK
        int position_id FK
        enum contract_type "CT|TV"
        decimal hourly_rate
        tinyint status "1=active, 0=inactive"
    }

    kpi_configs {
        int id PK
        int store_id FK
        varchar month "YYYY-MM"
        bigint total_target
        json locked_weeks
    }

    daily_targets {
        int id PK
        int kpi_config_id FK
        date date
        decimal target_amount
    }

    shift_records {
        int id PK
        int store_id FK
        int user_id FK
        date date
        enum shift_type "morning|afternoon|evening"
        decimal hours
        decimal personal_revenue
        boolean is_locked
    }

    employee_daily_kpi {
        int id PK
        int store_id FK
        int user_id FK
        date date
        decimal target_amount
    }

    commission_brackets {
        int id PK
        varchar position_code FK
        enum contract_type "CT|TV"
        decimal min_kpi
        decimal max_kpi
        decimal commission_rate
        date effective_from
        date effective_to
    }

    roles {
        int id PK
        varchar name
        varchar display_name
    }

    permissions {
        int id PK
        varchar name
        varchar display_name
        varchar group
    }

    role_permissions {
        int role_id FK
        int permission_id FK
    }

    user_roles {
        int user_id FK
        int role_id FK
    }

    %% Relationships
    stores ||--o{ users : "có"
    stores ||--o{ kpi_configs : "có"
    stores ||--o{ shift_records : "có"
    stores ||--o{ employee_daily_kpi : "có"

    positions ||--o{ users : "thuộc"
    positions ||--o{ commission_brackets : "áp dụng"

    users ||--o{ shift_records : "chấm công"
    users ||--o{ employee_daily_kpi : "nhận KPI"
    users }o--o{ roles : "gán role qua user_roles"

    kpi_configs ||--o{ daily_targets : "phân bổ"
    roles }o--o{ permissions : "gán quyền qua role_permissions"
```

### Ký hiệu kết nối quan hệ
- `||--o{` : Quan hệ 1 - Nhiều (One-to-Many).
- `}o--o{` : Quan hệ Nhiều - Nhiều (Many-to-Many, qua bảng trung gian).
- `PK` / `FK` / `UK` : Khóa chính (Primary Key) / Khóa ngoại (Foreign Key) / Khóa duy nhất (Unique Key).

---


## 2. Class Diagram — Sơ đồ lớp (UML)

Sơ đồ lớp dưới đây thể hiện cấu trúc mã nguồn gồm các thuộc tính (fields), phương thức (methods) của các Model chính và sự tương tác điều hướng từ các Controller nghiệp vụ:

![UML Class Diagram](class_diagram.png)

```mermaid
classDiagram
    class Store {
        +int id
        +string code
        +string name
        +int area_id
        +users() HasMany
        +shiftRecords() HasMany
        +kpiConfigs() HasMany
    }

    class User {
        +int id
        +string username
        +string full_name
        +string role
        +int store_id
        +int position_id
        +string contract_type
        +decimal hourly_rate
        +tinyint status
        +getGroupRoleName() string
        +position() BelongsTo
        +store() BelongsTo
    }

    class Position {
        +int id
        +string code
        +string name
        +boolean is_sales
        +decimal default_hourly_rate
        +decimal team_bonus_base
    }

    class ShiftRecord {
        +int id
        +int store_id
        +int user_id
        +date date
        +string shift_type
        +decimal hours
        +decimal personal_revenue
        +boolean is_locked
        +user() BelongsTo
        +store() BelongsTo
    }

    class KpiConfig {
        +int id
        +int store_id
        +string month
        +bigint total_target
        +array locked_weeks
        +dailyTargets() HasMany
    }

    class DailyTarget {
        +int id
        +int kpi_config_id
        +date date
        +decimal target_amount
    }

    class EmployeeDailyKpi {
        +int id
        +int store_id
        +int user_id
        +date date
        +decimal target_amount
    }

    class CommissionBracket {
        +int id
        +string position_code
        +string contract_type
        +decimal min_kpi
        +decimal max_kpi
        +decimal commission_rate
        +date effective_from
        +date effective_to
    }

    class PayrollController {
        +index(Request request) Response
        -getCommissionRate(User user, float personalKpiPct, string month) float
    }

    class DailyWorkController {
        +index(Request request) Response
        +updateField(Request request) Response
        +equalize(Request request) Response
        +lock(Request request) Response
    }

    class MonthlyController {
        +index(Request request) Response
        +calendar(Request request) Response
        +revenue(Request request) Response
    }

    %% Associations & Dependencies
    Store "1" --> "*" User : contains
    Store "1" --> "*" ShiftRecord : contains
    Store "1" --> "*" KpiConfig : contains
    
    Position "1" --> "*" User : defines_rate
    Position "1" --> "*" CommissionBracket : defines_brackets
    
    User "1" --> "*" ShiftRecord : logs
    User "1" --> "*" EmployeeDailyKpi : tracks
    
    KpiConfig "1" --> "*" DailyTarget : distributes
    
    PayrollController ..> User : calculates
    PayrollController ..> CommissionBracket : queries_rate
    DailyWorkController ..> ShiftRecord : manages
    MonthlyController ..> ShiftRecord : aggregates
```

### Ký hiệu quan hệ trong UML Class Diagram
- `-->` : Quan hệ kết hợp (Association - chỉ hướng tham chiếu giữa các đối tượng).
- `..>` : Quan hệ phụ thuộc (Dependency - Controller gọi hoặc xử lý logic trên Model).
- `+` : Phương thức / Thuộc tính công khai (Public).
- `-` : Phương thức / Thuộc tính nội bộ (Private).

---


## 3. Permission Model

### Thiết kế
- **Role-based**: mỗi `user` có 1 `role` (admin / area_manager / store_manager / staff)
- **Permission-based bổ sung**: mỗi role được gán tập hợp permissions trong bảng `role_permissions`
- **Nguyên tắc ưu tiên**: **Role quyết định scope (bao nhiêu data)**, Permission quyết định **có vào màn hình không**

### Ma trận quyền chính

| Permission | Admin | Area Mgr | QLCH | CHP | NV thường |
|---|:---:|:---:|:---:|:---:|:---:|
| `manage_all_stores` | ✅ | | | | |
| `manage_own_store` | ✅ | ✅ | ✅ | | |
| `view_payroll_all` | ✅ | | | | |
| `view_payroll_store` | ✅ | ✅ | ✅ | ✅* | |
| `manage_staff` | ✅ | ✅ | ✅ | ✅ | |
| `lock_day` | ✅ | ✅ | ✅ | | |
| `configure_kpi` | ✅ | ✅ | | | |
| `admin_permissions` | ✅ | | | | |

> *CHP có `view_payroll_store` nhưng bị lock scope về store của mình theo role.

### Scoping Rules (quan trọng)
```
Bảng công ngày:
  admin/area_manager    → thấy store được chọn
  QLCH (store_manager)  → thấy toàn bộ NV store mình
  CHP/NV thường (staff) → chỉ thấy row của chính mình

Bảng lương / Tổng quan tháng:
  admin                 → dropdown tất cả stores
  area_manager          → stores trong khu vực
  QLCH/CHP/NV           → lock về store của mình (bất kể có permission view_payroll_all)
```

---

## 4. Xử lý Save-on-Blur Race Condition

### Vấn đề
Khi nhân viên nhập số liệu (giờ công, doanh thu) vào nhiều ô rồi chuyển focus nhanh, có thể xảy ra:
- Request 1 (giờ công) và Request 2 (doanh thu) gửi gần như đồng thời
- Response về sai thứ tự → UI hiện giá trị sai

### Giải pháp đã áp dụng

**1. Debounce 400ms tại frontend:**
```javascript
// Mỗi input chỉ trigger save sau 400ms không có keystroke mới
let saveTimer;
input.addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => saveField(input), 400);
});
```

**2. Per-field request (không batch):**
- Mỗi field (hours, personal_revenue, customers...) gửi request độc lập
- Server UPDATE chỉ đúng 1 column → không bao giờ overwrite field khác

**3. Optimistic UI + rollback:**
```javascript
const oldVal = input.value;
fetch('/daily/update', { method: 'POST', body: ... })
  .then(r => r.ok ? showSaved(input) : rollback(input, oldVal))
  .catch(() => rollback(input, oldVal));
```

**4. Lock check tại server:**
```php
// Nếu ngày đã khoá → reject 403, không update DB
if ($record->is_locked) abort(403, 'Ngày đã khoá');
```

---

## 5. Trade-offs lớn nhất

### Trade-off 1: On-the-fly Payroll vs Cached Payroll

**Vấn đề**: Tính lương từ raw `shift_records` mỗi khi load `/payrolls` — nếu tháng có 30 ngày × 15 NV × 3 ca = 1.350 rows cần aggregate.

**Lựa chọn**: Tính on-the-fly (không cache)

**Lý do**:
- Data thay đổi thường xuyên trong tháng (nhập công hàng ngày)
- Cache sẽ stale ngay sau lần nhập tiếp theo
- 1.350 rows với MySQL index đủ nhanh (<100ms)
- Đơn giản hóa codebase — không cần invalidation logic

**Hạn chế**: Nếu chuỗi có 50+ store × 30+ NV, cần thêm cache layer (Redis) hoặc materialized view.

---

### Trade-off 2: Salary History vs Sync from Position

**Vấn đề**: Khi lương theo giờ thay đổi (ví dụ từ 25k → 27k), bảng lương tháng trước có bị ảnh hưởng không?

**Lựa chọn**: Lưu `hourly_rate` trực tiếp trên `users`, sync từ `position.default_hourly_rate` khi cập nhật chức danh.

**Lý do**: Đủ dùng cho giai đoạn hiện tại; tránh over-engineering.

**Hạn chế**: Không có lịch sử lương. Cần thêm bảng `user_salary_histories` nếu yêu cầu audit.

**Đề xuất tương lai**:
```sql
CREATE TABLE user_salary_histories (
  id INT PK,
  user_id INT FK,
  hourly_rate DECIMAL(10,2),
  effective_from DATE,
  effective_to DATE,
  created_by INT
);
```
