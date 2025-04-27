<?php
include 'config.php';
include 'function.php';

function formatTimeTo12Hour($time)
{
    return date('h:i A', strtotime($time));
}

if (isset($_POST['room'])) {
    $room         = sanitize($_POST['room']);
    $professor_id = isset($_POST['professor_id']) ? sanitize($_POST['professor_id']) : null;

    echo "<!-- Debug: Room = $room, Professor ID = $professor_id -->";

    $schedules = [];
    $sql       = "SELECT s.schedule_id, s.*, ta.professor_id, p.first_name, p.last_name, sub.subject_code, sec.section_code 
            FROM Schedule s 
            JOIN Teaching_Assignment ta ON s.assignment_id = ta.assignment_id 
            JOIN Professor p ON ta.professor_id = p.professor_id 
            JOIN Subject sub ON ta.subject_id = sub.subject_id 
            JOIN Section sec ON ta.section_id = sec.section_id 
            WHERE s.room = ?";
    $stmt      = $conn->prepare($sql);
    $stmt->bind_param("s", $room);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();

    $days       = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $time_slots = array_map(function ($h) {
        return sprintf("%02d:00:00", $h);
    }, range(7, 21));

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

    $timetable       = [];
    $color_index     = 0;
    $schedule_colors = [];

    foreach ($schedules as $schedule) {
        $start = strtotime($schedule['start_time']);
        $end   = strtotime($schedule['end_time']);
        $day   = $schedule['DAY'];

        $schedule_key = $schedule['assignment_id'];
        if (!isset($schedule_colors[$schedule_key])) {
            $schedule_colors[$schedule_key] = $pastel_colors[$color_index % count($pastel_colors)];
            $color_index++;
        }
        $color = $schedule_colors[$schedule_key];

        for ($t = $start; $t < $end; $t += 3600) {
            $time                   = date('H:i:00', $t);
            $timetable[$time][$day] = [
                'schedule_id' => $schedule['schedule_id'],
                'professor' => $schedule['first_name'] . ' ' . $schedule['last_name'],
                'professor_id' => $schedule['professor_id'],
                'subject' => $schedule['subject_code'],
                'section' => $schedule['section_code'],
                'room' => $schedule['room'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'assignment_id' => $schedule['assignment_id'],
                'color' => $color,
            ];
        }
    }

    echo "<table class='table table-bordered timetable'><tr><th>Time</th>";
    foreach ($days as $day) {
        echo "<th>$day</th>";
    }
    echo "</tr>";

    foreach ($time_slots as $time) {
        $time_display = formatTimeTo12Hour($time);
        echo "<tr><td>$time_display</td>";

        foreach ($days as $day) {
            $entry = $timetable[$time][$day] ?? null;
            if ($entry) {
                echo "<td style='background-color:{$entry['color']}; position:relative;'>";

                // Only show the ellipsis if the schedule belongs to the selected professor
                if ($professor_id && $entry['professor_id'] == $professor_id) {
                    echo "<!-- Debug: Ellipsis rendered for schedule_id={$entry['schedule_id']}, professor_id={$entry['professor_id']} -->";
                    echo "<div class='schedule-actions'>";
                    echo "<button class='btn btn-sm dropdown-toggle' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>";
                    echo "<i class='fas fa-ellipsis-v'></i>";
                    echo "</button>";
                    echo "<div class='dropdown-menu dropdown-menu-right'>";
                    echo "<a href='javascript:void(0)' class='dropdown-item edit-schedule' 
                            data-schedule-id='{$entry['schedule_id']}'
                            data-day='$day'
                            data-start-time='{$entry['start_time']}'
                            data-end-time='{$entry['end_time']}'
                            data-room='{$entry['room']}'
                            data-assignment-id='{$entry['assignment_id']}'>
                        <i class='fas fa-edit mr-2'></i>Edit
                    </a>";
                    echo "<a href='javascript:void(0)' class='dropdown-item delete-schedule' 
                            data-schedule-id='{$entry['schedule_id']}'>
                        <i class='fas fa-trash mr-2'></i>Delete
                    </a>";
                    echo "</div>";
                    echo "</div>";
                }

                echo "<div class='schedule-entry'>{$entry['professor']}</div>";
                echo "<div class='schedule-entry'>{$entry['subject']}</div>";
                echo "<div class='expanded-content'>";
                echo "<div class='schedule-entry'>{$entry['section']}</div>";
                echo "<div class='schedule-entry'>{$entry['room']}</div>";
                echo "</div>";
                echo "</td>";
            } else {
                echo "<td class='no-schedule' data-day='$day' data-time='$time'>-</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>