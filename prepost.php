<?php
include 'koneksi.php';

// Filter berdasarkan pilihan dropdown jenis_prepost
$jenis_prepost = isset($_GET['jenis_prepost']) ? $_GET['jenis_prepost'] : ''; // Ambil nilai dari parameter GET
$jenis_post = isset($_GET['jenis_post']) ? $_GET['jenis_post'] : ''; // Ambil nilai dari parameter GET
$sumber_dana = isset($_GET['sumber_dana']) ? $_GET['sumber_dana'] : ''; // Ambil nilai dari parameter GET

// Query untuk Pre-Financing Aktif (post='1')
$sql_pfb = $conn->prepare("SELECT plafond FROM pfb WHERE ket = ? OR ket = ?");
$sql_pfb->bind_param("ss", $jenis_prepost, $jenis_post);
$sql_pfb->execute();
$result_pfb = $sql_pfb->get_result();
$plafond = 0;
if ($result_pfb->num_rows > 0) {
    while ($row_pfb = $result_pfb->fetch_assoc()) {
        $plafond += $row_pfb['plafond'];
    }
}
$sql_pre = $conn->prepare("SELECT * FROM pre WHERE post='1' AND (jenis_prepost = ?)");
$sql_pre->bind_param("s", $jenis_prepost);
$sql_pre->execute();
$result_pre = $sql_pre->get_result();

// Initialize subtotal variables
$subtotal_total = 0;
$subtotal_os = 0;
$subtotal_difference = 0;

// Query untuk Pre-Financing On Process (post='0')
$sql_pre_op = $conn->prepare("SELECT * FROM pre WHERE post='0' AND (jenis_prepost = ? )");
$sql_pre_op->bind_param("s", $jenis_prepost);
$sql_pre_op->execute();
$result_pre_op = $sql_pre_op->get_result();

$sql_post = $conn->prepare("SELECT * FROM post WHERE post='1' AND (jenis_post = ? )");
$sql_post->bind_param("s", $jenis_post);
$sql_post->execute();
$result_post = $sql_post->get_result();

$sql_post_op = $conn->prepare("SELECT * FROM post WHERE post='0' AND (jenis_post = ? )");
$sql_post_op->bind_param("s", $jenis_post);
$sql_post_op->execute();
$result_post_op = $sql_post_op->get_result();

$sql_um = $conn->prepare("SELECT * FROM um WHERE post='0' AND (sumber_dana = ? )");
$sql_um->bind_param("s", $sumber_dana);
$sql_um->execute();
$result_um = $sql_um->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pre-Financing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>

</head>

<body>
    <div class="tabs">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a title="" class="nav-link active" data-bs-toggle="tab" href="#pre_financing">Pre - Financing</a>
            </li>
            <li class="nav-item">
                <a title="" class="nav-link" data-bs-toggle="tab" href="#post_financing">Post - Financing</a>
            </li>
            <li class="nav-item">
                <a title="" class="nav-link" data-bs-toggle="tab" href="#uang_masuk">Uang Masuk</a>
            </li>
        </ul>
    </div>
    <div class="tab-content">
        <div id="pre_financing" class="tab-pane fade show active">
            <div class="container mt-5">
                <h1 class="text-Left mb-4">Pre-Financing</h1>
                <div class="form-group">
                    <select class="form-control" id="jenis_prepost" name="jenis_prepost">
                        <option value="" style="font-style:italic;">-- Pilih jenis Prefinancing --</option>
                        <option value="BNI - Prefinancing" <?php if ($jenis_prepost == 'BNI - Prefinancing') echo 'selected'; ?>>BNI - Prefinancing</option>
                        <!-- <option value="BRI - KMK WA" <?php if ($jenis_prepost == 'BRI - KMK WA') echo 'selected'; ?>>BRI - KMK WA</option>
                        <option value="BRI - KMK CO TETAP" <?php if ($jenis_prepost == 'BRI - KMK CO TETAP') echo 'selected'; ?>>BRI - KMK CO TETAP</option> -->
                        <option value="BCA - Time Loan Revolving 1" <?php if ($jenis_prepost == 'BCA - Time Loan Revolving 1') echo 'selected'; ?>>BCA - Time Loan Revolving 1</option>
                    </select>
                </div>
                <br>
                <script>
                    document.getElementById('jenis_prepost').addEventListener('change', function() {
                        const jenis_prepost = this.value;
                        const currentHash = '#pre_financing'; // Tetapkan hash untuk tab Pre-Financing
                        const currentURL = new URL(window.location.href);
                        currentURL.searchParams.set('jenis_prepost', jenis_prepost); // Tambahkan atau perbarui parameter jenis_prepost
                        currentURL.searchParams.delete('jenis_post'); // Hapus parameter jenis_post jika ada
                        currentURL.hash = currentHash; // Pastikan hash tetap
                        window.location.href = currentURL.toString(); // Redirect ke URL baru
                    });
                </script>
                <a class="btn btn-info" href="pre_input.php"><i class="bi bi-plus-circle"></i></a>
                <span>&nbsp;</span>
                <a class="btn btn-danger" href="dashboard.php"><i class="bi bi-backspace"></i></a>
                <br><br>
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>HUTANG</th>
                            <th>TANGGAL CAIR</th>
                            <th>TAHAPAN</th>
                            <th>TANGGAL JATUH TEMPO</th>
                            <th>NOMINAL</th>
                            <th>PENUTUPAN</th>
                            <th>SISA OS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_pre->num_rows > 0) {
                            while ($row = $result_pre->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['jenis_prepost'] . "</td>";
                                echo "<td>" . $row['tanggal_prepost'] . "</td>";
                                echo "<td>" . "TAHAP " . $row['tahapan'] . "</td>";
                                echo "<td>" . $row['tanggal_jatuh_tempo'] . "</td>";
                                echo "<td>" . number_format($row['total'], 2, ',', '.') . "</td>";
                                echo "<td>" . number_format($row['os'], 2, ',', '.') . "</td>";
                                echo "<td>" . number_format(($row['total'] - $row['os']), 2, ',', '.') . "</td>";
                                echo "<td class='text-center'><a class='btn btn-success' onclick=\"showPreRowInfo(" . htmlspecialchars($row['id']) . ")\"><i class=\"bi bi-info\"></i></a></td>";
                                $subtotal_total += $row['total'];
                                $subtotal_os += $row['os'];
                                $subtotal_difference += ($row['total'] - $row['os']);
                                $plafond += $row['plafond'];
                            }
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Subtotal</strong></td>";
                            echo "<td>" . number_format($subtotal_total, 2, ',', '.') . "</td>";
                            echo "<td>" . number_format($subtotal_os, 2, ',', '.') . "</td>";
                            echo "<td colspan='2'>" . number_format($subtotal_difference, 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Plafond Full</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format($plafond, 2, ',', '.') . "</td>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Remaining Plafond</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format(($plafond - $subtotal_difference), 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Tidak ada data</td></tr>";
                        }
                        ?>
                    </tbody>
                    <table class="table table-bordered table-striped table-hover">
                        <p><strong>Pre-Financing On Prosses</strong></p>
                        <thead class="table-dark">
                            <tr class="text-center">
                                <th>HUTANG</th>
                                <th>TANGGAL CAIR</th>
                                <th>TAHAPAN</th>
                                <th>TANGGAL JATUH TEMPO</th>
                                <th>NOMINAL</th>
                                <th>PENUTUPAN</th>
                                <th>SISA OS</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_pre_op->num_rows > 0) {
                                while ($row = $result_pre_op->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['jenis_prepost'] . "</td>";
                                    echo "<td>" . $row['tanggal_prepost'] . "</td>";
                                    echo "<td>" . "TAHAP " . $row['tahapan'] . "</td>";
                                    echo "<td>" . $row['tanggal_jatuh_tempo'] . "</td>";
                                    echo "<td>" . number_format($row['total'], 2, ',', '.') . "</td>";
                                    echo "<td>" . number_format($row['os'], 2, ',', '.') . "</td>";
                                    echo "<td>" . number_format(($row['total'] - $row['os']), 2, ',', '.') . "</td>";
                                    echo "<td class='text-center'><a class='btn btn-warning' onclick=\"showEditModal('pre', " . htmlspecialchars($row['id']) . ")\"><i class=\"bi bi-send-check\"></i></a></td>";

                                    $subtotal_total += $row['total'];
                                    $subtotal_os += $row['os'];
                                    $subtotal_difference += ($row['total'] - $row['os']);
                                    $plafond += $row['plafond'];
                                }
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Subtotal</strong></td>";
                                echo "<td>" . number_format($subtotal_total, 2, ',', '.') . "</td>";
                                echo "<td>" . number_format($subtotal_os, 2, ',', '.') . "</td>";
                                echo "<td colspan='2'>" . number_format($subtotal_difference, 2, ',', '.') . "</td>";
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Plafond Full</strong></td>";
                                echo "<td colspan='2' class='text-end'><strong></strong></td>";
                                echo "<td colspan='2'>" . number_format($plafond, 2, ',', '.') . "</td>";
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Remaining Plafond</strong></td>";
                                echo "<td colspan='2' class='text-end'><strong></strong></td>";
                                echo "<td colspan='2'>" . number_format(($plafond - $subtotal_difference), 2, ',', '.') . "</td>";
                                echo "</tr>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>Tidak ada data</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
            </div>
        </div>
        <div id="post_financing" class="tab-pane fade show active">
            <div class="container mt-5">
                <h1 class="text-Left mb-4">Post-Financing</h1>
                <div class="form-group">
                    <select class="form-control" id="jenis_post" name="jenis_post">
                        <option value="" style="font-style:italic;">-- Pilih jenis Post Financing --</option>
                        <option value="BNI - Post Invoice Sinarmas" <?php if ($jenis_post == 'BNI - Post Invoice Sinarmas') echo 'selected'; ?>>BNI - Post Invoice Sinarmas</option>
                        <!-- <option value="BNI - SCF Post PLN" <?php if ($jenis_post == 'BNI - SCF Post PLN') echo 'selected'; ?>>BNI - SCF Post PLN</option> -->
                        <option value="BCA - Time Loan Revolving 2" <?php if ($jenis_post == 'BCA - Time Loan Revolving 2') echo 'selected'; ?>>BCA - Time Loan Revolving 2</option>
                        <!-- <option value="BCA - Kredit Lokal" <?php if ($jenis_post == 'BCA - Kredit Lokal') echo 'selected'; ?>>BCA - Kredit Lokal</option> -->
                    </select>
                </div>
                <br>
                <script>
                    document.getElementById('jenis_post').addEventListener('change', function() {
                        const jenis_post = this.value;
                        const currentHash = '#post_financing'; // Tetapkan hash untuk tab Post-Financing
                        const currentURL = new URL(window.location.href);
                        currentURL.searchParams.set('jenis_post', jenis_post); // Tambahkan atau perbarui parameter jenis_post
                        currentURL.searchParams.delete('jenis_prepost'); // Hapus parameter jenis_pre jika ada
                        currentURL.hash = currentHash; // Pastikan hash tetap
                        window.location.href = currentURL.toString(); // Redirect ke URL baru
                    });
                </script>
                <a class="btn btn-info" href="post_input.php"><i class="bi bi-plus-circle"></i></a>
                <span>&nbsp;</span>
                <a class="btn btn-danger" href="dashboard.php"><i class="bi bi-backspace"></i></a>
                <br><br>
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>HUTANG</th>
                            <th>TANGGAL CAIR</th>
                            <th>TAHAPAN</th>
                            <th>TANGGAL JATUH TEMPO</th>
                            <th>NOMINAL</th>
                            <th>PENUTUPAN</th>
                            <th>SISA OS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_post->num_rows > 0) {
                            while ($row = $result_post->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['jenis_post'] . "</td>";
                                echo "<td>" . $row['tanggal_post'] . "</td>";
                                echo "<td>" . "TAHAP " . $row['tahapan_post'] . "</td>";
                                echo "<td>" . $row['tanggal_jatuh_tempo'] . "</td>";
                                echo "<td>" . number_format($row['released'], 2, ',', '.') . "</td>";
                                echo "<td>" . number_format($row['os'], 2, ',', '.') . "</td>";
                                echo "<td>" . number_format(($row['released'] - $row['os']), 2, ',', '.') . "</td>";
                                echo "<td class='text-center'><a class='btn btn-success' onclick=\"showPostRowInfo(" . htmlspecialchars($row['id']) . ")\"><i class=\"bi bi-info\"></i></a></td>";
                                $subtotal_total += $row['released'];
                                $subtotal_os += $row['os'];
                                $subtotal_difference += ($row['released'] - $row['os']);
                                $plafond += $row['plafond'];
                            }
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Subtotal</strong></td>";
                            echo "<td>" . number_format($subtotal_total, 2, ',', '.') . "</td>";
                            echo "<td>" . number_format($subtotal_os, 2, ',', '.') . "</td>";
                            echo "<td colspan='2'>" . number_format($subtotal_difference, 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Plafond Full</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format($plafond, 2, ',', '.') . "</td>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Remaining Plafond</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format(($plafond - $subtotal_difference), 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Tidak ada data</td></tr>";
                        }
                        ?>
                    </tbody>
                    <table class="table table-bordered table-striped table-hover">
                        <p><strong>Post-Financing On Prosses</strong></p>
                        <thead class="table-dark">
                            <tr class="text-center">
                                <th>HUTANG</th>
                                <th>TANGGAL CAIR</th>
                                <th>TAHAPAN</th>
                                <th>TANGGAL JATUH TEMPO</th>
                                <th>NOMINAL</th>
                                <th>PENUTUPAN</th>
                                <th>SISA OS</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_post_op->num_rows > 0) {
                                while ($row = $result_post_op->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['jenis_post'] . "</td>";
                                    echo "<td>" . $row['tanggal_post'] . "</td>";
                                    echo "<td>" . "TAHAP " . $row['tahapan_post'] . "</td>";
                                    echo "<td>" . $row['tanggal_jatuh_tempo'] . "</td>";
                                    echo "<td>" . number_format($row['released'], 2, ',', '.') . "</td>";
                                    echo "<td>" . number_format($row['os'], 2, ',', '.') . "</td>";
                                    echo "<td>" . number_format(($row['released'] - $row['os']), 2, ',', '.') . "</td>";
                                    echo "<td class='text-center'><a class='btn btn-warning' onclick=\"showEditModal('post', " . htmlspecialchars($row['id']) . ")\"><i class=\"bi bi-send-check\"></i></a></td>";
                                    $subtotal_total += $row['released'];
                                    $subtotal_os += $row['os'];
                                    $subtotal_difference += ($row['released'] - $row['os']);
                                    $plafond += $row['plafond'];
                                }
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Subtotal</strong></td>";
                                echo "<td>" . number_format($subtotal_total, 2, ',', '.') . "</td>";
                                echo "<td>" . number_format($subtotal_os, 2, ',', '.') . "</td>";
                                echo "<td colspan='2'>" . number_format($subtotal_difference, 2, ',', '.') . "</td>";
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Plafond Full</strong></td>";
                                echo "<td colspan='2' class='text-end'><strong></strong></td>";
                                echo "<td colspan='2'>" . number_format($plafond, 2, ',', '.') . "</td>";
                                echo "<tr class='table-secondary'>";
                                echo "<td colspan='4' class='text-end'><strong>Remaining Plafond</strong></td>";
                                echo "<td colspan='2' class='text-end'><strong></strong></td>";
                                echo "<td colspan='2'>" . number_format(($plafond - $subtotal_difference), 2, ',', '.') . "</td>";
                                echo "</tr>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>Tidak ada data</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
            </div>
        </div>
        <div id="uang_masuk" class="tab-pane fade show active">
            <div class="container mt-5">

                <h1 class="text-Left mb-4">Uang Masuk</h1>
                <div class="form-group">
                    <select class="form-control" id="sumber_dana" name="sumber_dana">
                        <option value="" style="font-style:italic;">-- Pilih sumber dana --</option>
                        <option value="PLN" <?php if ($sumber_dana == 'PLN') echo 'selected'; ?>>PLN</option>
                        <option value="Sinarmas" <?php if ($sumber_dana == 'Sinarmas') echo 'selected'; ?>>Sinarmas</option>
                    </select>
                </div>
                <br>
                <script>
                    document.getElementById('sumber_dana').addEventListener('change', function() {
                        const sumber_dana = this.value;
                        const currentHash = '#uang_masuk'; // Tetapkan hash untuk tab Uang Masuk
                        const currentURL = new URL(window.location.href);
                        currentURL.searchParams.set('sumber_dana', sumber_dana); // Tambahkan atau perbarui parameter sumber_dana
                        currentURL.hash = currentHash; // Pastikan hash tetap
                        window.location.href = currentURL.toString(); // Redirect ke URL baru
                    });
                </script>
                <a class="btn btn-info" href="pre_input.php"><i class="bi bi-plus-circle"></i></a>
                <span>&nbsp;</span>
                <a class="btn btn-danger" href="dashboard.php"><i class="bi bi-backspace"></i></a>
                <br><br>
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>TANGGAL</th>
                            <th>SUMBER DANA</th>
                            <th>NOMINAL TERIMA</th>
                            <th>KETERANGAN</th>

                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_um->num_rows > 0) {
                            while ($row = $result_um->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['tgl'] . "</td>";
                                echo "<td>" . $row['sumber_dana'] . "</td>";
                                echo "<td>" . number_format($row['nominal_terima'], 2, ',', '.') . "</td>";
                                echo "<td>" . $row['ket'] . "</td>";

                                echo "<td>" . $row['tanggal_jatuh_tempo'] . "</td>";

                                echo "<td class='text-center'><a class='btn btn-success' onclick=\"showPostRowInfo(" . htmlspecialchars($row['id']) . ")\"><i class=\"bi bi-info\"></i></a></td>";
                                $subtotal_total += $row['nominal_terima'];
                                $subtotal_os += $row['os'];
                                $subtotal_difference += ($row['nominal_terima'] - $row['os']);
                                $plafond += $row['plafond'];
                            }
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Subtotal</strong></td>";
                            echo "<td>" . number_format($subtotal_total, 2, ',', '.') . "</td>";
                            echo "<td>" . number_format($subtotal_os, 2, ',', '.') . "</td>";
                            echo "<td colspan='2'>" . number_format($subtotal_difference, 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Plafond Full</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format($plafond, 2, ',', '.') . "</td>";
                            echo "<tr class='table-secondary'>";
                            echo "<td colspan='4' class='text-end'><strong>Remaining Plafond</strong></td>";
                            echo "<td colspan='2' class='text-end'><strong></strong></td>";
                            echo "<td colspan='2'>" . number_format(($plafond - $subtotal_difference), 2, ',', '.') . "</td>";
                            echo "</tr>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Tidak ada data</td></tr>";
                        }
                        ?>
                    </tbody>
            </div>
        </div>
    </div>
</body>

</html>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showPreRowInfo(id) {
        // AJAX request to fetch data for the selected row
        fetch(`get_row_info_pre.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Pre-Financing Information',
                        width: '80%',
                        html: `
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Hutang:</strong> ${data.row.jenis_prepost}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tanggal Cair:</strong> ${data.row.tanggal_prepost}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tahapan:</strong> Tahap ${data.row.tahapan}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tanggal Jatuh Tempo:</strong> ${data.row.tanggal_jatuh_tempo}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Nominal:</strong> ${parseFloat(data.row.total).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Penutupan:</strong> ${parseFloat(data.row.os).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Sisa OS:</strong> ${(parseFloat(data.row.total) - parseFloat(data.row.os)).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Keterangan:</strong> ${data.row.ket}</p>

                            <p style="text-align: justify; font-size: 0.9em;"><strong>Penutupan:</strong></p>
                            <table class="table table-bordered table-striped table-hover" style="font-size: 0.8em;">
                                <thead class="table-dark">
                                    <tr class="text-center">
                                        <th>Post</th>
                                        <th>Tahapan Post</th>
                                        <th>Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.rowtutuppre.map(row => `
                                        <tr>
                                            <td>${row.jenis_post}</td>
                                            <td>${row.tahapan_post}</td>
                                            <td>${parseFloat(row.nominal).toLocaleString()}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        `,
                        icon: 'info'
                    });

                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch row information.',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while fetching data.',
                    icon: 'error'
                });
                console.error('Error fetching row data:', error);
            });
    }

    function showPostRowInfo(id) {
        // AJAX request to fetch data for the selected row
        fetch(`get_row_info_post.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Post-Financing Information',
                        width: '80%',
                        html: `
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Hutang:</strong> ${data.row.jenis_post}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tanggal Cair:</strong> ${data.row.tanggal_post}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tahapan:</strong> Tahap ${data.row.tahapan_post}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Tanggal Jatuh Tempo:</strong> ${data.row.tanggal_jatuh_tempo}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Nominal:</strong> ${parseFloat(data.row.released).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Penutupan:</strong> ${parseFloat(data.row.os).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Sisa OS:</strong> ${(parseFloat(data.row.released) - parseFloat(data.row.os)).toLocaleString()}</p>
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Keterangan:</strong> ${data.row.ket}</p>
                            
                            <p style="text-align: justify; font-size: 0.9em;"><strong>Penutupan:</strong></p>
                                    <table class="table table-bordered table-striped table-hover" style="font-size: 0.8em;">
                                        <thead class="table-dark">
                                            <tr class="text-center">
                                                <th>Tanggal</th>
                                                <th>sumber_dana</th>
                                                <th>Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.rowtutupost.map(row => `
                                                <tr>
                                                    <td>${row.tanggal}</td>
                                                    <td>${row.sumber_dana}</td>
                                                    <td>${parseFloat(row.nominal).toLocaleString()}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                `,
                        icon: 'info'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to fetch row information.',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while fetching data.',
                    icon: 'error'
                });
                console.error('Error fetching row data:', error);
            });
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Ambil hash dari URL
        const hash = window.location.hash;

        if (hash) {
            // Temukan elemen tab berdasarkan hash
            const activeTab = document.querySelector(`.nav-link[href="${hash}"]`);
            if (activeTab) {
                // Nonaktifkan tab aktif sebelumnya
                document.querySelectorAll(".nav-link.active").forEach(tab => {
                    tab.classList.remove("active");
                });
                document.querySelectorAll(".tab-pane.active").forEach(tab => {
                    tab.classList.remove("show", "active");
                });

                // Aktifkan tab baru
                activeTab.classList.add("active");
                const pane = document.querySelector(hash);
                if (pane) {
                    pane.classList.add("show", "active");
                }
            }
        }
    });

    function showEditModal(type, id) {
        // Fetch current data
        fetch(`get_row_info_${type}.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = data.row;
                    const penutupanData = type === 'pre' ? data.rowtutuppost : data.rowtutupre;
                    
                    // Create penutupan HTML
                    let penutupanHtml = '';
                    if (type === 'pre') {
                        penutupanHtml = `
                            <div class="penutupan-container">
                                ${penutupanData.map((item, index) => `
                                    <div class="row mb-2">
                                        <div class="col">
                                            <input type="text" class="form-control" name="penutupan[jenis_post][]" placeholder="Jenis Post" value="${item.jenis_post || ''}">
                                        </div>
                                        <div class="col">
                                            <input type="text" class="form-control" name="penutupan[tahapan_post][]" placeholder="Tahapan" value="${item.tahapan_post || ''}">
                                        </div>
                                        <div class="col">
                                            <input type="number" class="form-control penutupan-nominal" name="penutupan[nominal][]" placeholder="Nominal" value="${item.nominal || ''}" oninput="checkBalance()">
                                        </div>
                                    </div>
                                `).join('')}
                            </div>`;
                    } else {
                        penutupanHtml = `
                            <div class="penutupan-container">
                                ${penutupanData.map((item, index) => `
                                    <div class="row mb-2">
                                        <div class="col">
                                            Tahap : ${item.tahapan_pre || ''}
                                            <input type="hidden" class="form-control" name="penutupan[tahapan_pre][]" value="${item.tahapan_pre || ''}">
                                        </div>
                                        <div class="col">
                                            <input type="number" class="form-control penutupan-nominal" name="penutupan[nominal][]" placeholder="Nominal" value="${item.nominal || ''}" oninput="checkBalance()">
                                        </div>
                                    </div>
                                `).join('')}
                            </div>`;
                    }

                    Swal.fire({
                        title: 'Edit Data',
                        width: '800px',
                        html: `
                            <form id="editForm">
                                <input type="hidden" name="id" value="${id}">
                                <input type="hidden" name="type" value="${type}">
                                <input type="hidden" name="tahapan_post" value="${row.tahapan_post}">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nominal</label>
                                    <input type="number" class="form-control" name="nominal" value="${type === 'pre' ? row.total : row.released}" step="0.01" id="main-nominal" oninput="checkBalance()">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Penutupan</label>
                                    ${penutupanHtml}
                                    ${type === 'pre' ? `
                                    <button type="button" class="btn btn-sm btn-primary mt-2" onclick="addPenutupanRow('${type}')">
                                        Add Penutupan
                                    </button>
                                    ` : ''}
                                </div>
                                <div id="balance-status" class="mt-2"></div>
                            </form>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Save',
                        cancelButtonText: 'Cancel',
                        didOpen: () => {
                            // Initialize any necessary elements
                            checkBalance();
                        },
                        preConfirm: () => {
                            // Collect form data
                            const formData = new FormData(document.getElementById('editForm'));
                            
                            // Send update request
                            return fetch(`${type}_update.php`, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (!result.success) {
                                    throw new Error(result.message || 'Update failed');
                                }
                                return result;
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Success',
                                text: 'Data has been updated',
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        }
                    }).catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            });
    }

    function checkBalance() {
        const mainNominal = parseFloat(document.getElementById('main-nominal').value) || 0;
        const penutupanNominals = Array.from(document.querySelectorAll('.penutupan-nominal')).map(input => parseFloat(input.value) || 0);
        const penutupanTotal = penutupanNominals.reduce((sum, value) => sum + value, 0);

        const balanceStatus = document.getElementById('balance-status');
        const confirmButton = Swal.getConfirmButton();
        if (mainNominal === penutupanTotal) {
            balanceStatus.innerHTML = '<span class="text-success">Balanced</span>';
            confirmButton.disabled = false;
        } else {
            balanceStatus.innerHTML = '<span class="text-danger">Not Balanced</span>';
            confirmButton.disabled = true;
        }
    }

    function addPenutupanRow(type) {
        const container = document.querySelector('.penutupan-container');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2';
        
        if (type === 'pre') {
            newRow.innerHTML = `
                <div class="col">
                    <input type="text" class="form-control" name="penutupan[jenis_post][]" placeholder="Jenis Post">
                </div>
                <div class="col">
                    <input type="text" class="form-control" name="penutupan[tahapan_post][]" placeholder="Tahapan">
                </div>
                <div class="col">
                    <input type="number" class="form-control penutupan-nominal" name="penutupan[nominal][]" placeholder="Nominal" oninput="checkBalance()">
                </div>
            `;
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger col-auto';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = function() {
            newRow.remove();
            checkBalance();
        };
        newRow.appendChild(removeBtn);
        
        container.appendChild(newRow);
    }
</script>

<?php
// Tutup koneksi
$conn->close();
?>