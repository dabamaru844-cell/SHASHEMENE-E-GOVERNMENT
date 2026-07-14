# REST API Layer

This directory contains the REST API layer that bridges the JavaScript frontend application with the existing PHP backend system.

## Directory Structure

```
/api/
├── index.php              # Entry point - routes all requests
├── router.php             # Request router and dispatcher
├── database.php           # Database connection using existing config
├── middleware/
│   ├── cors.php          # CORS headers handler
│   ├── auth.php          # JWT validation middleware
│   └── rbac.php          # Role-based authorization
├── utils/
│   ├── jwt.php           # JWT generation and validation
│   ├── response.php      # Standardized JSON responses
│   └── validator.php     # Input validation utilities
└── handlers/
    ├── auth.php          # Authentication endpoints
    ├── employees.php     # Employee CRUD operations
    ├── assets.php        # Asset management endpoints
    ├── attendance.php    # Attendance recording
    ├── departments.php   # Department listing
    └── users.php         # User management (admin)
```

## Features

- **JWT Authentication**: Stateless token-based authentication
- **CORS Support**: Enables cross-origin requests from frontend
- **Role-Based Access Control**: Enforces permissions (admin, hr, it, employee)
- **Standardized Responses**: Consistent JSON response format
- **Input Validation**: Comprehensive validation for all endpoints
- **Activity Logging**: Audit trail for all user actions

## Technology Stack

- PHP 7.4+
- Firebase PHP-JWT library
- MySQL 8+ with PDO
- RESTful HTTP conventions

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user profile

### Employees
- `GET /api/employees` - List employees
- `GET /api/employees/:id` - Get employee details
- `POST /api/employees` - Create employee
- `PUT /api/employees/:id` - Update employee
- `DELETE /api/employees/:id` - Delete employee

### Assets
- `GET /api/assets` - List assets
- `GET /api/assets/:id` - Get asset details
- `POST /api/assets` - Create asset
- `PUT /api/assets/:id` - Update asset
- `DELETE /api/assets/:id` - Delete asset

### Attendance
- `GET /api/attendance` - List attendance records
- `POST /api/attendance` - Check-in/check-out
- `GET /api/attendance/reports` - Attendance reports

### Departments
- `GET /api/departments` - List departments

### Users (Admin Only)
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `PUT /api/users/:id` - Update user
- `DELETE /api/users/:id` - Delete user

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

## Status Codes

- `200 OK` - Successful GET, PUT, DELETE
- `201 Created` - Successful POST with resource creation
- `400 Bad Request` - Validation errors
- `401 Unauthorized` - Missing or invalid JWT
- `403 Forbidden` - Insufficient role permissions
- `404 Not Found` - Resource does not exist
- `409 Conflict` - Duplicate resource
- `500 Internal Server Error` - Server errors
- `503 Service Unavailable` - Database connection failure
