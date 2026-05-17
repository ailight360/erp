# ERP System - Installation Guide

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server

## Installation Steps

### 1. Database Setup
```bash
mysql -u root -p < database.sql
```

### 2. Configuration
Edit `config/database.php` with your credentials:
```php
$host = 'localhost';
$dbname = 'erp_system';
$username = 'root';
$password = '';
```

### 3. Access
Navigate to: `http://localhost/workspace/login.php`

## Features
- Stock In/Out Management
- Inventory Tracking
- Resin Material Management
- Company & Dropdown Settings
- Dark/Light Theme

## File Structure
```
/workspace
├── config/database.php
├── includes/
├── modules/
├── assets/css/style.css
├── assets/js/app.js
├── database.sql
├── index.php
└── login.php
```
