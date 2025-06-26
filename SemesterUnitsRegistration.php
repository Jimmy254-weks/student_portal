<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize classes
$user = new User();
$student = new Student();
$db = new Database();

$page_title = 'Unit Registration';
$page_css = 'dashboard.css';
$page_css = 'semester_units.css';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit();
}

// Get user and student data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

if (!$current_user || !$student_profile) {
    flash('error', 'Please complete your profile first');
    redirect('profile_setup.php');
    exit();
}

// Determine current semester and academic year
$current_month = date('n');
$current_semester = ($current_month >= 1 && $current_month <= 6) ? 'First Semester' : 'Second Semester';
$current_year = date('Y');
$academic_year = ($current_semester == 'First Semester') ? "$current_year/" . ($current_year + 1) : ($current_year - 1) . "/$current_year";

// Check if student has registered for the current semester
$is_registered = false;
$db->query('SELECT * FROM semester_registrations 
            WHERE student_id = :student_id 
            AND semester = :semester 
            AND academic_year = :academic_year');
$db->bind(':student_id', $student_profile->id);
$db->bind(':semester', $current_semester);
$db->bind(':academic_year', $academic_year);
$registration_data = $db->single();

if (!$registration_data) {
    flash('error', 'You must register for the semester before selecting units');
    redirect('SemRegister.php');
    exit();
}

$registration_deadline = $registration_data->deadline_date;
$registration_closed = (strtotime($registration_deadline) < time());

// Get registered units with day and timeslot info
$registered_units = [];
$db->query('SELECT su.id as registration_id, u.id as unit_id, u.code, u.name, 
                   d.id as day_id, d.name as day_name, 
                   t.id as timeslot_id, t.name as timeslot_name, t.start_time, t.end_time
            FROM student_units su
            JOIN units u ON su.unit_id = u.id
            LEFT JOIN days d ON su.day_id = d.id
            LEFT JOIN timeslots t ON su.timeslot_id = t.id
            WHERE su.student_id = :student_id
            AND su.semester = :semester
            AND su.academic_year = :academic_year
            ORDER BY d.id, t.start_time, u.code');
$db->bind(':student_id', $student_profile->id);
$db->bind(':semester', $current_semester);
$db->bind(':academic_year', $academic_year);
$registered_units = $db->resultSet();

// Get all available units for the program
$all_units = [];
if (!empty($student_profile->program_id)) {
    $db->query('SELECT u.* FROM units u
                WHERE u.program_id = :program_id 
                AND (u.semester = :semester OR u.semester = "Both")
                ORDER BY u.code');
    $db->bind(':program_id', $student_profile->program_id);
    $db->bind(':semester', $current_semester);
    $all_units = $db->resultSet();
    
    if (empty($all_units)) {
        flash('info', 'No units available for your program this semester. Please contact your department.');
    }
} else {
    flash('error', 'Your academic program is not assigned. Please contact administration.');
}

// Filter out registered units to get offered units
$offered_units = array_filter($all_units, function($unit) use ($registered_units) {
    foreach ($registered_units as $ru) {
        if ($ru->unit_id == $unit->id) {
            return false;
        }
    }
    return true;
});

// Get all days and timeslots for dropdowns
$days = [];
$timeslots = [];
$db->query('SELECT * FROM days ORDER BY id');
$days = $db->resultSet();
$db->query('SELECT * FROM timeslots ORDER BY start_time');
$timeslots = $db->resultSet();

// Process unit registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$registration_closed) {
    if (isset($_POST['register_units'])) {
        $selected_units = $_POST['units'] ?? [];
        $selected_days = $_POST['days'] ?? [];
        $selected_timeslots = $_POST['timeslots'] ?? [];
        
        // Validate selection
        if (count($selected_units) > 7) {
            flash('error', 'You cannot register more than 7 units');
        } elseif (empty($selected_units)) {
            flash('error', 'Please select at least one unit');
        } else {
            // Get currently registered units
            $current_registrations = [];
            $db->query('SELECT unit_id, day_id, timeslot_id FROM student_units 
                       WHERE student_id = :student_id 
                       AND semester = :semester 
                       AND academic_year = :academic_year');
            $db->bind(':student_id', $student_profile->id);
            $db->bind(':semester', $current_semester);
            $db->bind(':academic_year', $academic_year);
            $current_registrations = $db->resultSet();
            
            // Combine existing and new selections
            $all_selections = [];
            $unit_ids = [];
            
            // First add all current registrations
            foreach ($current_registrations as $reg) {
                $all_selections[] = [
                    'unit_id' => $reg->unit_id,
                    'day_id' => $reg->day_id,
                    'timeslot_id' => $reg->timeslot_id
                ];
                $unit_ids[] = $reg->unit_id;
            }
            
            // Then add new selections that aren't already registered
            foreach ($selected_units as $index => $unit_id) {
                if (!in_array($unit_id, $unit_ids)) {
                    $day_id = $selected_days[$index] ?? null;
                    $timeslot_id = $selected_timeslots[$index] ?? null;
                    
                    if (!$day_id || !$timeslot_id) {
                        flash('error', 'Please select both day and time for all selected units');
                        redirect('SemesterUnitsRegistration.php');
                        exit();
                    }
                    
                    $all_selections[] = [
                        'unit_id' => $unit_id,
                        'day_id' => $day_id,
                        'timeslot_id' => $timeslot_id
                    ];
                    $unit_ids[] = $unit_id;
                }
            }
            
            // Check for time collisions in combined selections
            $schedule = [];
            $has_collision = false;
            $collision_details = '';
            
            foreach ($all_selections as $selection) {
                // Get timeslot details
                $db->query('SELECT * FROM timeslots WHERE id = :id');
                $db->bind(':id', $selection['timeslot_id']);
                $timeslot = $db->single();
                
                // Get unit details
                $db->query('SELECT code, name FROM units WHERE id = :id');
                $db->bind(':id', $selection['unit_id']);
                $unit = $db->single();
                
                // Check for collisions with existing schedule
                foreach ($schedule as $scheduled) {
                    if ($scheduled['day_id'] == $selection['day_id']) {
                        // Check if timeslots overlap
                        if (!($timeslot->end_time <= $scheduled['start_time'] || 
                              $timeslot->start_time >= $scheduled['end_time'])) {
                            $has_collision = true;
                            
                            $collision_details .= "• {$unit->code} ({$unit->name}) conflicts with {$scheduled['unit_code']} on " . 
                                                  $days[$selection['day_id']-1]->name . " (" . 
                                                  date('H:i', strtotime($timeslot->start_time)) . "-" . 
                                                  date('H:i', strtotime($timeslot->end_time)) . ")\n";
                        }
                    }
                }
                
                // Add to schedule if no collision
                if (!$has_collision) {
                    $schedule[] = [
                        'unit_id' => $selection['unit_id'],
                        'unit_code' => $unit->code,
                        'unit_name' => $unit->name,
                        'day_id' => $selection['day_id'],
                        'timeslot_id' => $selection['timeslot_id'],
                        'start_time' => $timeslot->start_time,
                        'end_time' => $timeslot->end_time
                    ];
                }
            }
            
            if ($has_collision) {
                flash('error', "You have scheduling conflicts:\n" . $collision_details . 
                      "\nPlease adjust your selections to avoid overlapping units.");
                redirect('SemesterUnitsRegistration.php');
                exit();
            }
            
            // Clear existing registrations
            $db->query('DELETE FROM student_units 
                       WHERE student_id = :student_id 
                       AND semester = :semester 
                       AND academic_year = :academic_year');
            $db->bind(':student_id', $student_profile->id);
            $db->bind(':semester', $current_semester);
            $db->bind(':academic_year', $academic_year);
            $db->execute();
            
            // Add all validated registrations
            $success = true;
            foreach ($schedule as $item) {
                $db->query('INSERT INTO student_units 
                           (student_id, unit_id, day_id, timeslot_id, semester, academic_year, registration_date) 
                           VALUES (:student_id, :unit_id, :day_id, :timeslot_id, :semester, :academic_year, NOW())');
                $db->bind(':student_id', $student_profile->id);
                $db->bind(':unit_id', $item['unit_id']);
                $db->bind(':day_id', $item['day_id']);
                $db->bind(':timeslot_id', $item['timeslot_id']);
                $db->bind(':semester', $current_semester);
                $db->bind(':academic_year', $academic_year);
                if (!$db->execute()) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                flash('success', 'Unit registration updated successfully');
                redirect('SemesterUnitsRegistration.php');
                exit();
            } else {
                flash('error', 'Failed to update unit registration');
            }
        }
    } elseif (isset($_POST['drop_unit'])) {
        $registration_id = $_POST['registration_id'] ?? 0;
        
        $db->query('DELETE FROM student_units 
                   WHERE id = :id 
                   AND student_id = :student_id');
        $db->bind(':id', $registration_id);
        $db->bind(':student_id', $student_profile->id);
        
        if ($db->execute()) {
            flash('success', 'Unit dropped successfully');
            redirect('SemesterUnitsRegistration.php');
            exit();
        } else {
            flash('error', 'Failed to drop unit');
        }
    }
}

include 'includes/header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Units Registration</h3>
                    
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                        <a href="SemRegister.php" class="btn btn-box-tool">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                
                <div class="box-body">
                    
                    <?php if ($registration_closed): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-circle"></i> The deadline for unit registration changes has passed. You can no longer add or drop units.
                        </div>
                    <?php endif; ?>
                    
                    <table id="curved_border" class="table table-striped">
                        <tr>
                            <td colspan="4">
                                <span style="font-size: 10pt">
                                    <span style="text-decoration: underline"><strong>OFFERED UNITS:</strong></span><br />
                                    <span style="color: red">Select units below to register.</span>
                                </span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td colspan="4">
                                <?php if (!empty($offered_units)): ?>
                                    <form method="post">
                                        <table class="table" id="units-table">
                                            <thead>
                                                <tr>
                                                    <th width="50px">Select</th>
                                                    <th>Unit</th>
                                                    <th>Day</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($offered_units as $unit): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="units[]" value="<?= $unit->id ?>" 
                                                                   class="unit-checkbox"
                                                                   <?= $registration_closed ? 'disabled' : '' ?>>
                                                        </td>
                                                        <td class="unit-info">
                                                            <strong><?= htmlspecialchars($unit->code) ?></strong><br>
                                                            <?= htmlspecialchars($unit->name) ?>
                                                        </td>
                                                        <td>
                                                            <select name="days[]" class="form-control day-select" <?= $registration_closed ? 'disabled' : '' ?>>
                                                                <option value="">Select Day</option>
                                                                <?php foreach ($days as $day): ?>
                                                                    <option value="<?= $day->id ?>">
                                                                        <?= htmlspecialchars($day->name) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="timeslots[]" class="form-control timeslot-select" <?= $registration_closed ? 'disabled' : '' ?>>
                                                                <option value="">Select Time</option>
                                                                <?php foreach ($timeslots as $timeslot): ?>
                                                                    <option value="<?= $timeslot->id ?>">
                                                                        <?= htmlspecialchars($timeslot->name) ?> (<?= date('H:i', strtotime($timeslot->start_time)) ?>-<?= date('H:i', strtotime($timeslot->end_time)) ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        
                                        <?php if (!$registration_closed): ?>
                                            <div class="text-right">
                                                <button type="submit" name="register_units" class="btn btn-primary">
                                                    <i class="fa fa-save"></i> Save Registration
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> All available units have been registered.
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <td colspan="4">
                                <span style="font-size: 10pt; text-decoration: underline"><strong>REGISTERED UNITS:</strong></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td colspan="4">
                                <?php if (!empty($registered_units)): ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th width="50px">#</th>
                                                <th>Unit Code</th>
                                                <th>Unit Name</th>
                                                <th>Day</th>
                                                <th>Time</th>
                                                <th width="150px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($registered_units as $index => $unit): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td class="unit-code"><?= htmlspecialchars($unit->code) ?></td>
                                                    <td><?= htmlspecialchars($unit->name) ?></td>
                                                    <td><?= htmlspecialchars($unit->day_name ?? 'Not set') ?></td>
                                                    <td>
                                                        <?php if ($unit->timeslot_name): ?>
                                                            <?= htmlspecialchars($unit->timeslot_name) ?> 
                                                            (<?= date('H:i', strtotime($unit->start_time)) ?>-<?= date('H:i', strtotime($unit->end_time)) ?>)
                                                        <?php else: ?>
                                                            Not set
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!$registration_closed): ?>
                                                            <form method="post" style="display: inline-block;">
                                                                <input type="hidden" name="registration_id" value="<?= $unit->registration_id ?>">
                                                                <button type="submit" name="drop_unit" class="btn btn-danger btn-xs" 
                                                                        onclick="return confirm('Are you sure you want to drop <?= htmlspecialchars($unit->code) ?>?')">
                                                                    <i class="fa fa-trash"></i> Drop
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="UnitDetails.php?id=<?= $unit->unit_id ?>" class="btn btn-info btn-xs">
                                                            <i class="fa fa-info-circle"></i> Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fa fa-exclamation-circle"></i> You haven't registered any units for this semester yet.
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-default">
                            <i class="fa fa-home"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.unit-checkbox');
    const daySelects = document.querySelectorAll('.day-select');
    const timeslotSelects = document.querySelectorAll('.timeslot-select');
    const submitBtn = document.querySelector('button[name="register_units"]');
    
    // Initialize validation
    updateSelection();
    
    // Enable/disable selects based on checkbox state
    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('change', function() {
            daySelects[index].disabled = !this.checked;
            timeslotSelects[index].disabled = !this.checked;
            updateSelection();
        });
        
        // Initialize disabled state
        daySelects[index].disabled = true;
        timeslotSelects[index].disabled = true;
    });
    
    // Update selection count and validate
    function updateSelection() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        if (submitBtn) {
            if (checked > 7) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Too many units selected';
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-danger');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-save"></i> Save Registration';
                submitBtn.classList.remove('btn-danger');
                submitBtn.classList.add('btn-primary');
            }
        }
    }
    
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checked = Array.from(checkboxes).filter(cb => cb.checked).length;
            if (checked > 7) {
                e.preventDefault();
                alert('You cannot select more than 7 units. Please remove some selections.');
                return;
            } else if (checked === 0) {
                e.preventDefault();
                alert('Please select at least one unit to register.');
                return;
            }
            
            // Validate day and timeslot selections
            let valid = true;
            checkboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    if (!daySelects[index].value || !timeslotSelects[index].value) {
                        valid = false;
                        daySelects[index].classList.add('is-invalid');
                        timeslotSelects[index].classList.add('is-invalid');
                    } else {
                        daySelects[index].classList.remove('is-invalid');
                        timeslotSelects[index].classList.remove('is-invalid');
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please select both day and time for all selected units.');
            }
        });
    }
});
</script>

<style>
.unit-info {
    white-space: nowrap;
}
.is-invalid {
    border-color: #dc3545;
}
</style>

<?php include 'includes/footer.php'; ?>