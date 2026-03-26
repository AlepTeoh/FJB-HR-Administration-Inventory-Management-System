<?php
// pages/report.php - Staff Registry Report (Admin HR + Admin IT)
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

// ── Aggregate stats ────────────────────────────────────────────────────────
$totalStaff   = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
$totalDepts   = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$fjbCount     = $pdo->query("SELECT COUNT(*) FROM staff WHERE company='FJB'")->fetchColumn();
$fbsbCount    = $pdo->query("SELECT COUNT(*) FROM staff WHERE company='FBSB'")->fetchColumn();
$totalFamilyMembers = $pdo->query("SELECT COUNT(*) FROM family_members")->fetchColumn();

// ── Headcount by department ────────────────────────────────────────────────
$deptRows = $pdo->query("
    SELECT d.name AS dept, d.company, COUNT(s.id) AS headcount
    FROM departments d
    LEFT JOIN staff s ON s.department_id = d.id
    GROUP BY d.id, d.name, d.company
    ORDER BY d.company, headcount DESC
")->fetchAll();

// ── Family members by relationship ────────────────────────────────────────
$relRows = $pdo->query("
    SELECT relationship, COUNT(*) AS total
    FROM family_members
    GROUP BY relationship
    ORDER BY total DESC
")->fetchAll();

// ── Staff with no family records ───────────────────────────────────────────
$noFamilyCount = $pdo->query("
    SELECT COUNT(*) FROM staff s
    WHERE NOT EXISTS (
        SELECT 1 FROM family_members f WHERE f.employee_name = s.name
    )
")->fetchColumn();

// ── Department with most family members ───────────────────────────────────
$topFamilyDept = $pdo->query("
    SELECT f.department, COUNT(*) AS total
    FROM family_members f
    GROUP BY f.department
    ORDER BY total DESC
    LIMIT 1
")->fetch();
?>

<div class="page-header">
    <div>
        <h2>Staff Registry Report</h2>
        <p class="page-subtitle">Overview of staff and family information across departments</p>
    </div>
    <button class="btn btn-outline" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print Report
    </button>
</div>

<!-- ── Summary Cards ──────────────────────────────────────────────── -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(56,189,248,.12);color:#0284c7;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalStaff ?></div>
            <div class="stat-label">Total Staff</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(22,163,74,.1);color:#16a34a;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalDepts ?></div>
            <div class="stat-label">Departments</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(217,119,6,.1);color:#d97706;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalFamilyMembers ?></div>
            <div class="stat-label">Family Members</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(220,38,38,.1);color:#dc2626;">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= $noFamilyCount ?></div>
            <div class="stat-label">Staff Without Family Records</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">

    <!-- ── Headcount by Company ──────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Headcount by Company</h3>
        </div>
        <div style="padding:1.25rem;">
            <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                <div style="flex:1;background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:8px;padding:1rem;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:#0284c7;"><?= $fjbCount ?></div>
                    <div style="font-size:.8rem;color:var(--muted);font-weight:600;margin-top:.25rem;">FJB</div>
                </div>
                <div style="flex:1;background:rgba(22,163,74,.07);border:1px solid rgba(22,163,74,.2);border-radius:8px;padding:1rem;text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:#16a34a;"><?= $fbsbCount ?></div>
                    <div style="font-size:.8rem;color:var(--muted);font-weight:600;margin-top:.25rem;">FBSB</div>
                </div>
            </div>
            <!-- Simple bar chart -->
            <?php if ($totalStaff > 0): $fjbPct = round($fjbCount / $totalStaff * 100); $fbsbPct = 100 - $fjbPct; ?>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--muted);margin-bottom:.35rem;">
                    <span>FJB <?= $fjbPct ?>%</span>
                    <span>FBSB <?= $fbsbPct ?>%</span>
                </div>
                <div style="height:10px;border-radius:99px;background:var(--border);overflow:hidden;display:flex;">
                    <div style="width:<?= $fjbPct ?>%;background:#0284c7;"></div>
                    <div style="flex:1;background:#16a34a;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Family Members by Relationship ───────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Family Members by Relationship</h3>
        </div>
        <div style="padding:1.25rem;">
            <?php
            $relColors = ['Spouse'=>'#0284c7','Child'=>'#16a34a','Parent'=>'#d97706','Sibling'=>'#7c3aed'];
            $maxRel = max(array_column($relRows, 'total') ?: [1]);
            foreach ($relRows as $r):
                $color = $relColors[$r['relationship']] ?? '#64748b';
                $pct = round($r['total'] / $maxRel * 100);
            ?>
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
                <div style="width:80px;font-size:.8rem;font-weight:600;color:var(--text);"><?= htmlspecialchars($r['relationship']) ?></div>
                <div style="flex:1;height:20px;background:var(--border);border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:4px;transition:width .5s;"></div>
                </div>
                <div style="width:28px;text-align:right;font-size:.8rem;font-weight:700;color:<?= $color ?>;"><?= $r['total'] ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($relRows)): ?>
            <p style="color:var(--muted);font-size:.875rem;text-align:center;padding:1rem 0;">No family records found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Headcount by Department table ────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Headcount by Department</h3>
        <span style="font-size:.8rem;color:var(--muted);"><?= count($deptRows) ?> departments</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Department</th>
                <th>Company</th>
                <th>Headcount</th>
                <th style="width:40%">Distribution</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $maxDept = max(array_column($deptRows, 'headcount') ?: [1]);
            foreach ($deptRows as $row):
                $barPct = $maxDept > 0 ? round($row['headcount'] / $maxDept * 100) : 0;
                $barColor = $row['company'] === 'FJB' ? '#0284c7' : '#16a34a';
            ?>
            <tr>
                <td><span class="fw-600"><?= htmlspecialchars($row['dept']) ?></span></td>
                <td>
                    <span class="badge" style="background:<?= $row['company']==='FJB' ? 'rgba(2,132,199,.12)' : 'rgba(22,163,74,.1)' ?>;color:<?= $barColor ?>;padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600;">
                        <?= htmlspecialchars($row['company']) ?>
                    </span>
                </td>
                <td><strong><?= $row['headcount'] ?></strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <div style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden;">
                            <div style="height:100%;width:<?= $barPct ?>%;background:<?= $barColor ?>;border-radius:4px;"></div>
                        </div>
                        <span style="font-size:.75rem;color:var(--muted);width:32px;"><?= $barPct ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($deptRows)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:2rem;">No department data found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
