<?php
// pages/import_training.php
require_once __DIR__ . '/../includes/config.php';
$pdo = getDB();

if (!isAdmin()) {
    echo "<div style='padding:1rem; background:#fee2e2; color:#991b1b; border-radius:8px;'>Access Denied.</div>";
    return;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], "r");
        
        $courseIdx = $participantIdx = $staffIdIdx = $dateIdx = -1;
        $headerFound = false;

        // 1. INTELLIGENT HEADER SCANNER
        for ($i = 0; $i < 15; $i++) {
            $row = fgetcsv($handle);
            if ($row === FALSE) break;

            foreach ($row as $index => $col) {
                $cleanCol = strtoupper(trim($col));
                if (strpos($cleanCol, 'NAME OF COURSE') !== false || $cleanCol === 'COURSE') $courseIdx = $index;
                if ($cleanCol === 'PARTICIPANT' || $cleanCol === 'NAMA' || $cleanCol === 'NAME') $participantIdx = $index;
                if ($cleanCol === 'ID STAFF' || $cleanCol === 'ID PETUGAS' || strpos($cleanCol, 'STAFF NO') !== false) $staffIdIdx = $index;
            }
            if ($courseIdx !== -1 && $participantIdx !== -1) {
                $headerFound = true;
                break;
            }
        }

        if ($headerFound) {
            $successCount = 0;
            $failCount = 0;
            $skipCount = 0;

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (empty(array_filter($row))) continue;

                $courseTitle = isset($row[$courseIdx]) ? trim($row[$courseIdx]) : '';
                $participantName = isset($row[$participantIdx]) ? trim($row[$participantIdx]) : '';
                $staffNo = ($staffIdIdx !== -1 && isset($row[$staffIdIdx])) ? trim($row[$staffIdIdx]) : '';

                if (empty($courseTitle) || empty($participantName)) {
                    $skipCount++;
                    continue;
                }

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("SELECT id FROM training_courses WHERE title = ?");
                    $stmt->execute([$courseTitle]);
                    $courseId = $stmt->fetchColumn();

                    if (!$courseId) {
                        $tempCode = 'CRS-' . strtoupper(substr(md5($courseTitle), 0, 5));
                        $insertCourse = $pdo->prepare("INSERT INTO training_courses (code, title, training_date) VALUES (?, ?, NULL)");
                        $insertCourse->execute([$tempCode, $courseTitle]);
                        $courseId = $pdo->lastInsertId();
                    }

                    $staffId = null;
                    if (!empty($staffNo)) {
                        $stmt = $pdo->prepare("SELECT id FROM staff WHERE staff_no = ?");
                        $stmt->execute([$staffNo]);
                        $staffId = $stmt->fetchColumn();
                    }
                    if (!$staffId) {
                        $stmt = $pdo->prepare("SELECT id FROM staff WHERE name LIKE ? LIMIT 1");
                        $stmt->execute(["%" . $participantName . "%"]);
                        $staffId = $stmt->fetchColumn();
                    }

                    if ($staffId) {
                        $checkStmt = $pdo->prepare("SELECT id FROM training_attendances WHERE staff_id = ? AND course_id = ?");
                        $checkStmt->execute([$staffId, $courseId]);
                        
                        if (!$checkStmt->fetch()) {
                            $insertAtt = $pdo->prepare("INSERT INTO training_attendances (staff_id, course_id, status) VALUES (?, ?, 'Completed')");
                            $insertAtt->execute([$staffId, $courseId]);
                            $successCount++;
                        } else {
                            $skipCount++;
                        }
                    } else {
                        $failCount++; 
                    }

                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $failCount++;
                }
            }

            $message = "
            <div style='padding:1.5rem; margin-bottom:1.5rem; border-radius:8px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;'>
                <h4 style='margin-top:0;'>Import Summary</h4>
                <ul style='margin-bottom:0; font-size:1.1rem; list-style-type:none; padding-left:0;'>
                    <li>✅ <strong>$successCount</strong> records successfully added.</li>
                    <li style='color:#ca8a04;'>⏭️ <strong>$skipCount</strong> records skipped (Empty rows or already exist).</li>
                    <li style='color:#dc2626;'>❌ <strong>$failCount</strong> records failed (Staff name/ID not found in the Staff Registry).</li>
                </ul>
            </div>";

        } else {
            $message = "
            <div style='padding:1.5rem; margin-bottom:1.5rem; border-radius:8px; background:#fee2e2; color:#991b1b; border:1px solid #fecaca;'>
                <h4 style='margin-top:0;'>Error: Header Row Not Found</h4>
                <p>The system scanned the first 15 rows of your CSV but could not find the required columns. Ensure your file has columns containing the words <strong>NAME OF COURSE</strong> and <strong>PARTICIPANT</strong>.</p>
            </div>";
        }
        fclose($handle);
    }
}
?>

<div class="page-header">
    <div>
        <h2>Bulk Import Training Records</h2>
        <p class="page-subtitle">Upload your external and internal training CSV files.</p>
    </div>
</div>

<?= $message ?>

<div class="card" style="padding: 2rem; max-width: 600px;">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group form-full">
            <label style="font-weight:bold; margin-bottom:.5rem; display:block;">Select CSV File *</label>
            <input type="file" name="csv_file" accept=".csv" required style="padding: 0.75rem; border: 2px dashed #cbd5e1; border-radius: 8px; width: 100%; cursor:pointer; background:#f8fafc;">
            <small style="color:#64748b; margin-top:0.75rem; display:block;">
                Note: The system will automatically ignore empty spaces and duplicate records. If a staff member is not found in your Staff Registry, their training record will be skipped.
            </small>
        </div>
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size:1rem;">Upload & Process File</button>
        </div>
    </form>
</div>