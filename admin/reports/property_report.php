<?php
// property_report.php
include("../config.php");

// Start output buffering to capture content for PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Report by City</title>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container {
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
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
                <h2><i class="fas fa-city me-2"></i>Property Report by City</h2>
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
            $query = "SELECT city, COUNT(*) as total_properties FROM property GROUP BY city";
            $result = mysqli_query($con, $query);
            ?>
            <table id="propertyTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>Total Properties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    while($row = mysqli_fetch_assoc($result)) { 
                        $grandTotal += $row['total_properties'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['city']); ?></td>
                        <td><?php echo number_format($row['total_properties']); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>Grand Total</strong></td>
                        <td><strong><?php echo number_format($grandTotal); ?></strong></td>
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
            $('#propertyTable').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: []
            });

            // PDF Export Functionality
            document.getElementById('exportPdf').addEventListener('click', function() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                
                // Report title
                doc.setFontSize(18);
                doc.text('Property Report by City', 14, 15);
                doc.setFontSize(11);
                doc.setTextColor(100);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 22);
                
                // Table data
                const tableData = [];
                const headers = [['City', 'Total Properties']];
                
                $('#propertyTable tbody tr').each(function() {
                    const row = [];
                    $(this).find('td').each(function() {
                        row.push($(this).text());
                    });
                    tableData.push(row);
                });
                
                // Add footer row
                const footerRow = [
                    $('#propertyTable tfoot tr td:first-child').text(),
                    $('#propertyTable tfoot tr td:last-child').text()
                ];
                
                // Generate table
                doc.autoTable({
                    head: headers,
                    body: tableData,
                    foot: [footerRow],
                    startY: 30,
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
                doc.save('Property_Report_' + new Date().toISOString().slice(0, 10) + '.pdf');
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
// End output buffering and clean (in case we need to use the buffer later)
ob_end_flush();
?>