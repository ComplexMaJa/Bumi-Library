# Bumi Library <3

A modern library management system built with PHP and Bootstrap 5 for managing books, users, and borrowing activities.

![Bumi Library](https://preview.redd.it/miku-is-trying-to-understand-calculus-v0-yabwi30m7m0b1.png?width=640&crop=smart&auto=webp&s=942d12fdd458463c48404c47536fd99ed9a0ec1a)

## 🌟 Features

### For Administrators
- **User Management**: Add, edit, and manage user accounts with different roles (admin/user)
- **Book Management**: Catalog books with complete details (title, author, publisher, year, genre, stock)
- **Book Cover Images**: Upload and manage cover images for books
- **Dashboard**: Overview of library system with quick access to key functions

### For Users
- **Browse Books**: View and search through the library catalog
- **Borrow Books**: Borrow books with a simple click (limit of 3 books per user)
- **Track Loans**: See currently borrowed books and their due dates
- **Return Books**: Record book returns easily

### General Features
- **Responsive Design**: Works on desktops, tablets, and mobile devices
- **Beautiful UI**: Clean and modern Bootstrap 5 interface with animations
- **Dynamic Login**: Interactive login page with time-of-day animations
- **Multi-role Support**: Different interfaces for administrators and regular users
- **Search Capabilities**: Find books quickly with the search function
- **Pagination**: Navigate through large lists of books easily

## 🔧 Technologies Used

- **Backend**: PHP 7+
- **Database**: MySQL
- **Frontend**: 
  - Bootstrap 5
  - HTML5/CSS3
  - JavaScript
- **Libraries**:
  - Bootstrap Icons
  - Animate.css for smooth animations
  - Custom interactive UI elements

## 📋 Requirements

- PHP 7.0 or higher
- MySQL 5.6 or higher
- Web server (Apache/Nginx)
- Modern web browser

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/Bumi-Library.git
   ```

2. **Set up the database**
   - Create a MySQL database named `bumi_library`
   - Import the `bumi-library.sql` file to set up the database structure

3. **Configure the connection**
   - Edit `config/koneksi.php` with your database credentials
   ```php
   $host = 'localhost';  // Your database host
   $db_user = 'root';    // Your database username
   $db_pass = '';        // Your database password
   $db_name = 'bumi_library'; // Your database name
   ```

4. **Place in web server directory**
   - Move the entire project to your web server's document root (e.g., htdocs for XAMPP)

5. **Access the application**
   - Open your web browser and navigate to `http://localhost/Bumi-Library/`
   - Login with the default admin account:
     - Username: admin
     - Password: admin123

## 👥 User Roles

1. **Administrator**
   - Manage users (add, edit, delete)
   - Manage books (add, edit, delete)
   - View all books in the library

2. **User**
   - View available books
   - Borrow books (maximum 3 at a time)
   - Return borrowed books
   - Track borrowing history

## 📁 Project Structure

```
bumi-library/
├── assets/               # Static assets
│   ├── book_images/      # Book cover images
│   ├── bootstrap.css/    # Bootstrap CSS files
│   └── bootstrap.js/     # Bootstrap JS files
├── config/               # Configuration files
│   └── koneksi.php       # Database connection
├── pages/                # Application pages
│   ├── buku/             # Book management pages
│   │   ├── edit_buku.php
│   │   ├── hapus_buku.php
│   │   ├── list_buku.php
│   │   └── tambah_buku.php
│   ├── peminjaman/       # Borrowing management pages
│   │   ├── daftar_pinjaman.php
│   │   ├── kembalikan_buku.php
│   │   ├── pinjam_buku.php
│   │   └── proses_pinjam.php
│   └── user/             # User management pages
│       ├── edit_user.php
│       ├── hapus_user.php
│       ├── list_user.php
│       └── tambah_user.php
├── bumi-library.sql      # Database schema
├── dashboard.php         # User dashboard
├── index.php             # Entry point
├── login.php             # Login page
├── logout.php            # Logout functionality
└── README.md             # This documentation file
```

## 📸 Screenshots

(Insert screenshots here)

## ✨ Special Features

### Interactive Login Page
- Dynamic background that changes based on time of day (morning, day, evening, night)
- Animated book character with eye tracking that follows cursor movement
- Responsive design for all devices

### Book Management
- Upload and display book cover images
- Track book availability with stock management
- Detailed book information including title, author, publisher, year, and genre

### Borrowing System
- Automatic due date calculation (7 days from borrowing)
- Visual indicators for due dates and overdue books
- Prevents borrowing books that are out of stock

## 🔒 Security Features

- Password hashing for user accounts
- Role-based access control
- Input sanitization to prevent SQL injection
- Session management and authentication

## 👨‍💻 Development

Want to contribute? Great! 

1. Fork the project
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Bootstrap team for the amazing UI framework
- Icons provided by Bootstrap Icons
- Animation effects by Animate.css

---

Made with ❤️ for the love of books and learning