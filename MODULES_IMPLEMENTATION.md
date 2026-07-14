# New Modules Implementation Guide

## Module 4: Employee Retirement Management System

### Overview
Automatically monitors employee age and notifies HR when employees reach retirement age (60 years).

### Features Implemented
✅ **Automatic Age Calculation**
- Calculates employee age based on Date of Birth
- Updates automatically when the page is accessed

✅ **Retirement Eligibility Detection**
- Automatically marks employees as "Retirement Eligible" at age 60
- Marks employees as "Near Retirement" at age 59
- Displays notifications on the HR dashboard

✅ **Retirement Notifications**
- Automatic notifications when employee reaches 60 years
- Notifies both HR and Administrator roles
- Highlights in notifications panel

✅ **Retirement Dashboard**
- Total employees near retirement (within 1 year)
- Employees retiring this month
- Employees already retired
- Retirement eligible employees (age 60+)

✅ **Retirement Reports**
- Employee details with:
  - Employee Name, ID, Department, Position
  - Date of Birth, Current Age
  - Retirement Date, Status
- Export to CSV
- Print functionality

✅ **Retirement Status**
- Active: Under 59 years
- Near Retirement: 59 years old
- Retirement Eligible: 60+ years old
- Retired: Processed retirement

### Database Tables
- `retirements` table with fields:
  - id, employee_id, retirement_date, status, remarks, notified, created_at, updated_at

### Access URLs
- `/modules/retirement/index.php` - Main retirement dashboard
- `/modules/retirement/view.php?id={employee_id}` - View retirement details
- `/modules/retirement/process.php?id={employee_id}` - Process retirement
- `/modules/retirement/report.php` - Generate reports

### Permissions
- **Admin**: Full access to all retirement features
- **HR**: Full access to all retirement features
- **IT**: No access
- **Employee**: No access

---

## Module 5: Employee Clearance Management System

### Overview
Processes employee clearance before they officially leave the organization.

### Features Implemented
✅ **Employee Exit Request**
- HR creates exit request for resigning/retiring/terminated employees
- Employee status changes to "Pending Clearance"
- Exit reasons: Resignation, Retirement, Termination, Contract End, Other

✅ **Department Clearance**
5 departments must approve clearance:
1. Human Resources (HR)
2. IT Department
3. Finance
4. Store/Warehouse
5. Administration

Each department can:
- Approve
- Reject
- Add comments
- List assets returned

✅ **Asset Verification**
- IT department verifies returned assets
- Field to list: Laptop, Desktop, Printer, ID Card, Access Card, Keys, etc.

✅ **Financial Clearance**
- Finance verifies salary settlement, loans, advances, allowances, benefits
- Comments field for financial details

✅ **Clearance Progress**
- Visual progress bar showing completion percentage
- Department-by-department status tracking
- Real-time progress updates
- Example: "Overall Progress: 60%"

✅ **Final Approval**
- After all departments approve:
  - Certificate can be generated
  - Employee marked as "Cleared"
  - Employee status updated to "Inactive"
  - Record archived

✅ **Notifications**
- Notifies HR, Department Heads, and Employee
- Examples:
  - "Your clearance request has been submitted"
  - "Finance has approved your clearance"
  - "Employee clearance completed successfully"

✅ **Clearance Reports**
- Employee Name, ID, Department
- Reason for Leaving, Clearance Date
- Approval Status per Department
- Approved/Pending departments list
- Export to CSV and Print

### Database Tables
- `clearances` table with fields:
  - id, employee_id, exit_reason, exit_date
  - hr_status, it_status, finance_status, store_status, administration_status
  - overall_status, approved_by, certificate_generated, remarks
  - created_at, updated_at

- `clearance_approvals` table with fields:
  - id, clearance_id, department, status
  - approved_by, comments, assets_returned
  - created_at, updated_at

### Access URLs
- `/modules/clearance/index.php` - Main clearance dashboard
- `/modules/clearance/create.php` - Create new clearance
- `/modules/clearance/view.php?id={clearance_id}` - View clearance details
- `/modules/clearance/approve.php?id={clearance_id}` - Approve clearance
- `/modules/clearance/certificate.php?id={clearance_id}` - View certificate

### Permissions
- **Admin**: Can approve all departments
- **HR**: Can create clearances and approve HR department
- **IT**: Can approve IT department only
- **Employee**: No access

---

## Integration Points

### 1. Retirement → Clearance
When an employee is retired through the Retirement Management system, HR can initiate a clearance process by selecting "Retirement" as the exit reason.

### 2. Navigation Menu
Both modules are integrated into the sidebar:
- Retirement Management (icon: calendar-event)
- Clearance Management (icon: clipboard-check)

### 3. Dashboard Integration
Both modules send notifications to the main dashboard notification system.

### 4. Leave Management Connection
Retirement is conceptually linked to the Leave Management module as it's another form of employee time/status management.

---

## How to Use

### Retirement Management Workflow
1. System automatically calculates employee ages daily
2. When employee turns 60, status changes to "Retirement Eligible"
3. HR receives notification
4. HR views retirement details at `/modules/retirement/view.php?id={employee_id}`
5. HR processes retirement at `/modules/retirement/process.php?id={employee_id}`
6. Employee status updates to "Retired"
7. Generate reports as needed

### Clearance Management Workflow
1. HR creates clearance request at `/modules/clearance/create.php`
2. System notifies all departments
3. Each department head approves their section
4. Progress bar updates in real-time
5. When all departments approve:
   - Overall status becomes "Completed"
   - Certificate can be generated
   - Employee record archived
6. Generate reports as needed

---

## Technical Notes

- All database tables use foreign key constraints with CASCADE for data integrity
- Transactions are used for multi-step operations
- Proper error handling with rollback on failures
- Activity logging for all major actions
- CSRF protection on all forms
- Role-based access control throughout
- Responsive design for mobile compatibility
- Multi-language support (English, Amharic, Oromo)

---

## Testing Checklist

### Retirement Module
- [ ] Create employee with date of birth making them 60+ years old
- [ ] Verify they appear in "Retirement Eligible" section
- [ ] Process retirement and verify status changes
- [ ] Check notifications are sent to HR/Admin
- [ ] Generate and export report

### Clearance Module
- [ ] Create clearance request for an employee
- [ ] Approve as different departments
- [ ] Verify progress bar updates correctly
- [ ] Complete all approvals
- [ ] Verify final status is "Completed"
- [ ] Generate and export report

---

## Future Enhancements

### Retirement
- Automated email notifications
- Retirement pension calculation
- Retirement benefit tracking
- Historical retirement analytics

### Clearance
- Clearance certificate PDF generation
- Email notifications to each department
- Reminder system for pending approvals
- Clearance timeline visualization
- Integration with payroll for final settlement

---

## Support

For issues or questions:
- Check database schema in `/database/schema.sql`
- Review translation strings in `/includes/lang/en.php`
- Check permissions in `/includes/auth.php`
- Review routing in sidebar at `/includes/sidebar.php`
