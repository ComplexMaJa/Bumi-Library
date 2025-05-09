<div align="center">

# ✨ Bumi Library <3 ✨

<img src="https://preview.redd.it/miku-is-trying-to-understand-calculus-v0-yabwi30m7m0b1.png?width=640&crop=smart&auto=webp&s=942d12fdd458463c48404c47536fd99ed9a0ec1a" width="500px">

### A modern library management system built with PHP and Bootstrap 5

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-purple.svg)](https://getbootstrap.com/)

</div>

---

## 📚 Overview

Bumi Library is a comprehensive library management system featuring book cataloging, user management, and a borrowing system. With its **modern, interactive UI** and powerful features, it helps libraries efficiently manage their collections and member activities.

<div align="center">
<table>
  <tr>
    <td align="center" width="33%"><b>📖 Book Management</b><br>Catalog, update, and track your entire collection</td>
    <td align="center" width="33%"><b>👥 User Management</b><br>Manage user accounts with different access levels</td>
    <td align="center" width="33%"><b>🔄 Borrowing System</b><br>Handle loans with automated due dates</td>
  </tr>
</table>
</div>

---

## 🌟 Key Features

### 📘 For Administrators
* **User Management** - Create and manage user accounts (admin/regular users)
* **Book Cataloging** - Add books with detailed info (title, author, publisher, etc.)
* **Book Cover Images** - Upload and manage visual representations
* **Admin Dashboard** - Get a complete overview of library activities

### 📗 For Regular Users
* **Browse Collection** - Search through available books
* **Borrow Books** - Check out books (up to 3 at once)
* **Track Loans** - Monitor borrowed books and due dates
* **Return System** - Easy process for returning books

### 📙 System Features
* **Responsive Design** - Works perfectly on all devices
* **Modern UI** - Clean Bootstrap 5 interface with animations
* **Engaging Login Experience** - Dynamic, time-based backgrounds and an interactive book character that reacts to user input.
* **Enhanced User Navigation** - Intuitive sidebar with quick access to core features like borrowing and viewing loaned books for all users.
* **Smart Search** - Find books quickly and efficiently
* **Pagination** - Navigate large collections with ease

---

<div align="center">

## 💻 Technologies Used

| Backend | Frontend | Libraries |
|:-------:|:--------:|:---------:|
| PHP 7+ | Bootstrap 5 | Bootstrap Icons |
| MySQL | HTML5/CSS3 | Animate.css |
|  | JavaScript | Custom UI Elements |

</div>

---

## 🚀 Installation Guide

### Prerequisites
* PHP 7.0 or higher
* MySQL 5.6 or higher
* Web server (Apache/Nginx)
* Web browser

### Setup Process

<details>
<summary><b>Step 1:</b> Clone the repository</summary>

```bash
git clone https://github.com/yourusername/Bumi-Library.git
```
</details>

<details>
<summary><b>Step 2:</b> Set up database</summary>

* Create a MySQL database named `bumi_library`
* Import the `bumi-library.sql` file
</details>

<details>
<summary><b>Step 3:</b> Configure connection</summary>

Edit `config/koneksi.php` with your database credentials:
```php
$host = 'localhost';  // Database host
$db_user = 'root';    // Database username
$db_pass = '';        // Database password
$db_name = 'bumi_library'; // Database name
```
</details>

<details>
<summary><b>Step 4:</b> Deploy and access</summary>

* Move the project to your web server's document root (e.g., htdocs for XAMPP)
* Navigate to `http://localhost/Bumi-Library/`
* Default admin login:
  * Username: admin
  * Password: admin123
</details>

---

## 👥 User Roles & Capabilities

<div align="center">
<table>
  <tr>
    <th>Administrator</th>
    <th>Regular User</th>
  </tr>
  <tr>
    <td>
      ✅ Manage user accounts<br>
      ✅ Add/edit/delete books<br>
      ✅ View all library data<br>
      ✅ Oversee the entire system
    </td>
    <td>
      ✅ View available books<br>
      ✅ Borrow books (max 3)<br>
      ✅ Return borrowed items<br>
      ✅ Track personal borrowing history
    </td>
  </tr>
</table>
</div>

---

## 📋 Project Structure

<details>
<summary>Click to expand project structure</summary>

```
bumi-library/
├── assets/               # Static resources
│   ├── book_images/      # Book cover images
│   ├── bootstrap.css/    # CSS files
│   └── bootstrap.js/     # JS files
├── config/               # Configuration
│   └── koneksi.php       # Database connection
├── pages/                # Application pages
│   ├── buku/             # Book management
│   ├── peminjaman/       # Borrowing system
│   └── user/             # User management
├── dashboard.php         # Main dashboard
├── login.php             # Authentication
└── [other files]         # Supporting files
```
</details>

---

## ✨ Special Features

### Dynamic Login Experience
* **Time-Based Backgrounds** - Morning, day, evening, and night themes
* **Interactive Book Character** - Brings the login page to life! The character's eyes follow your cursor, it blinks, tilts, and reacts when you focus on input fields.
* **Fully Responsive** - Perfect on all screen sizes

### Advanced Book Management
* **Cover Image System** - Visual catalog of your books
* **Stock Management** - Automatic inventory tracking
* **Detailed Information** - Complete book metadata

### Smart Borrowing System
* **Automatic Due Dates** - 7-day loan period calculation
* **Visual Status Indicators** - Easy to track due/overdue books
* **Stock Protection** - Prevents borrowing unavailable books

---

## 🔒 Security

* Password hashing with PHP's secure algorithms
* Role-based access control system
* Input sanitization against SQL injection
* Secure session management

---

## 👨‍💻 Contribute

Want to make Bumi Library even better? Here's how:

1. **Fork** the repository
2. **Create** a feature branch
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Commit** your changes
   ```bash
   git commit -m 'Add an amazing feature'
   ```
4. **Push** to your branch
5. Open a **Pull Request**

---

<div align="center">

## 📄 License

This project is available under the MIT License

## 🙏 Acknowledgments

Bootstrap team • Bootstrap Icons • Animate.css

<p>Made with ❤️ for the love of books and learning</p>
</div>