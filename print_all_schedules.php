<?php
require_once 'config.php';
require_once 'function.php';
require_once 'vendor/autoload.php'; // Adjust path if using Composer, or use 'tcpdf/tcpdf.php' for manual install

// Enable error logging for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch all professors
$professors = getAllProfessors();
if (empty($professors)) {
    die("No professors found.");
}

// Initialize TCPDF
$pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->SetCreator('Class Scheduling System');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('All Professors Schedules');
$pdf->SetMargins(0, 12.7, 0); // Left = 0, Top = 25.4mm, Right = 0
$pdf->SetAutoPageBreak(false);
$pdf->SetFont('helvetica', '', 8); // Base font size

// Days and time slots
$days       = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$time_slots = array_map(function ($h) {
    return sprintf("%02d:00:00", $h);
}, range(7, 21));

// Pastel colors
$pastel_colors = [
    '#9FB3DF',
    '#C599B6',
    '#FADA7A',
    '#D8BFD8',
    '#C8AAAA',
    '#F39E60',
    '#E16A54',
    '#55AD9B',
    '#A5DD9B',
    '#BCE29E',
];

function formatTimeTo12Hour($time)
{
    return date('h:i A', strtotime($time));
}

// Table dimensions (in mm, full Letter size: 279.4mm x 215.9mm)
$width            = 279.4;
$height           = 215.9;
$cell_width       = $width / 7;
$base_cell_height = 10; // Minimum cell height
$row_heights      = array_fill(0, count($time_slots), $base_cell_height);

$has_schedules = false;

foreach ($professors as $professor) {
    $professor_id   = $professor['professor_id'];
    $professor_name = htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']);

    // Fetch professor's schedule
    $schedules = [];
    $sql       = "SELECT s.*, sub.subject_code, sub.title, sec.section_code 
            FROM Schedule s 
            JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id 
            JOIN Subject sub ON ta.subject_id = sub.subject_id 
            JOIN Section sec ON ta.section_id = sec.section_id 
            WHERE ta.professor_id = ?";
    $stmt      = $conn->prepare($sql);
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();

    // Skip if no schedules
    if (empty($schedules)) {
        continue;
    }

    $has_schedules = true;

    // Build complete timetable grid
    $timetable       = array_fill(0, count($time_slots), array_fill_keys($days, null));
    $color_index     = 0;
    $schedule_colors = [];

    foreach ($schedules as $schedule) {
        $start        = strtotime($schedule['start_time']);
        $end          = strtotime($schedule['end_time']);
        $day          = $schedule['DAY'];
        $schedule_key = $schedule['assignment_id'];

        if (!isset($schedule_colors[$schedule_key])) {
            $schedule_colors[$schedule_key] = $pastel_colors[$color_index % count($pastel_colors)];
            $color_index++;
        }
        $color = $schedule_colors[$schedule_key];

        $entry = htmlspecialchars($schedule['subject_code']) . " - " .
            htmlspecialchars($schedule['title']) . " (" .
            htmlspecialchars($schedule['section_code']) . ")\n" .
            htmlspecialchars($schedule['room']) . "\n" .
            date('h:i A', $start) . " - " . date('h:i A', $end);

        $start_index = array_search(date('H:i:00', $start), $time_slots);
        $end_index   = array_search(date('H:i:00', $end), $time_slots);
        $rowspan     = $end_index - $start_index;

        if ($start_index !== false && $end_index !== false && $rowspan > 0) {
            error_log("Schedule for $professor_name: $day, $start_index to $end_index, Rowspan: $rowspan");
            $timetable[$start_index][$day] = [
                'content' => $entry,
                'rowspan' => $rowspan,
                'color' => $color,
            ];
            for ($i = $start_index + 1; $i < $end_index; $i++) {
                $timetable[$i][$day] = ['spanned' => true];
            }
        }
    }

    // Add a new page
    $pdf->AddPage();

    // Header: Professor Name (start at top margin)
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetXY(0, 12.7); // Start at y = 25.4mm (top margin)
    $pdf->Cell($width, $base_cell_height, "Professor $professor_name Schedule", 1, 1, 'C', true);

    // Header: Days (adjusted to start below the professor name)
    $pdf->SetXY(0, 12.7 + $base_cell_height); // Move down by base_cell_height
    $pdf->Cell($cell_width, $base_cell_height, 'Time', 1, 0, 'C', true);
    foreach ($days as $day) {
        $pdf->Cell($cell_width, $base_cell_height, $day, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table Body (adjusted to start after headers)
    for ($i = 0; $i < count($time_slots); $i++) {
        $time = $time_slots[$i];
        $y    = 12.7 + $base_cell_height * 2 + array_sum(array_slice($row_heights, 0, $i)); // Start at top margin + 2 headers
        $pdf->SetXY(0, $y);

        // Calculate maximum height needed for this row
        $row_height = $base_cell_height;
        foreach ($days as $day) {
            if (isset($timetable[$i][$day]) && !isset($timetable[$i][$day]['spanned'])) {
                $entry = $timetable[$i][$day];
                if ($entry['rowspan'] == 1) {
                    $pdf->SetFontSize(8);
                    $text_height = $pdf->getStringHeight($cell_width - 2, $entry['content'], false, true, '', 1);
                    $row_height  = max($row_height, $text_height + 2); // Add padding
                }
            }
        }
        $row_heights[$i] = $row_height; // Store row height

        // Time column
        $pdf->Cell($cell_width, $row_height, formatTimeTo12Hour($time), 1, 0, 'C');

        // Schedule columns
        $x = $cell_width;
        foreach ($days as $day) {
            $pdf->SetXY($x, $y);
            if (isset($timetable[$i][$day])) {
                $entry = $timetable[$i][$day];
                if (isset($entry['spanned'])) {
                    $x += $cell_width;
                    continue;
                }

                $hex = str_replace('#', '', $entry['color']);
                $r   = hexdec(substr($hex, 0, 2));
                $g   = hexdec(substr($hex, 2, 2));
                $b   = hexdec(substr($hex, 4, 2));
                $pdf->SetFillColor($r, $g, $b);

                if ($entry['rowspan'] > 1) {
                    // Calculate total height of merged cells
                    $total_height = array_sum(array_slice($row_heights, $i, $entry['rowspan']));

                    // Draw the cell background first
                    $pdf->Rect($x, $y, $cell_width, $total_height, 'F');

                    // Calculate text height and position
                    $text_height = $pdf->getStringHeight($cell_width - 2, $entry['content'], false, true, '', 1);
                    $text_y      = $y + ($total_height - $text_height) / 2; // Center vertically

                    // Draw border and text separately
                    $pdf->Rect($x, $y, $cell_width, $total_height, 'D');
                    $pdf->SetXY($x, $text_y);
                    $pdf->MultiCell(
                        $cell_width,
                        $text_height,
                        $entry['content'],
                        0, // No border (already drawn)
                        'C', // Center horizontally
                        false, // No fill (already drawn)
                        0,
                        $x,
                        $text_y,
                        true,
                        0,
                        false,
                        true,
                        $text_height,
                        'T' // Top alignment since we calculated the position
                    );
                } else {
                    // Single cell: Use calculated row height
                    $pdf->SetFontSize(8);
                    $pdf->MultiCell(
                        $cell_width,
                        $row_height,
                        $entry['content'],
                        1,
                        'C',
                        true,
                        0,
                        '',
                        '',
                        true,
                        0,
                        false,
                        true,
                        $row_height,
                        'M',
                    );
                }
            } else {
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Cell($cell_width, $row_height, '-', 1, 0, 'C', true);
            }
            $x += $cell_width;
        }
    }
}

if (!$has_schedules) {
    $pdf->AddPage();
    $pdf->SetXY(0, 12.7); // Respect top margin here too
    $pdf->Cell($width, $height - 25.4, 'No schedules found for any professor.', 1, 1, 'C');
}

// Output PDF
$pdf->Output('all_professors_schedules.pdf', 'I');
exit;
?>