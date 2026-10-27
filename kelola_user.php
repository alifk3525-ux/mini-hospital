<?php
include 'koneksi.php';

// 1. Proses Tambah User
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_lengkap'];
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $role = $_POST['role'];
    mysqli_query($koneksi, "INSERT INTO users (nama_lengkap, username, password, role) VALUES ('$nama', '$user', '$pass', '$role')");
    echo "<script>alert('User berhasil ditambah!'); window.location='kelola_user.php';</script>";
}

// 2. Proses Update / Edit User
if (isset($_POST['update'])) {
    $id   = $_POST['id_user'];
    $nama = $_POST['nama_lengkap'];
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $role = $_POST['role'];
    
    mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama', username='$user', password='$pass', role='$role' WHERE id_user='$id'");
    echo "<script>alert('Data user berhasil diperbarui!'); window.location='kelola_user.php';</script>";
}

// 3. Proses Hapus User
if (isset($_GET['hapus'])) {
    mysqli_query($koneksi, "DELETE FROM users WHERE id_user='$_GET[hapus]'");
    echo "<script>alert('User dihapus!'); window.location='kelola_user.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manajemen User - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; background: #f4f4f9; margin: 0; }
        .card { background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 10px; margin: 5px 0 15px 0; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        label { font-size: 13px; font-weight: bold; color: #475569; }
        button { color: white; border: none; font-weight: bold; cursor: pointer; border-radius: 4px; padding: 12px; width: 100%; font-size: 15px; }
        .btn-tambah { background: #28a745; }
        .btn-tambah:hover { background: #218838; }
        .btn-edit { background: #f59e0b; }
        .btn-edit:hover { background: #d97706; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #2c3e50; color: white; }
        .aksi-edit { background: #f59e0b; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 12px; }
        .aksi-hapus { background: #ef4444; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; margin-left: 5px; font-size: 12px; }
        .batal { display: block; text-align: center; margin-top: 15px; color: #ef4444; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <?php
    // LOGIKA TAMPILAN: Jika tombol edit diklik, tampilkan form edit. Jika tidak, tampilkan form tambah.
    if (isset($_GET['edit'])) {
        $id_edit = $_GET['edit'];
        $q_edit = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_edit'");
        $data_edit = mysqli_fetch_assoc($q_edit);
    ?>
    <!-- ================= FORM EDIT ================= -->
    <div class="card" style="border-top: 4px solid #f59e0b;">
        <h3 style="margin-top:0; color: #d97706;">✏️ Edit Data User</h3>
        <form method="POST">
            <input type="hidden" name="id_user" value="<?php echo $data_edit['id_user']; ?>">
            
            <label>Nama Lengkap Staf</label>
            <input type="text" name="nama_lengkap" value="<?php echo $data_edit['nama_lengkap']; ?>" required>
            
            <label>Username Login</label>
            <input type="text" name="username" value="<?php echo $data_edit['username']; ?>" required>
            
            <label>Password Login</label>
            <input type="text" name="password" value="<?php echo $data_edit['password']; ?>" required>
            
            <label>Role / Hak Akses</label>
            <select name="role">
                <option value="Petugas Pendaftaran" <?php if($data_edit['role']=='Petugas Pendaftaran') echo 'selected'; ?>>Petugas Pendaftaran</option>
                <option value="Tenaga Medis (Dokter/Perawat)" <?php if($data_edit['role']=='Tenaga Medis (Dokter/Perawat)') echo 'selected'; ?>>Tenaga Medis (Dokter/Perawat)</option>
                <option value="Coder Rekam Medis" <?php if($data_edit['role']=='Coder Rekam Medis') echo 'selected'; ?>>Coder Rekam Medis</option>
                <option value="Super Admin" <?php if($data_edit['role']=='Super Admin') echo 'selected'; ?>>Super Admin / Administrator</option>
            </select>
            
            <button type="submit" name="update" class="btn-edit">💾 Simpan Perubahan</button>
            <a href="kelola_user.php" class="batal">✖ Batal Edit</a>
        </form>
    </div>
    
    <?php } else { ?>
    
    <!-- ================= FORM TAMBAH ================= -->
    <div class="card" style="border-top: 4px solid #28a745;">
        <h3 style="margin-top:0; color: #28a745;">➕ Tambah Akses Baru</h3>
        <form method="POST">
            <label>Nama Lengkap Staf</label>
            <input type="text" name="nama_lengkap" placeholder="Contoh: dr. Andi" required>
            
            <label>Username Login</label>
            <input type="text" name="username" placeholder="Contoh: andi_medis" required>
            
            <label>Password Login</label>
            <input type="text" name="password" placeholder="Contoh: 12345" required>
            
            <label>Role / Hak Akses</label>
            <select name="role">
                <option value="Petugas Pendaftaran">Petugas Pendaftaran</option>
                <option value="Tenaga Medis (Dokter/Perawat)">Tenaga Medis (Dokter/Perawat)</option>
                <option value="Coder Rekam Medis">Coder Rekam Medis</option>
                <option value="Super Admin">Super Admin / Administrator</option>
            </select>
            
            <button type="submit" name="tambah" class="btn-tambah">+ Simpan Akses</button>
        </form>
    </div>
    <?php } ?>

    <!-- ================= TABEL DATA ================= -->
    <div class="card">
        <h3 style="margin-top:0; color: #2c3e50;">👥 Daftar Pengguna Sistem</h3>
        <table>
            <tr>
                <th>Nama Staf</th>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
            <?php
            $q = mysqli_query($koneksi, "SELECT * FROM users ORDER BY role ASC");
            while ($d = mysqli_fetch_assoc($q)) {
                echo "<tr>
                    <td>{$d['nama_lengkap']}</td>
                    <td style='font-weight:bold; color:#2563eb;'>{$d['username']}</td>
                    <td>{$d['password']}</td>
                    <td><span style='background:#e2e8f0; padding:4px 8px; border-radius:4px; font-size:12px;'>{$d['role']}</span></td>
                    <td>
                        <a href='?edit={$d['id_user']}' class='aksi-edit'>✏️ Edit</a>
                        <a href='?hapus={$d['id_user']}' class='aksi-hapus' onclick=\"return confirm('Cabut akses user ini secara permanen?');\">🗑️ Hapus</a>
                    </td>
                </tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>