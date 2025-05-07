-- Database: Perpustakaan Muflih
-- Tabel: buku

-- INSERT DATA BUKU
INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, genre, stok, gambar) VALUES 
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', 'Novel', 15, 'laskar_pelangi.jpg'),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', '1980', 'Fiksi Sejarah', 12, 'bumi_manusia.jpg'),
('Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', '2009', 'Novel', 20, 'negeri_5_menara.jpg'),
('Filosofi Teras', 'Henry Manampiring', 'Kompas', '2018', 'Filsafat', 10, 'filosofi_teras.jpg'),
('Pulang', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', '2012', 'Novel Sejarah', 8, 'pulang.jpg'),
('Laut Bercerita', 'Leila S. Chudori', 'Kepustakaan Populer Gramedia', '2017', 'Novel Sejarah', 9, 'laut_bercerita.jpg'),
('Ayat-Ayat Cinta', 'Habiburrahman El Shirazy', 'Republika', '2004', 'Novel Islam', 14, 'ayat_cinta.jpg'),
('Sang Pemimpi', 'Andrea Hirata', 'Bentang Pustaka', '2006', 'Novel', 11, 'sang_pemimpi.jpg'),
('Perahu Kertas', 'Dee Lestari', 'Bentang Pustaka', '2009', 'Novel Romantis', 7, 'perahu_kertas.jpg'),
('Belajar Pemrograman Python', 'Jubilee Enterprise', 'Elex Media Komputindo', '2019', 'Teknologi', 15, 'python_programming.jpg'),
('Hujan', 'Tere Liye', 'Gramedia Pustaka Utama', '2016', 'Fiksi', 13, 'hujan.jpg'),
('Sejarah Dunia yang Disembunyikan', 'Jonathan Black', 'Alvabet', '2015', 'Sejarah', 6, 'sejarah_dunia.jpg'),
('Statistika Terapan', 'Singgih Santoso', 'Elex Media Komputindo', '2018', 'Pendidikan', 10, 'statistika.jpg'),
('Algoritma dan Pemrograman', 'Rinaldi Munir', 'Informatika', '2016', 'Teknologi', 9, 'algoritma.jpg'),
('Dasar-Dasar Manajemen', 'T. Hani Handoko', 'BPFE Yogyakarta', '2013', 'Manajemen', 8, 'manajemen.jpg'),
('Anatomi dan Fisiologi', 'Syaifuddin', 'EGC', '2011', 'Kesehatan', 7, 'anatomi.jpg'),
('Kimia Dasar', 'Raymond Chang', 'Erlangga', '2010', 'Pendidikan', 10, 'kimia.jpg'),
('Fisika Modern', 'Kenneth S. Krane', 'UI Press', '2012', 'Pendidikan', 6, 'fisika.jpg'),
('Ensiklopedia Hewan', 'John Woodward', 'DK Publishing', '2014', 'Ensiklopedia', 5, 'ensiklopedia_hewan.jpg'),
('Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Serambi', '2008', 'Sejarah', 8, 'sejarah_indonesia.jpg');

-- CATATAN: 
-- 1. Pastikan folder assets/book_images/ sudah dibuat dan dapat diakses
-- 2. Gambar sampul perlu disediakan dan ditempatkan di folder tersebut
-- 3. Gambar sampul harus sesuai dengan nama pada kolom 'gambar'
-- 4. Jika belum ada gambar, sistem akan menggunakan contoh-book.jpg