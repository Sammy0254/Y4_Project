<?php
// agent_performance_report.php
include("../config.php");

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Performance Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .report-header {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
            padding: 20px;
        }

        .action-buttons {
            margin-bottom: 20px;
        }

        .table thead {
            background-color: #343a40;
            color: white;
        }

        .badge-active {
            background-color: #28a745;
        }

        .badge-inactive {
            background-color: #6c757d;
        }

        .activity-high {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .activity-medium {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .activity-low {
            background-color: rgba(220, 53, 69, 0.1);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                background-color: white;
            }

            .table-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-chart-line me-2"></i>Agent Performance Report</h2>
                <div class="action-buttons no-print">
                    <button id="exportPdf" class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-2"></i>Export to PDF
                    </button>
                    <button id="printReport" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
            <p class="text-muted">Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
        </div>

        <div class="table-container">
            <?php
            $query = "SELECT 
            u.uid,
            u.uname,
            u.uemail,
            u.uphone,
            u.utype,
            COUNT(DISTINCT p.pid) AS total_listings,
            MAX(p.date) AS last_listing_date,
            COUNT(i.id) AS total_inquiries
          FROM user u
          LEFT JOIN property p ON p.uid = u.uid
          LEFT JOIN inquiries i ON i.property_id = p.pid
          WHERE u.utype IN ('agent', 'builder')
          GROUP BY u.uid
          ORDER BY total_listings DESC";
            $result = mysqli_query($con, $query);
            ?>
            <table id="agentTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Agent/Builder</th>
                        <th>Type</th>
                        <th>Total Listings</th>
                        <th>Total Inquiries</th>
                        <th>Last Listing</th>
                        <th>Activity Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) {
                        $lastListingDate = !empty($row['last_listing_date']) ? date('M j, Y', strtotime($row['last_listing_date'])) : 'Never';
                        $daysSinceLastListing = !empty($row['last_listing_date']) ? (time() - strtotime($row['last_listing_date'])) / (60 * 60 * 24) : 999;

                        // Determine activity level
                        $activityClass = '';
                        $activityText = '';

                        if ($row['total_listings'] > 10) {
                            $activityClass = 'activity-high';
                            $activityText = 'High';
                        } elseif ($row['total_listings'] > 3) {
                            $activityClass = 'activity-medium';
                            $activityText = 'Medium';
                        } else {
                            $activityClass = 'activity-low';
                            $activityText = 'Low';
                        }

                        // Mark as inactive if no listings in last 90 days
                        if ($daysSinceLastListing > 90 && $row['total_listings'] > 0) {
                            $activityClass = 'activity-low';
                            $activityText = 'Inactive';
                        }
                        ?>
                        <tr class="<?php echo $activityClass; ?>">
                            <td>
                                <?php echo htmlspecialchars($row['uname']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['uemail']); ?></small>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($row['utype'])); ?></td>
                            <td><?php echo number_format($row['total_listings']); ?></td>
                            <td><?php echo number_format($row['total_inquiries']); ?></td>
                            <td><?php echo $lastListingDate; ?></td>
                            <td>
                                <span class="badge <?php
                                echo $activityText === 'High' ? 'badge-active' :
                                    ($activityText === 'Medium' ? 'badge-warning' : 'badge-inactive');
                                ?>">
                                    <?php echo $activityText; ?>
                                </span>
                                <?php if ($daysSinceLastListing > 90 && $row['total_listings'] > 0) { ?>
                                    <br><small class="text-muted"><?php echo floor($daysSinceLastListing); ?> days ago</small>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- PDF Export Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize DataTable with export buttons
            $('#agentTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'print'
                ],
                order: [[2, 'desc']],
                pageLength: 25
            });

            // PDF Export Functionality
            document.getElementById('exportPdf').addEventListener('click', function () {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('landscape');

                // Report title
                doc.setFontSize(18);
                doc.text('Agent Performance Report', 14, 15);
                doc.setFontSize(11);
                doc.setTextColor(100);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 22);

                // Table data
                const tableData = [];
                const headers = [
                    ['Agent/Builder', 'Type', 'Listings', 'Inquiries', 'Last Listing', 'Activity']
                ];

                $('#agentTable tbody tr').each(function () {
                    const row = [];
                    $(this).find('td').each(function (index) {
                        // Skip the email in the first column
                        if (index === 0) {
                            row.push($(this).contents().first().text().trim());
                        }
                        // For activity column, get just the badge text
                        else if (index === 5) {
                            row.push($(this).find('.badge').text());
                        }
                        else {
                            row.push($(this).text().trim());
                        }
                    });
                    tableData.push(row);
                });

                // Generate table
                doc.autoTable({
                    head: headers,
                    body: tableData,
                    startY: 30,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [52, 58, 64]
                    },
                    columnStyles: {
                        0: { cellWidth: 40 },
                        1: { cellWidth: 20 },
                        2: { cellWidth: 20 },
                        3: { cellWidth: 20 },
                        4: { cellWidth: 25 },
                        5: { cellWidth: 20 }
                    },
                    styles: {
                        fontSize: 8,
                        cellPadding: 3
                    }
                });

                // Save the PDF
                doc.save('Agent_Performance_Report_' + new Date().toISOString().slice(0, 10) + '.pdf');
            });

            // Print Functionality
            document.getElementById('printReport').addEventListener('click', function () {
                window.print();
            });
        });
    </script>
</body>

</html>
<?php
// End output buffering
ob_end_flush();
?>