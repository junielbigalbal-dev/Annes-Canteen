# School Canteen Management System

A comprehensive web-based solution for managing a school canteen's operations, including order management, menu management, and reporting. This system is built with PHP, MySQL, and Bootstrap 5.

## Features

- **Admin Dashboard**
  - Real-time statistics and analytics
  - Order management
  - Menu and category management
  - User and role management
  - Sales reports and analytics

- **User Authentication**
  - Secure login/logout
  - Role-based access control
  - Password reset functionality

- **Menu Management**
  - Add, edit, delete menu items
  - Categorize menu items
  - Update pricing and availability

- **Order Processing**
  - Place new orders
  - Track order status
  - Order history
  - Print receipts

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Web browser with JavaScript enabled

## Installation

1. **Prerequisites**
   - Install XAMPP/WAMP/MAMP (includes Apache, MySQL, PHP)
   - Start Apache and MySQL services

2. **Setup Database**
   - Create a new MySQL database named `canteen_db`
   - Import the provided SQL file (if available) or run the database schema from `database/schema.sql`

3. **Configuration**
   - Copy `config/database.sample.php` to `config/database.php`
   - Update database credentials in `config/database.php`
   - Ensure `admin/` directory has proper write permissions

4. **Access the Application**
   - Place the project in your web server's root directory (e.g., `htdocs/canteen`)
   - Access via: `http://localhost/canteen`
   - Admin panel: `http://localhost/canteen/admin`

## Default Login Credentials

- **Admin Panel**
  - Username: `admin`
  - Password: `admin123` (change after first login)

- **User Login**
  - Username: `user`
  - Password: `user123`

## Project Structure

```
canteen/
├── admin/                  # Admin panel files
│   ├── assets/             # CSS, JS, images
│   │   ├── css/            # Stylesheets
│   │   ├── js/             # JavaScript files
│   │   └── img/            # Images and icons
│   └── includes/           # PHP includes
│       ├── header.php      # Common header
│       └── footer.php      # Common footer
├── config/                 # Configuration files
│   └── database.php        # Database configuration
├── index.php              # Main entry point
└── README.md              # This file
```

## Security

- All passwords are hashed using PHP's `password_hash()`
- CSRF protection enabled
- Prepared statements used for all database queries
- Input validation and sanitization
- Session management with proper security measures

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please contact your system administrator or IT department.

## Screenshots

![Admin Dashboard](screenshots/dashboard.png)
*Admin Dashboard with statistics and quick actions*
