# SHASHE E GOVERNMENT

Web-based organizational resource management system for IT assets, employees, and attendance tracking.

## Features

- **Authentication** – Login, logout, password change, password recovery
- **User Management** – Role-based access (Admin, HR Officer, IT Officer, Employee)
- **IT Asset Inventory** – Categories, assignment, status tracking, warranty dates
- **Employee Directory** – Full CRUD, photos, department management
- **Attendance** – Daily tracking, check-in/out, working hours
- **Leave Management** – Request and approval workflow
- **Reports** – CSV export, print-friendly views
- **Dashboard** – Real-time statistics
- **Notifications** – System alerts
- **Multilingual** – English, Afaan Oromoo (OR), Amharic (AM)

## Tech Stack

- PHP 8+
- MySQL 8+
- Bootstrap 5.3.3
- HTML5, CSS3, JavaScript

## Installation (XAMPP)

1. Copy project to `C:\xampp\htdocs\Shashemene e gevernment`
2. Start Apache and MySQL in XAMPP Control Panel
3. Open `http://localhost/Shashemene%20e%20gevernment/install.php`
4. Click **Install Database**
5. Login at `http://localhost/Shashemene%20e%20gevernment/login.php`

### Default Credentials

| Username | Password   |
|----------|------------|
| admin    | Admin@123  |

**Delete `install.php` after installation.**

## Configuration

Edit `config/database.php` for MySQL credentials:

```php
'host' => 'localhost',
'database' => 'shashe_egovernment',
'username' => 'root',
'password' => '',
```

Edit `config/app.php` for base URL if needed.

## Security

- bcrypt password hashing
- PDO prepared statements (SQL injection protection)
- CSRF tokens on forms
- Session management with httponly cookies
- Role-based access control
- Input sanitization (XSS protection)
- Upload directory script blocking

## Languages

Use the language switcher in the top navigation bar to switch between:

- English (EN)
- Afaan Oromoo (OR)
- Amharic (AM)

## Project Structure

```
├── assets/          CSS, JS, images, uploads
├── config/          App and database config
├── database/        SQL schema
├── includes/        Core PHP, auth, i18n, layout
├── modules/         Feature modules (dashboard, users, etc.)
├── index.php        Entry redirect
├── login.php        Authentication
└── install.php      One-time DB setup
```

## License

Proprietary – SHASHE E GOVERNMENT Project
