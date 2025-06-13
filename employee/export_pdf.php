<?php
session_start();
include '../config/db.php';

require_once('../vendor/tcpdf/tcpdf.php');  

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    die("Unauthorized");
}

$employee_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT r.id, i.item_name, r.quantity AS requested_quantity, r.approved_quantity, r.status, r.request_date
                        FROM requests r
                        JOIN inventory i ON r.item_id = i.id
                        WHERE r.employee_id = ?
                        ORDER BY r.request_date DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

// Extend TCPDF to add header/footer
class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        // Logo (adjust path and size)
        $image_file = '../assets/logo.png'; 
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 25, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        // Title
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'Stationery Request History', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        $this->SetLineWidth(0.3);
        $this->Line(15, 28, $this->getPageWidth() - 15, 28); // horizontal line below header
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $pageNumTxt = 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pageNumTxt, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company');
$pdf->SetTitle('Stationery Request History');
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(15);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();

// Table header with styling
$tbl_header = '
<table border="1" cellpadding="4" style="border-collapse:collapse;">
    <thead>
        <tr style="background-color:#d3d3d3; font-weight:bold;">
            <th width="5%" align="center">ID</th>
            <th width="35%" align="center">Item</th>
            <th width="15%" align="center">Requested Qty</th>
            <th width="15%" align="center">Approved Qty</th>
            <th width="15%" align="center">Status</th>
            <th width="15%" align="center">Request Date</th>
        </tr>
    </thead>
    <tbody>
';

$tbl_footer = '</tbody></table>';
$tbl = '';

function formatStatusText($status) {
    return ucfirst($status);
}

while ($row = $result->fetch_assoc()) {
    $approvedQty = is_null($row['approved_quantity']) ? '-' : $row['approved_quantity'];
    $tbl .= '<tr>
                <td align="center">'. $row['id'] .'</td>
                <td>'. htmlspecialchars($row['item_name']) .'</td>
                <td align="center">'. $row['requested_quantity'] .'</td>
                <td align="center">'. $approvedQty .'</td>
                <td align="center">'. formatStatusText($row['status']) .'</td>
                <td align="center">'. $row['request_date'] .'</td>
             </tr>';
}

$pdf->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false, false, '');

$pdf->Output('request_history.pdf', 'D'); // force download
exit;
