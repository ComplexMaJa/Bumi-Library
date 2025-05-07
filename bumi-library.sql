-- Buat database
CREATE DATABASE bumi_library;

-- Gunakan database tersebut
USE bumi_library;

-- Tabel users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL
);

-- Tabel buku
CREATE TABLE buku (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  pengarang VARCHAR(255) NOT NULL,
  penerbit VARCHAR(255) NOT NULL,
  tahun_terbit YEAR NOT NULL,
  genre VARCHAR(100) NOT NULL,
  stok INT NOT NULL
);

-- Password yang baru: admin123 dan user123 (dalam bentuk plain text)
INSERT INTO users (username, password, role) VALUES
('admin', 'admin123', 'admin'),
('user1', 'user123', 'user');


-- Tabel peminjaman
CREATE TABLE IF NOT EXISTS peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    status ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (buku_id) REFERENCES buku(id)
);

--tambah gambar
ALTER TABLE buku ADD COLUMN gambar VARCHAR(255) DEFAULT NULL;

-- Tambah data buku sample
INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, gambar) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 1925, 'Fiction', 5, 'the_great_gatsby.jpg'),
('To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', 1960, 'Fiction', 3, 'to_kill_a_mockingbird.jpg'),
('1984', 'George Orwell', 'Secker & Warburg', 1949, 'Dystopian', 7, '1984.jpg'),
('Pride and Prejudice', 'Jane Austen', 'T. Egerton', 1813, 'Romance', 4, 'pride_and_prejudice.jpg'),
('The Hobbit', 'J.R.R. Tolkien', 'George Allen & Unwin', 1937, 'Fantasy', 6, 'the_hobbit.jpg'),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'Bloomsbury', 1997, 'Fantasy', 8, 'harry_potter.jpg'),
('The Lord of the Rings', 'J.R.R. Tolkien', 'Allen & Unwin', 1954, 'Fantasy', 4, 'lord_of_the_rings.jpg'),
('The Catcher in the Rye', 'J.D. Salinger', 'Little, Brown and Company', 1951, 'Fiction', 5, 'catcher_in_the_rye.jpg'),
('Brave New World', 'Aldous Huxley', 'Chatto & Windus', 1932, 'Dystopian', 3, 'brave_new_world.jpg'),
('The Alchemist', 'Paulo Coelho', 'HarperCollins', 1988, 'Fiction', 7, 'the_alchemist.jpg'),
('Dune', 'Frank Herbert', 'Chilton Books', 1965, 'Science Fiction', 4, 'dune.jpg'),
('The Da Vinci Code', 'Dan Brown', 'Doubleday', 2003, 'Mystery', 6, 'da_vinci_code.jpg');

