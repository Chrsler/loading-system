<?php
include_once 'function.php';

ob_start();

// Fetch initial data
$professors = getAllProfessors();
$rooms      = ['Lab 1', 'Room101', 'Room102', 'Room103', '201'];
$days       = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$time_slots = array_map(function ($h) { return sprintf("%02d:00:00", $h); }, range(7, 21));

// Handle professor search and room selection
$professor_id = isset($_GET['professor_id']) ? sanitize($_GET['professor_id']) : '';
$selected_room = isset($_GET['room']) ? sanitize($_GET['room']) : '';

ob_end_flush();
?>
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-calendar-alt me-2"></i>Class Scheduling</h1>
        <button id="printAllSchedule" class="btn btn-success mt-2">Print All Schedule</button>
    </div>

    <div id="feedback-alert" class="mb-4" style="display: none;"></div>

    <div class="row mb-4">
        <div class="col-md-4">
            <h3>Search Professor</h3>
            <input type="text" id="professor_search" class="form-control" placeholder="Search by name..."
                onkeyup="filterProfessors()">
            <select id="professor_select" name="professor_id" class="form-control mt-2"
                onchange="window.location.href='index.php?page=sched&professor_id='+this.value">
                <option value="">-- Select Professor --</option>
                <?php foreach ($professors as $professor): ?>
                <option value="<?php echo htmlspecialchars($professor['professor_id']); ?>"
                    <?php echo $professor_id == $professor['professor_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($professor['first_name'] . ' ' . $professor['last_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($professor_id): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h3>Select Room</h3>
            <div class="form-group">
                <select name="room" id="room_select" class="form-control">
                    <option value="">-- Select Room --</option>
                    <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo htmlspecialchars($room); ?>"
                        <?php echo $selected_room == $room ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h3>Room Timetable</h3>
            <div class="toggle-button" onclick="toggleTimetable()">Expand/Collapse Timetable</div>
            <button id="print-schedule-btn" class="btn btn-success mt-2" disabled>Print Schedule</button>
            <div id="timetable" class="timetable compressed">
                <?php if (!$selected_room): ?>
                <p class="text-muted">Please select a room to view the timetable.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="modal-error-alert" class="alert alert-danger mb-3" style="display: none;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="modal-error-text"></span>
                    </div>
                    <form id="modal_form">
                        <input type="hidden" id="modal_day" name="day">
                        <input type="hidden" id="modal_start_time" name="start_time">
                        <input type="hidden" id="modal_room" name="room">
                        <input type="hidden" id="modal_professor_id" name="professor_id"
                            value="<?php echo htmlspecialchars($professor_id); ?>">
                        <div class="form-group">
                            <label for="modal_assignment_id">Subject</label>
                            <select class="form-control" id="modal_assignment_id" name="assignment_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php
                                if ($professor_id) {
                                    $subjects = getTeachingAssignmentsByProfessorId($professor_id);
                                    foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['assignment_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title'] . ' (' . $subject['section_code'] . ')'); ?>
                                </option>
                                <?php endforeach;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modal_end_time">End Time</label>
                            <select class="form-control" id="modal_end_time" name="end_time" required></select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="add-schedule-btn">Add Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-modal-error-alert" class="alert alert-danger mb-3" style="display: none;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="edit-modal-error-text"></span>
                    </div>
                    <form id="edit_modal_form">
                        <input type="hidden" id="edit_modal_schedule_id" name="schedule_id">
                        <input type="hidden" id="edit_modal_room" name="room">
                        <input type="hidden" id="edit_modal_professor_id" name="professor_id"
                            value="<?php echo htmlspecialchars($professor_id); ?>">
                        <div class="form-group">
                            <label for="edit_modal_day">Day</label>
                            <select class="form-control" id="edit_modal_day" name="day" required>
                                <option value="">-- Select Day --</option>
                                <?php foreach ($days as $day): ?>
                                <option value="<?php echo htmlspecialchars($day); ?>">
                                    <?php echo htmlspecialchars($day); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_modal_start_time">Start Time</label>
                            <select class="form-control" id="edit_modal_start_time" name="start_time" required></select>
                        </div>
                        <div class="form-group">
                            <label for="edit_modal_assignment_id">Subject</label>
                            <select class="form-control" id="edit_modal_assignment_id" name="assignment_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php
                                if ($professor_id) {
                                    $subjects = getTeachingAssignmentsByProfessorId($professor_id);
                                    foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['assignment_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['title'] . ' (' . $subject['section_code'] . ')'); ?>
                                </option>
                                <?php endforeach;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_modal_end_time">End Time</label>
                            <select class="form-control" id="edit_modal_end_time" name="end_time" required></select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="update-schedule-btn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Styles remain the same -->
<style>
/* Your existing CSS remains unchanged */
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Ensure jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded');
} else {
    console.log('jQuery loaded successfully');
}

jQuery(document).ready(function($) {
    // Global state variables
    let selectedRoom = '<?php echo $selected_room; ?>';
    let selectedProfessorId = '<?php echo $professor_id; ?>';
    let isExpanded = localStorage.getItem('timetableExpanded') === 'true';

    // --- Utility Functions ---
    function formatTimeTo12Hour(time) {
        const [hours, minutes] = time.split(':');
        const h = parseInt(hours);
        const suffix = h >= 12 ? 'PM' : 'AM';
        const hour12 = ((h + 11) % 12 + 1);
        return `${hour12}:${minutes} ${suffix}`;
    }

    function showFeedback(message, type) {
        const alertDiv = $('#feedback-alert');
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        alertDiv.html(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
            </div>
        `).show();
        if (type === 'success') {
            setTimeout(() => {
                alertDiv.find('.alert').alert('close');
            }, 5000);
        }
    }

    function showModalError(message, alertId = '#modal-error-alert', textId = '#modal-error-text') {
        const errorAlert = $(alertId);
        $(textId).text(message);
        errorAlert.show();
        errorAlert.closest('.modal-body').animate({
            scrollTop: 0
        }, 200);
    }

    function hideModalError(alertId = '#modal-error-alert') {
        $(alertId).hide();
    }

    // --- Timetable Functions ---
    function filterProfessors() {
        let input = document.getElementById('professor_search').value.toLowerCase();
        let select = document.getElementById('professor_select');
        let options = select.getElementsByTagName('option');
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === "") continue;
            let txt = options[i].textContent.toLowerCase();
            options[i].style.display = txt.includes(input) ? '' : 'none';
        }
    }

    function showTimetable(room) {
        if (!room) {
            $('#timetable').html('<p class="text-muted">Please select a room to view the timetable.</p>');
            return;
        }
        selectedRoom = room;
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('room', room);
        window.history.pushState({
            path: currentUrl.href
        }, '', currentUrl.href);
        fetchTimetable(room);
    }

    function fetchTimetable(room) {
        if (!room) return;
        $('#timetable').html(
            '<div class="text-center p-3"><span class="spinner-border spinner-border-sm"></span> Loading timetable...</div>'
        );
        $.post('fetch_timetable.php', {
            room: room,
            professor_id: selectedProfessorId
        }, function(data) {
            $('#timetable')
                .html(data)
                .removeClass('expanded compressed')
                .addClass(isExpanded ? 'expanded' : 'compressed');

            $('#timetable [data-toggle="dropdown"]').dropdown();
            console.log('Dropdowns found:', $('#timetable [data-toggle="dropdown"]').length);

            setupScheduleActions();
            setupAddScheduleClick();
            console.log('Event handlers set up for edit/delete');
        }).fail(function(xhr) {
            console.error('Timetable fetch error:', xhr.responseText);
            $('#timetable').html(
                '<p class="text-danger text-center">Failed to load timetable. Please try again.</p>'
            );
            showFeedback('Failed to load timetable', 'danger');
        });
    }

    function toggleTimetable() {
        isExpanded = !isExpanded;
        localStorage.setItem('timetableExpanded', isExpanded);
        $('#timetable').toggleClass('expanded compressed');
    }

    // --- Add Schedule Modal & Actions ---
    function setupAddScheduleClick() {
        $(document).off('click', '.timetable td.no-schedule');
        $(document).on('click', '.timetable td.no-schedule', function() {
            let day = $(this).data('day');
            let start_time = $(this).data('time');
            if (day && start_time && selectedRoom) {
                showScheduleModal(day, start_time, selectedRoom);
            } else {
                console.warn("Missing data for Add Schedule modal:", {
                    day,
                    start_time,
                    room: selectedRoom
                });
            }
        });
    }

    function showScheduleModal(day, start_time, room) {
        $('#modal_form')[0].reset();
        hideModalError();
        $('#modal_day').val(day);
        $('#modal_start_time').val(start_time);
        $('#modal_room').val(room);
        $('#modal_professor_id').val(selectedProfessorId);
        let times = <?php echo json_encode($time_slots); ?>;
        let startIndex = times.indexOf(start_time);
        let endTimeSelect = $('#modal_end_time');
        endTimeSelect.empty().append('<option value="">-- Select End Time --</option>');
        if (startIndex !== -1) {
            for (let i = startIndex + 1; i < times.length; i++) {
                let time12hr = formatTimeTo12Hour(times[i]);
                endTimeSelect.append(`<option value="${times[i]}">${time12hr}</option>`);
            }
        }
        $('#scheduleModal').modal('show');
    }

    function submitSchedule() {
        let formData = $('#modal_form').serialize() + '&ajax_add_schedule=1';
        console.log('Add Schedule Form Data:', formData);
        hideModalError();
        const $submitButton = $('#add-schedule-btn');
        $.ajax({
            url: 'index.php?page=sched',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $submitButton.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Saving...');
            },
            success: function(response) {
                console.log('Add Schedule Response:', response);
                if (response && response.status === 'success') {
                    showFeedback(response.message || 'Schedule added successfully!', 'success');
                    $('#scheduleModal').modal('hide');
                    fetchTimetable(selectedRoom);
                } else {
                    const errorMsg = response?.message ||
                        'An unknown error occurred while adding the schedule.';
                    showModalError(errorMsg);
                }
            },
            error: function(xhr) {
                console.error("Add Schedule AJAX Error:", xhr.status, xhr.responseText);
                let errorMsg = 'Failed to communicate with the server.';
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    errorMsg = jsonResponse.message || errorMsg;
                } catch (e) {
                    console.error('Failed to parse error response:', e);
                }
                showModalError(errorMsg);
            },
            complete: function() {
                $submitButton.prop('disabled', false).text('Add Schedule');
            }
        });
    }

    // --- Edit/Delete Schedule Actions ---
    function setupScheduleActions() {
        $(document).off('click', '.edit-schedule');
        $(document).off('click', '.delete-schedule');
        $(document).on('click', '.edit-schedule', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Edit clicked:', $(this).data());
            const scheduleId = $(this).data('schedule-id');
            const day = $(this).data('day');
            const startTime = $(this).data('start-time');
            const endTime = $(this).data('end-time');
            const room = $(this).data('room');
            const assignmentId = $(this).data('assignment-id');
            $(this).closest('.dropdown-menu').removeClass('show');
            showEditScheduleModal(scheduleId, day, startTime, endTime, room, assignmentId);
        });
        $(document).on('click', '.delete-schedule', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Delete clicked:', $(this).data());
            const scheduleId = $(this).data('schedule-id');
            $(this).closest('.dropdown-menu').removeClass('show');
            if (confirm(
                    'Are you sure you want to delete this schedule? This action cannot be undone.')) {
                deleteSchedule(scheduleId);
            }
        });
    }

    function showEditScheduleModal(scheduleId, day, startTime, endTime, room, assignmentId) {
        $('#edit_modal_form')[0].reset();
        hideModalError('#edit-modal-error-alert');
        $('#edit_modal_schedule_id').val(scheduleId);
        $('#edit_modal_day').val(day);
        $('#edit_modal_room').val(room);
        $('#edit_modal_professor_id').val(selectedProfessorId);
        $('#edit_modal_assignment_id').val(assignmentId);

        let times = <?php echo json_encode($time_slots); ?>;
        let startTimeSelect = $('#edit_modal_start_time');
        startTimeSelect.empty().append('<option value="">-- Select Start Time --</option>');
        for (let i = 0; i < times.length - 1; i++) {
            let time12hr = formatTimeTo12Hour(times[i]);
            let selectedAttr = times[i] === startTime ? 'selected' : '';
            startTimeSelect.append(`<option value="${times[i]}" ${selectedAttr}>${time12hr}</option>`);
        }

        let startIndex = times.indexOf(startTime);
        let endTimeSelect = $('#edit_modal_end_time');
        endTimeSelect.empty().append('<option value="">-- Select End Time --</option>');
        if (startIndex !== -1) {
            for (let i = startIndex + 1; i < times.length; i++) {
                let time12hr = formatTimeTo12Hour(times[i]);
                let selectedAttr = times[i] === endTime ? 'selected' : '';
                endTimeSelect.append(`<option value="${times[i]}" ${selectedAttr}>${time12hr}</option>`);
            }
        }

        startTimeSelect.off('change').on('change', function() {
            let newStartTime = $(this).val();
            let newStartIndex = times.indexOf(newStartTime);
            endTimeSelect.empty().append('<option value="">-- Select End Time --</option>');
            if (newStartIndex !== -1) {
                for (let i = newStartIndex + 1; i < times.length; i++) {
                    let time12hr = formatTimeTo12Hour(times[i]);
                    endTimeSelect.append(`<option value="${times[i]}">${time12hr}</option>`);
                }
            }
        });

        $('#editScheduleModal').modal('show');
    }

    function updateSchedule() {
        let formData = $('#edit_modal_form').serialize() + '&ajax_update_schedule=1';
        console.log('Update Schedule Form Data:', formData);
        hideModalError('#edit-modal-error-alert');
        const $saveButton = $('#update-schedule-btn');
        $.ajax({
            url: 'index.php?page=sched',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $saveButton.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Saving...');
            },
            success: function(response) {
                console.log('Update Schedule Response:', response);
                if (response && response.status === 'success') {
                    showFeedback(response.message || 'Schedule updated successfully!', 'success');
                    $('#editScheduleModal').modal('hide');
                    fetchTimetable(selectedRoom);
                } else {
                    const errorMsg = response?.message ||
                        'An unknown error occurred while updating the schedule.';
                    showModalError(errorMsg, '#edit-modal-error-alert', '#edit-modal-error-text');
                }
            },
            error: function(xhr) {
                console.error("Update Schedule AJAX Error:", xhr.status, xhr.responseText);
                let errorMsg = 'Failed to communicate with the server.';
                try {
                    const jsonResponse = JSON.parse(xhr.responseText);
                    errorMsg = jsonResponse.message || errorMsg;
                } catch (e) {
                    console.error('Failed to parse error response:', e);
                }
                showModalError(errorMsg, '#edit-modal-error-alert', '#edit-modal-error-text');
            },
            complete: function() {
                $saveButton.prop('disabled', false).text('Save Changes');
            }
        });
    }

    function deleteSchedule(scheduleId) {
        $.ajax({
            url: 'index.php?page=sched',
            type: 'POST',
            data: {
                ajax_delete_schedule: 1,
                schedule_id: scheduleId,
                professor_id: selectedProfessorId
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    showFeedback(response.message || 'Schedule deleted successfully!', 'success');
                    fetchTimetable(selectedRoom);
                } else {
                    const errorMsg = response?.message || 'Failed to delete the schedule.';
                    showFeedback(errorMsg, 'danger');
                }
            },
            error: function(xhr) {
                console.error("Delete Schedule AJAX Error:", xhr.responseText);
                showFeedback('Failed to communicate with the server.', 'danger');
            }
        });
    }

    function updatePrintButtonState() {
        const printButton = $('#print-schedule-btn');
        if (selectedProfessorId) {
            printButton.prop('disabled', false);
        } else {
            printButton.prop('disabled', true);
        }
    }

    function printSchedule() {
        if (!selectedProfessorId) {
            showFeedback('Please select a professor to print their schedule.', 'danger');
            return;
        }
        // Open the print page in a new tab
        window.open(`print_schedule.php?professor_id=${selectedProfessorId}`, '_blank');
    }

    // --- Initialization ---
    $('#timetable').addClass(isExpanded ? 'expanded' : 'compressed');
    $('#scheduleModal').on('hidden.bs.modal', function() {
        $('#modal_form')[0].reset();
        hideModalError();
    });
    $('#editScheduleModal').on('hidden.bs.modal', function() {
        $('#edit_modal_form')[0].reset();
        hideModalError('#edit-modal-error-alert');
    });
    if (selectedRoom) {
        fetchTimetable(selectedRoom);
    } else {
        setupScheduleActions();
        setupAddScheduleClick();
        $('[data-toggle="dropdown"]').dropdown();
    }

    // Attach room select change event
    $('#room_select').on('change', function() {
        showTimetable(this.value);
    });

    // Attach button click events
    $('#add-schedule-btn').on('click', function() {
        submitSchedule();
    });

    $('#update-schedule-btn').on('click', function() {
        updateSchedule();
    });

    updatePrintButtonState(); // Initial state of print button

    // Update print button state when professor changes
    $('#professor_select').on('change', function() {
        selectedProfessorId = this.value;
        updatePrintButtonState();
    });

    // Attach print button click event
    $('#print-schedule-btn').on('click', function() {
        printSchedule();
    });

    // Attach print all schedules button click event
    $('#printAllSchedule').on('click', function() {
        window.open('print_all_schedules.php', '_blank');
    });

});
</script>