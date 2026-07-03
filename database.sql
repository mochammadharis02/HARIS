CREATE DATABASE IF NOT EXISTS puskesmas;
USE puskesmas;

CREATE TABLE pasien (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100),
  nik VARCHAR(16),
  gender VARCHAR(20),
  alamat TEXT,
  tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

