CREATE DATABASE donor_darah;
USE donor_darah;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','donor') DEFAULT 'donor',
    jenis_kelamin ENUM('L','P'),
    tanggal_lahir DATE,
    berat_badan INT,
    golongan_darah ENUM('A','B','AB','O'),
    rhesus ENUM('+','-'),
    no_telepon VARCHAR(30),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stok_darah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    golongan_darah ENUM('A','B','AB','O') NOT NULL,
    rhesus ENUM('+','-') NOT NULL,
    jumlah_kantong INT DEFAULT 0,
    batas_kritis INT DEFAULT 5
);

CREATE TABLE jadwal_donor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal_donor DATE NOT NULL,
    sesi ENUM('pagi','siang','sore') NOT NULL,
    lokasi VARCHAR(150),
    status ENUM('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
    keterangan_tolak TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE riwayat_donor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jadwal_id INT,
    tanggal DATE NOT NULL,
    golongan_darah ENUM('A','B','AB','O') NOT NULL,
    rhesus ENUM('+','-') NOT NULL,
    volume_ml INT NOT NULL,
    hb DECIMAL(4,1),
    tekanan_darah VARCHAR(20),
    petugas VARCHAR(100),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

INSERT INTO stok_darah (golongan_darah, rhesus, jumlah_kantong, batas_kritis) VALUES
('A', '+', 12, 5), ('A', '-', 4, 5),
('B', '+', 10, 5), ('B', '-', 3, 5),
('AB', '+', 8, 5), ('AB', '-', 2, 5),
('O', '+', 14, 5), ('O', '-', 4, 5);
