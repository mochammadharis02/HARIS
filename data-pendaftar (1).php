<?php
require_once 'auth_check.php';
include 'koneksi.php';
// Koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
// Ambil semua data pasien
$sql = "SELECT * FROM pasien ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Pendaftar Pasien</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f0fdfd;
      padding: 30px;
      color: #004d4d;
    }
    h2 {
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: white;
      box-shadow: 0 5px 15px rgba(0,128,128,0.1);
    }
    th, td {
      padding: 12px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: teal;
      color: white;
    }
    .btn {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 4px;
      text-decoration: none;
      color: white;
      font-size: 14px;
    }
    .btn-edit {
      background-color: #4CAF50;
      margin-right: 5px;
    }
    .btn-hapus {
      background-color: #f44336;
    }
  </style>
</head>
<body>

  <!-- Tombol Logout -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
    <span style="font-size:14px; color:#004d4d;">👤 Login sebagai: <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong></span>
    <a href="logout.php" style="background-color:#f44336; padding:8px 16px; border-radius:4px; color:white; text-decoration:none; font-size:14px; font-weight:bold;">🚪 Logout</a>
  </div>

  <h2>Data Pendaftar Pasien</h2>
  <a href="cetak-pdf.php" class="btn btn-edit">Cetak PDF</a>
  <a href="export-excel.php" class="btn btn-edit" style="background-color:green;">Export Excel</a>
  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>Jenis Kelamin</th>
        <th>Alamat</th>
        <th>Poli</th>
        <th>Dokter</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      while ($row = $result->fetch_assoc()) {
      ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= $row['nama']; ?></td>
        <td><?= $row['nik']; ?></td>
        <td><?= $row['gender'];?></td>
        <td><?= $row['alamat']; ?></td>
        <td><?= $row['poli']; ?></td>
        <td><?= $row['dokter']; ?></td>
        <td>
          <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-edit">Edit</a>
          <a href="hapus.php?id=<?= $row['id']; ?>" class="btn btn-hapus" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
          <a href="tambah.php" class="btn btn-edit" style="background-color:blue;"> Tambah Pasien</a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>

</body>
</html>