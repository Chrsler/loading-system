<?php
include_once 'config.php';
include_once 'function.php';

// Get the professor ID from the URL
$professor_id = isset($_GET['professor_id']) ? sanitize($_GET['professor_id']) : '';
if (!$professor_id) {
    die("No professor selected.");
}

// Fetch professor details
$professor = getRecordById('Professor', 'professor_id', $professor_id);
if (!$professor) {
    die("Professor not found.");
}
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

// Build timetable data with colors and rowspan
$days       = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$time_slots = array_map(function ($h) {
    return sprintf("%02d:00:00", $h);
}, range(7, 21));

// Define pastel colors (same as fetch_timetable.php)
$pastel_colors   = [
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
$color_index     = 0;
$schedule_colors = [];

$timetable     = [];
$spanned_cells = []; // To track merged cells

foreach ($schedules as $schedule) {
    $start        = strtotime($schedule['start_time']);
    $end          = strtotime($schedule['end_time']);
    $day          = $schedule['DAY'];
    $schedule_key = $schedule['assignment_id'];

    // Assign color to schedule
    if (!isset($schedule_colors[$schedule_key])) {
        $schedule_colors[$schedule_key] = $pastel_colors[$color_index % count($pastel_colors)];
        $color_index++;
    }
    $color = $schedule_colors[$schedule_key];

    $entry = htmlspecialchars($schedule['subject_code']) . " - " .
        htmlspecialchars($schedule['title']) . " (" .
        htmlspecialchars($schedule['section_code']) . ")<br>" .
        htmlspecialchars($schedule['room']) . "<br>" .
        date('h:i A', $start) . " - " . date('h:i A', $end);

    $start_index = array_search(date('H:i:00', $start), $time_slots);
    $end_index   = array_search(date('H:i:00', $end), $time_slots);
    $rowspan     = $end_index - $start_index;

    if ($start_index !== false && $end_index !== false && $rowspan > 0) {
        // Place the entry only at the starting time slot with rowspan
        $timetable[$time_slots[$start_index]][$day] = [
            'content' => $entry,
            'rowspan' => $rowspan,
            'color' => $color,
            'processed' => false,
        ];
        // Mark subsequent slots as spanned
        for ($i = $start_index + 1; $i < $end_index; $i++) {
            $spanned_cells[$time_slots[$i]][$day] = true;
        }
    }
}

function formatTimeTo12Hour($time)
{
    return date('h:i A', strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Professor <?php echo $professor_name; ?> Schedule</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
        @page {
            size: 8.5in 11in landscape;
            /* Short paper size in landscape */
            margin: 0.25in;
            /* Minimal margins to maximize space */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            height: 100%;
            padding: 0.25in;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            height: 100%;
            /* Fill the page height */
            border-collapse: collapse;
            table-layout: fixed;
            /* Ensures columns are evenly distributed */
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            font-size: 8pt;
            /* Reduced font size to fit content */
            overflow: hidden;
            /* Prevent overflow */
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .time-column {
            width: 10%;
            /* Allocate fixed width for time column */
        }

        .day-column {
            width: 15%;
            /* Equal width for day columns (90% / 6 days = 15%) */
        }

        .schedule-cell {
            line-height: 1.1;
        }

        @media print {
            .container {
                width: 100%;
                max-width: none;
                height: 100vh;
                /* Full viewport height for printing */
            }

            table {
                width: 100%;
                height: 100%;
            }
        }
        </style>
    </head>

    <body>
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <th colspan="7" style="font-size: 12pt; padding: 8px;">
                            Professor <?php echo $professor_name; ?> Schedule
                        </th>
                    </tr>
                    <tr>
                        <th class="time-column">Time</th>
                        <?php foreach ($days as $day): ?>
                        <th class="day-column"><?php echo $day; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $time): ?>
                    <tr>
                        <td class="time-column"><?php echo formatTimeTo12Hour($time); ?></td>
                        <?php foreach ($days as $day): ?>
                        <?php
                                if (isset($spanned_cells[$time][$day])) {
                                    // Skip this cell as it's part of a rowspan
                                    continue;
                                }
                                $entry = $timetable[$time][$day] ?? null;
                                if ($entry && !$entry['processed']) {
                                    $entry['processed'] = true; // Mark as processed
                                    echo "<td class='schedule-cell' rowspan='{$entry['rowspan']}' style='background-color: {$entry['color']};'>";
                                    echo $entry['content'];
                                    echo "</td>";
                                } elseif (!isset($timetable[$time][$day])) {
                                    echo "<td class='schedule-cell'>-</td>";
                                }
                                ?>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        window.onload = function() {
            window.print();
        };
        </script>
    </body>

</html>