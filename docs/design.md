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