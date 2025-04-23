<?php
// user_report.php
include("../config.php");

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Role Distribution Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container, .chart-container {
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
            padding: 20px;
            margin-bottom: 20px;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
        .table thead {
            background-color: #343a40;
            color: white;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .chart-wrapper {
            height: 400px;
            position: relative;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                background-color: white;
            }
            .table-container, .chart-container {
                box-shadow: none;
                padding: 0;
            }
            .chart-container {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="report-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-users me-2"></i>User Role Distribution Report</h2>
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

        <div class="chart-container">
            <h4 class="mb-4"><i class="fas fa-chart-pie me-2"></i>User Distribution Chart</h4>
            <div class="chart-wrapper">
                <canvas id="userChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <?php
            $query = "SELECT 
                        utype as user_type, 
                        COUNT(*) as total_users,
                        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM user), 1) as percentage
                      FROM user
                      GROUP BY utype
                      ORDER BY total_users DESC";
            $result = mysqli_query($con, $query);
            ?>
            <h4 class="mb-4"><i class="fas fa-table me-2"></i>User Role Statistics</h4>
            <table id="userTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>User Type</th>
                        <th>Total Users</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    $chartLabels = [];
                    $chartData = [];
                    $chartColors = [];
                    $colorPalette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];
                    
                    while($row = mysqli_fetch_assoc($result)) { 
                        $grandTotal += $row['total_users'];
                        $chartLabels[] = $row['user_type'];
                        $chartData[] = $row['total_users'];
                        $chartColors[] = $colorPalette[count($chartLabels) % count($colorPalette)];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(ucwords($row['user_type'])); ?></td>
                        <td><?php echo number_format($row['total_users']); ?></td>
                        <td><?php echo $row['percentage']; ?>%</td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>Grand Total</strong></td>
                        <td><strong><?php echo number_format($grandTotal); ?></strong></td>
                        <td><strong>100%</strong></td>
                    </tr>
                </tfoot>
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
        $(document).ready(function() {
            // Initialize DataTable
            $('#userTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'print'
                ],
                order: [[1, 'desc']]
            });

            // Initialize Chart
            const ctx = document.getElementById('userChart').getContext('2d');
            const userChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: <?php echo json_encode($chartColors); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // PDF Export Functionality
            document.getElementById('exportPdf').addEventListener('click', function() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                // Report title
                doc.setFontSize(18);
                doc.text('User Role Distribution Report', 14, 15);
                doc.setFontSize(11);
                doc.setTextColor(100);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 22);
                
                // Add chart image (canvas)
                const canvas = document.getElementById('userChart');
                const chartImage = canvas.toDataURL('image/png');
                doc.addImage(chartImage, 'PNG', 15, 30, 180, 100);
                
                // Table data
                const tableData = [];
                const headers = [['User Type', 'Total Users', 'Percentage']];
                
                $('#userTable tbody tr').each(function() {
                    const row = [];
                    $(this).find('td').each(function() {
                        row.push($(this).text());
                    });
                    tableData.push(row);
                });
                
                // Add footer row
                const footerRow = [
                    $('#userTable tfoot tr td:first-child').text(),
                    $('#userTable tfoot tr td:nth-child(2)').text(),
                    $('#userTable tfoot tr td:last-child').text()
                ];
                
                // Generate table
                doc.autoTable({
                    head: headers,
                    body: tableData,
                    foot: [footerRow],
                    startY: 140,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [52, 58, 64]
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    footStyles: {
                        fontStyle: 'bold',
                        fillColor: [248, 249, 250]
                    }
                });
                
                // Save the PDF
                doc.save('User_Role_Report_' + new Date().toISOString().slice(0, 10) + '.pdf');
            });
            
            // Print Functionality
            document.getElementById('printReport').addEventListener('click', function() {
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