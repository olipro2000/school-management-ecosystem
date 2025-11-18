# School Management Ecosystem Database Schema

## Overview
Complete MySQL database schema for a multi-role School Management System built for PHP PDO integration.

## Database Structure

### Core Tables
- **users** - Centralized user management for all roles
- **students** - Student information linked to users and classes
- **parents** - Parent information with relationships
- **classes** - Class and section management
- **subjects** - Subject assignments to classes and teachers

### Academic Management
- **exams** - Exam scheduling and management
- **grades** - Student results and grading
- **attendance** - Role-based attendance tracking

### Financial Management
- **payments** - Receipt-based fee payments (screenshot verification)
- **salaries** - Staff salary management

### Library System
- **books** - Book catalog for reference
- **student_library_records** - Individual student book records

### Transport Management
- **buses** - Bus fleet management
- **transport_subscriptions** - Student transport subscriptions

### Communication
- **announcements** - System-wide announcements
- **messages** - Direct messaging between users
- **activity_logs** - Complete system activity tracking

### Utility Tables
- **settings** - System configuration
- **academic_years** - Academic year management

## Key Features

### Database Design
- Normalized schema with proper foreign key relationships
- Comprehensive indexing for optimal performance
- Receipt-based payment verification system
- Useful views for common queries

### Security & Performance
- Proper CASCADE and SET NULL constraints
- Indexed columns for fast queries
- JSON support for activity logging
- Timestamp tracking on all tables

### Multi-Role Support
- Admin, Teacher, Student, Parent, Accountant, Librarian roles
- Role-based access control ready
- Flexible user management system

## Installation

1. Import the database schema:
```sql
mysql -u root -p < database/school_ecosystem.sql
```

2. Update database credentials in `config/db.php`

3. Default admin login:
   - Email: admin@school.com
   - Password: password (hash provided in schema)

## Database Views

### student_details
Complete student information with class and parent details

### teacher_subjects
Teacher-subject assignments with class information

### monthly_attendance_summary
Monthly attendance statistics by user and role

## Payment System

- **Receipt-based verification** - All payments require screenshot upload
- **Admin verification** - Payments verified by admin/accountant
- **Bank reference tracking** - Optional bank transaction references

## Usage with PHP PDO

```php
require_once 'config/db.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE role = ?");
$stmt->execute(['student']);
$students = $stmt->fetchAll();
```

## Schema Highlights

- **17 main tables** covering all school operations
- **Foreign key relationships** ensuring data integrity
- **Comprehensive indexing** for performance
- **Receipt-based payment system** with verification workflow
- **Complete audit trail** through activity logs
- **Transport management** with route tracking
- **Student library records** for individual tracking
- **Multi-audience announcements** system
- **Direct messaging** between all user types

This schema supports a complete school ecosystem without hostel management, designed for scalability and production use.