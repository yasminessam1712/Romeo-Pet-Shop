# Romeo Pet Shop

Romeo Pet Shop is a web-based pet shop management system developed as a personal project to enhance my skills in web development and system design.

## Project Purpose
- To practice PHP and MySQL development
- To understand CRUD operations and database relationships
- To implement user authentication and role-based access
- To gain experience in building a complete web system

## Features
- User registration and login
- Product listing and management
- Shopping cart and checkout
- Order tracking and status updates
- Admin dashboard for managing products and orders

## Technologies Used
- PHP
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP

## Folder Structure
romeo-pet-shop/
├── admin/               # Admin pages
├── customer/            # Customer pages
├── staff/               # Staff pages
├── assets/              # Images, CSS, JS files
├── database/            # Database structure file
│   └── petshop.sql      # SQL structure (no sensitive data)
├── README.md            # Project README
└── .gitignore           # Ignore sensitive files

## Database Setup
1. Open phpMyAdmin (or another MySQL client).  
2. Create a new database named `petshop`.  
3. Import the SQL file located at `database/petshop.sql`.  
4. Create a `config.php` file with your local database credentials. Example:

<?php
$host = "localhost";
$user = "root";
$pass = ""; (depending on what server you are using)
$db   = "petshop";
?>

> Note: The database file contains **structure only**, no sensitive or real user data.

## How to Run
1. Install XAMPP or WAMP
2. Place the project folder in the `htdocs` directory if using XAMPP or 'www' if using WAMP.  
3. Start Apache and MySQL.  
4. Open your browser and go to: http://localhost/romeo-pet-shop

## Additional Notes
- This project is **not deployed live**. It is intended to be run locally for learning and development purposes.  
- All code and database files are included for easy setup.
