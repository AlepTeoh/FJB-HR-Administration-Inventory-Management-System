# HR Admin System v2.0
## FGV Group — Human Resources & Administration Portal

### 3 User Roles

| Role | Email | Password | Access |
|------|-------|----------|--------|
| Admin (IT) | admin@company.com | password | Full system control, module settings, user management |
| Admin (HR) | hr@company.com | hr123 | Staff registry, training, family, meeting rooms |
| Staff | staff@company.com | staff123 | View training, family info, book rooms |

### Quick Setup

1. Import `database_v2.sql` into MySQL
2. Edit `includes/config.php` with your DB credentials
3. Visit `setup.php?key=setup2024` to initialize passwords
4. Delete `setup.php` after setup
5. Login at `index.php`

### Data Loaded from Excel
- **505 staff** from FJB (FGV Johor Bulkers) and FBSB (FGV Bulkers Sdn Bhd)
- **40 departments** across both companies
- **108 training courses** with full titles
- **1,332 training attendance records**

### New Features in v2.0
- 3-role access control (Admin IT, Admin HR, Staff)
- Staff Registry page with department/company filters
- Training page with 4 views:
  - **By Department**: Card overview per department
  - **Department Detail**: All staff + courses for a department
  - **Course Detail**: All attendees for a specific course
  - **List View**: Filterable flat list
- System Settings (Admin IT): toggle modules on/off per role
- User Account management (Admin IT only)
