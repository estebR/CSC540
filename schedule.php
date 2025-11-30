<?php
require_once 'auth.php';
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: home.php");
    exit;
}

$errors  = [];
$success = '';

// Logged-in user
$current_account_id = $_SESSION['account_id'] ?? null;
$current_user_id    = $_SESSION['user_id'] ?? null;

// Today (for min date)
$today     = new DateTime('today');
$today_str = $today->format('Y-m-d');

function build_time_slots($start = '08:00', $end = '20:00', $interval = 15)
{
    $slots   = [];
    $startDT = DateTime::createFromFormat('H:i', $start);
    $endDT   = DateTime::createFromFormat('H:i', $end);

    for ($t = clone $startDT; $t <= $endDT; $t->modify("+{$interval} minutes")) {
        $slots[] = $t->format('H:i');
    }

    return $slots;
}

// all valid times for the select (08:00–20:00, every 15 min)
$time_slots = build_time_slots('08:00', '20:00', 15);

// Fetch tutors (Users + Tutors), make sure each tutor appears once
$tutors = [];
$tutorSql = "
    SELECT u.user_id, u.first_name, u.last_name
    FROM Users u
    INNER JOIN Tutors t ON u.user_id = t.user_id
    ORDER BY u.first_name, u.last_name
";
$tutorResult = mysqli_query($conn, $tutorSql);
while ($row = mysqli_fetch_assoc($tutorResult)) {
    $uid = (int)$row['user_id'];
    $tutors[$uid] = $row; // keeps only one row per user_id
}
mysqli_free_result($tutorResult);
$tutors = array_values($tutors);

// Fetch all subjects (for JS / right panel)
$subjects = [];
$subSql = "
    SELECT subject_id, subject_name, department, level
    FROM Subject
    ORDER BY department, level, subject_name
";
$subResult = mysqli_query($conn, $subSql);
while ($row = mysqli_fetch_assoc($subResult)) {
    $subjects[] = $row;
}
mysqli_free_result($subResult);

// subject_id -> subject info map
$subjectsById = [];
foreach ($subjects as $sub) {
    $subjectsById[(int)$sub['subject_id']] = $sub;
}

// Tutor qualifications (which subjects each tutor can teach)
$tutor_subjects = [];
$qualSql = "SELECT user_id, subject_id FROM TutorQualification";
$qualResult = mysqli_query($conn, $qualSql);
while ($row = mysqli_fetch_assoc($qualResult)) {
    $uid = (int)$row['user_id'];
    $sid = (int)$row['subject_id'];
    if (!isset($tutor_subjects[$uid])) {
        $tutor_subjects[$uid] = [];
    }
    $tutor_subjects[$uid][] = $sid;
}
mysqli_free_result($qualResult);

// Tutor availability
$tutor_availability = [];
$availSql = "SELECT user_id, day_of_week, start_time, end_time FROM Availability";
$availResult = mysqli_query($conn, $availSql);
while ($row = mysqli_fetch_assoc($availResult)) {
    $uid = (int)$row['user_id'];
    if (!isset($tutor_availability[$uid])) {
        $tutor_availability[$uid] = [];
    }
    $tutor_availability[$uid][] = [
        'day_of_week' => (int)$row['day_of_week'],         // 1 = Mon ... 5 = Fri
        'start_time'  => substr($row['start_time'], 0, 5), // "HH:MM"
        'end_time'    => substr($row['end_time'], 0, 5),
    ];
}
mysqli_free_result($availResult);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Student requests 1-on-1
    if ($action === 'request_1on1') {
        $tutor_id   = (int)($_POST['tutor_id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $date_str   = trim($_POST['session_date'] ?? '');
        $time_str   = trim($_POST['session_time'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');

        if (!$current_user_id || !$current_account_id) {
            $errors[] = "You must be logged in as a student to request a session.";
        }

        if (!$tutor_id || !$subject_id || $date_str === '' || $time_str === '') {
            $errors[] = "Please select a tutor, subject, date, and time.";
        }

        $sessionDate = null;
        $startDT     = null;
        $now         = new DateTime('now');

        if ($date_str !== '') {
            $sessionDate = DateTime::createFromFormat('Y-m-d', $date_str);
            if (!$sessionDate) {
                $errors[] = "Invalid date.";
            } else {
                $sessionDate->setTime(0, 0);

                if ($sessionDate < $today) {
                    $errors[] = "The date must be in the future.";
                }

                $dow = (int)$sessionDate->format('w'); // 0 = Sun, 6 = Sat
                if ($dow === 0 || $dow === 6) {
                    $errors[] = "Sessions are available Monday through Friday only.";
                }
            }
        }

        // time sanity: 08:00–20:00, 15-minute increments
        if ($time_str !== '') {
            if ($time_str < '08:00' || $time_str > '20:00') {
                $errors[] = "Time must be between 8:00 AM and 8:00 PM.";
            } else {
                list($hh, $mm) = explode(':', $time_str);
                if (!in_array($mm, ['00', '15', '30', '45'], true)) {
                    $errors[] = "Time must be in 15-minute increments.";
                }
            }
        }

        if ($sessionDate && $time_str !== '' && empty($errors)) {
            $startDT = DateTime::createFromFormat('Y-m-d H:i', "$date_str $time_str");
            if (!$startDT) {
                $errors[] = "Invalid time.";
            } else {
                if ($sessionDate == $today && $startDT <= $now) {
                    $errors[] = "Choose a time later than the current time.";
                }
            }
        }

        // tutor must teach this subject
        if ($tutor_id && $subject_id) {
            if (!isset($tutor_subjects[$tutor_id]) ||
                !in_array($subject_id, $tutor_subjects[$tutor_id], true)) {
                $errors[] = "This tutor is not assigned to that subject.";
            }
        }

        // check against tutor availability (1-hour block inside a slot)
        $endDT = null;
        if ($startDT && $tutor_id) {
            $endDT = (clone $startDT)->modify('+1 hour');
            $start_hm = $startDT->format('H:i');
            $end_hm   = $endDT->format('H:i');
            $dowPHP   = (int)$startDT->format('N'); // 1 = Mon .. 7 = Sun

            $inAvailability = false;
            if (isset($tutor_availability[$tutor_id])) {
                foreach ($tutor_availability[$tutor_id] as $slot) {
                    if ((int)$slot['day_of_week'] === $dowPHP) {
                        if ($start_hm >= $slot['start_time'] && $end_hm <= $slot['end_time']) {
                            $inAvailability = true;
                            break;
                        }
                    }
                }
            }

            if (!$inAvailability) {
                $errors[] = "The selected time is outside this tutor's availability.";
            }
        }

        // make sure tutor is not already booked in Booked
        if ($startDT && $endDT && empty($errors)) {
            $session_start_str = $startDT->format('Y-m-d H:i:s');
            $session_end_str   = $endDT->format('Y-m-d H:i:s');

            $confSql = "
                SELECT COUNT(*) AS cnt
                FROM Booked
                WHERE user_id = ?
                  AND status IN ('pending','scheduled')
                  AND session_start < ?
                  AND session_end   > ?
            ";
            $confStmt = mysqli_prepare($conn, $confSql);
            mysqli_stmt_bind_param($confStmt, "iss", $tutor_id, $session_end_str, $session_start_str);
            mysqli_stmt_execute($confStmt);
            $confRes = mysqli_stmt_get_result($confStmt);
            $confRow = mysqli_fetch_assoc($confRes);
            mysqli_stmt_close($confStmt);

            if ($confRow && (int)$confRow['cnt'] > 0) {
                $errors[] = "This tutor already has a session during that time.";
            }
        }

        // create Booked + AppointmentStudent
        if ($startDT && $endDT && empty($errors)) {
            try {
                mysqli_begin_transaction($conn);

                $session_start_str = $startDT->format('Y-m-d H:i:s');
                $session_end_str   = $endDT->format('Y-m-d H:i:s');
                $max_students      = 1;

                $bookedSql = "
                    INSERT INTO Booked 
                        (user_id, subject_id, created_by_account_id, session_start, session_end, max_students, platform, status, created_at)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, 'in_person', 'pending', NOW())
                ";
                $bookedStmt = mysqli_prepare($conn, $bookedSql);
                mysqli_stmt_bind_param(
                    $bookedStmt,
                    "iiissi",
                    $tutor_id,
                    $subject_id,
                    $current_account_id,
                    $session_start_str,
                    $session_end_str,
                    $max_students
                );
                mysqli_stmt_execute($bookedStmt);
                $booked_id = mysqli_insert_id($conn);
                mysqli_stmt_close($bookedStmt);

                $joinSql = "
                    INSERT INTO AppointmentStudent (booked_id, user_id, booked_at)
                    VALUES (?, ?, NOW())
                ";
                $joinStmt = mysqli_prepare($conn, $joinSql);
                mysqli_stmt_bind_param($joinStmt, "ii", $booked_id, $current_user_id);
                mysqli_stmt_execute($joinStmt);
                mysqli_stmt_close($joinStmt);

                if ($notes !== '') {
                    $noteSql = "
                        INSERT INTO Notes (booked_id, user_id, note_text)
                        VALUES (?, ?, ?)
                    ";
                    $noteStmt = mysqli_prepare($conn, $noteSql);
                    mysqli_stmt_bind_param($noteStmt, "iis", $booked_id, $current_user_id, $notes);
                    mysqli_stmt_execute($noteStmt);
                    mysqli_stmt_close($noteStmt);
                }

                mysqli_commit($conn);
                $success = "Your 1-on-1 session request has been sent to the tutor.";

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = "Unable to create this session. Please try again.";
            }
        }
    }

    // Student joins an existing scheduled group session
    if ($action === 'join_session') {
        $booked_id = (int)($_POST['booked_id'] ?? 0);

        if (!$booked_id) {
            $errors[] = "Invalid session selection.";
        }

        if (empty($errors)) {
            try {
                mysqli_begin_transaction($conn);

                $capSql = "
                    SELECT 
                        max_students,
                        session_start,
                        status,
                        (SELECT COUNT(*) FROM AppointmentStudent WHERE booked_id = ?) AS current_count
                    FROM Booked
                    WHERE booked_id = ?
                    FOR UPDATE
                ";
                $capStmt = mysqli_prepare($conn, $capSql);
                mysqli_stmt_bind_param($capStmt, "ii", $booked_id, $booked_id);
                mysqli_stmt_execute($capStmt);
                $capRes = mysqli_stmt_get_result($capStmt);
                $sessionRow = mysqli_fetch_assoc($capRes);
                mysqli_stmt_close($capStmt);

                if (!$sessionRow) {
                    $errors[] = "Session not found.";
                } else {
                    if ($sessionRow['status'] !== 'scheduled') {
                        $errors[] = "This session is not open for registration.";
                    }

                    $startDT = new DateTime($sessionRow['session_start']);
                    $nowDT   = new DateTime('now');
                    if ($startDT <= $nowDT) {
                        $errors[] = "This session has already started.";
                    }

                    $max_students  = (int)$sessionRow['max_students'];
                    $current_count = (int)$sessionRow['current_count'];

                    if ($current_count >= $max_students) {
                        $errors[] = "This session is full.";
                    }

                    if (empty($errors)) {
                        $checkSql = "
                            SELECT 1 FROM AppointmentStudent 
                            WHERE booked_id = ? AND user_id = ?
                        ";
                        $checkStmt = mysqli_prepare($conn, $checkSql);
                        mysqli_stmt_bind_param($checkStmt, "ii", $booked_id, $current_user_id);
                        mysqli_stmt_execute($checkStmt);
                        mysqli_stmt_store_result($checkStmt);

                        if (mysqli_stmt_num_rows($checkStmt) > 0) {
                            $errors[] = "You have already joined this session.";
                        }
                        mysqli_stmt_close($checkStmt);
                    }
                }

                if (empty($errors)) {
                    $joinSql = "
                        INSERT INTO AppointmentStudent (booked_id, user_id, booked_at)
                        VALUES (?, ?, NOW())
                    ";
                    $joinStmt = mysqli_prepare($conn, $joinSql);
                    mysqli_stmt_bind_param($joinStmt, "ii", $booked_id, $current_user_id);
                    mysqli_stmt_execute($joinStmt);
                    mysqli_stmt_close($joinStmt);

                    mysqli_commit($conn);
                    $success = "You have joined the session.";
                } else {
                    mysqli_rollback($conn);
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = "Unable to join this session. Please try again.";
            }
        }
    }
}

// Fetch upcoming sessions students can join (only scheduled)
$sessions = [];
$sessionSql = "
    SELECT 
        b.booked_id,
        b.session_start,
        b.session_end,
        b.max_students,
        b.status,
        s.subject_name,
        u.first_name AS tutor_first,
        u.last_name  AS tutor_last,
        (
            SELECT COUNT(*) 
            FROM AppointmentStudent a 
            WHERE a.booked_id = b.booked_id
        ) AS current_students
    FROM Booked b
    INNER JOIN Subject s ON b.subject_id = s.subject_id
    INNER JOIN Users u   ON b.user_id = u.user_id
    WHERE b.session_start >= NOW()
      AND b.status = 'scheduled'
    ORDER BY b.session_start ASC
";
$sessionResult = mysqli_query($conn, $sessionSql);
while ($row = mysqli_fetch_assoc($sessionResult)) {
    $sessions[] = $row;
}
mysqli_free_result($sessionResult);

// data for JS
$js_tutor_subjects     = $tutor_subjects;
$js_tutor_availability = $tutor_availability;
$js_subjects_by_id     = $subjectsById;

$js_tutors = [];
foreach ($tutors as $t) {
    $js_tutors[] = [
        'user_id'   => (int)$t['user_id'],
        'full_name' => $t['first_name'] . ' ' . $t['last_name'],
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Schedule | Tutoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container page-wrapper">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-glass p-4 h-100">
                <h2 class="h4 mb-2 section-title">Request a 1-on-1 session</h2>
                <p class="small text-muted mb-3">
                    Choose a tutor, then pick one of their subjects and a time that works for you.
                </p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><?php echo htmlspecialchars($err); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success && empty($errors)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="schedule.php" class="vstack gap-3">
                    <input type="hidden" name="action" value="request_1on1">

                    <div>
                        <label for="tutor_id" class="form-label">Tutor</label>
                        <select id="tutor_id" name="tutor_id" class="form-select" required>
                            <option value="">Select a tutor</option>
                            <?php foreach ($tutors as $t): ?>
                                <option value="<?php echo (int)$t['user_id']; ?>">
                                    <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="subject_id" class="form-label">Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" required>
                            <option value="">Select a tutor first</option>
                        </select>
                        <div class="small text-muted mt-1">
                            This list shows only subjects offered by the selected tutor.
                        </div>
                    </div>

                    <div>
                        <label for="session_date" class="form-label">Date</label>
                        <input
                            type="date"
                            class="form-control"
                            id="session_date"
                            name="session_date"
                            min="<?php echo htmlspecialchars($today_str); ?>"
                            required
                        >
                        <div class="small text-muted mt-1">
                            Choose a weekday on or after today.
                        </div>
                    </div>

                    <div>
                        <label for="session_time" class="form-label">Time</label>
                        <select
                            class="form-select"
                            id="session_time"
                            name="session_time"
                            required
                        >
                            <option value="">Select a time</option>
                            <?php foreach ($time_slots as $slot): ?>
                                <option value="<?php echo $slot; ?>">
                                    <?php echo date('g:i A', strtotime($slot)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small text-muted mt-1">
                            Available between 8:00 AM and 8:00 PM in 15-minute increments.
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-control"
                            placeholder="Briefly describe what you’d like to work on."
                        ></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            Request 1-on-1 Session
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <!-- Subject / time based view -->
            <div class="card-glass p-4 mb-4">
                <h2 class="h5 mb-1 section-title">Tutors available for this subject &amp; time</h2>
                <p class="small text-muted mb-3">
                    After choosing a tutor and subject, you can see which tutors teach that subject and when they’re available.
                </p>
                <div id="tutor_suggestions" class="small text-muted">
                    No tutors to display.
                </div>
            </div>

            <!-- Selected tutor weekly availability -->
            <div class="card-glass p-4 mb-4">
                <h2 class="h5 mb-1 section-title">Selected tutor availability</h2>
                <p class="small text-muted mb-3">
                    View the weekly availability for the tutor you selected.
                </p>
                <div id="single_tutor_availability" class="small text-muted">
                    Select a tutor to view availability.
                </div>
            </div>

            <!-- Upcoming sessions -->
            <div class="card-glass p-4 h-100">
                <h2 class="h4 mb-1 section-title">Upcoming tutor sessions</h2>
                <p class="small text-muted mb-4">
                    Join an existing scheduled session hosted by a tutor, if there is space.
                </p>

                <?php if (empty($sessions)): ?>
                    <p class="small text-muted mb-0">
                        There are no scheduled sessions right now.
                    </p>
                <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($sessions as $s): ?>
                            <?php
                            $start   = new DateTime($s['session_start']);
                            $end     = new DateTime($s['session_end']);
                            $is_full = $s['current_students'] >= $s['max_students'];
                            ?>
                            <div class="p-3 rounded-3 border border-secondary-subtle">
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($s['subject_name']); ?>
                                </div>
                                <div class="small text-muted">
                                    Tutor: <?php echo htmlspecialchars($s['tutor_first'] . ' ' . $s['tutor_last']); ?>
                                </div>
                                <div class="small text-muted">
                                    <?php echo $start->format('D, M j'); ?> •
                                    <?php echo $start->format('g:i A'); ?> – <?php echo $end->format('g:i A'); ?>
                                </div>
                                <div class="mt-2 d-flex align-items-center justify-content-between">
                                    <div class="small">
                                        <span class="badge bg-secondary-subtle text-dark rounded-pill">
                                            <?php echo $s['current_students'] . ' / ' . $s['max_students']; ?> students
                                        </span>
                                        <?php if ((int)$s['max_students'] === 1): ?>
                                            <span class="badge bg-info ms-2 rounded-pill">1-on-1</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary ms-2 rounded-pill">Group</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($is_full): ?>
                                        <span class="badge bg-danger">Full</span>
                                    <?php else: ?>
                                        <form method="post" action="schedule.php" class="mb-0">
                                            <input type="hidden" name="action" value="join_session">
                                            <input type="hidden" name="booked_id" value="<?php echo (int)$s['booked_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-light">
                                                Join
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const tutorSubjects = <?php echo json_encode($js_tutor_subjects); ?>;
    const tutorAvail    = <?php echo json_encode($js_tutor_availability); ?>;
    const subjectsById  = <?php echo json_encode($js_subjects_by_id); ?>;
    const allTutors     = <?php echo json_encode($js_tutors); ?>;

    const tutorSelect   = document.getElementById('tutor_id');
    const subjectSelect = document.getElementById('subject_id');
    const dateInput     = document.getElementById('session_date');
    const timeInput     = document.getElementById('session_time');
    const suggestions   = document.getElementById('tutor_suggestions');
    const tutorAvailDiv = document.getElementById('single_tutor_availability');

    const dayNames = {
        1: 'Monday',
        2: 'Tuesday',
        3: 'Wednesday',
        4: 'Thursday',
        5: 'Friday',
        6: 'Saturday',
        7: 'Sunday'
    };

    function resetSubjectOptions(message) {
        subjectSelect.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.disabled = true;
        opt.selected = true;
        opt.textContent = message;
        subjectSelect.appendChild(opt);
    }

    function formatTimeLabel(hhmm) {
        var parts = hhmm.split(':');
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        var d = new Date(2000, 0, 1, h, m);
        return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function renderTutorWeeklyAvailability(tutorId) {
        if (!tutorAvailDiv) return;

        if (!tutorId) {
            tutorAvailDiv.textContent = 'Select a tutor to view availability.';
            return;
        }

        const slots = tutorAvail[tutorId] || [];
        if (!slots.length) {
            tutorAvailDiv.textContent = 'No availability set for this tutor.';
            return;
        }

        const byDay = {};
        slots.forEach(function (slot) {
            const d = slot.day_of_week;
            if (!byDay[d]) byDay[d] = [];
            byDay[d].push(slot);
        });

        Object.keys(byDay).forEach(function (d) {
            byDay[d].sort(function (a, b) {
                return a.start_time.localeCompare(b.start_time);
            });
        });

        tutorAvailDiv.innerHTML = '';
        Object.keys(byDay).sort(function (a, b) { return a - b; }).forEach(function (d) {
            const dayName = dayNames[d] || ('Day ' + d);
            const ranges = byDay[d].map(function (slot) {
                return formatTimeLabel(slot.start_time) + ' – ' + formatTimeLabel(slot.end_time);
            });
            const line = document.createElement('div');
            line.textContent = dayName + ': ' + ranges.join(', ');
            tutorAvailDiv.appendChild(line);
        });
    }

    // Tutor selected -> populate ONLY their subjects
    tutorSelect.addEventListener('change', function () {
        const tutorId = tutorSelect.value;

        if (!tutorId) {
            resetSubjectOptions('Select a tutor first');
            renderTutorWeeklyAvailability(null);
            updateSuggestions();
            return;
        }

        if (!tutorSubjects[tutorId]) {
            resetSubjectOptions('No subjects available for this tutor');
        } else {
            resetSubjectOptions('Select a subject');
            tutorSubjects[tutorId].forEach(function (sid) {
                const subj = subjectsById[sid];
                if (!subj) return;
                const opt = document.createElement('option');
                opt.value = sid;
                opt.textContent = subj.department + " " + subj.level + " – " + subj.subject_name;
                subjectSelect.appendChild(opt);
            });
        }

        renderTutorWeeklyAvailability(tutorId);
        updateSuggestions();
    });

    // Prevent Sat/Sun: snap to next Monday
    dateInput.addEventListener('change', function () {
        if (!dateInput.value) return;
        var d   = new Date(dateInput.value + "T00:00:00");
        var dow = d.getDay(); // 0 = Sun, 6 = Sat

        if (dow === 6) {
            d.setDate(d.getDate() + 2);
        } else if (dow === 0) {
            d.setDate(d.getDate() + 1);
        }

        dateInput.value = d.toISOString().slice(0, 10);
        updateSuggestions();
    });

    subjectSelect.addEventListener('change', updateSuggestions);
    timeInput.addEventListener('change', updateSuggestions);

    function updateSuggestions() {
        var subjectId = parseInt(subjectSelect.value || '0', 10);
        var dateVal   = dateInput.value;
        var timeVal   = timeInput.value;

        if (!subjectId) {
            suggestions.textContent = 'No tutors to display.';
            return;
        }

        var subjInfo = subjectsById[subjectId] || null;
        var subjLabel = subjInfo
            ? (subjInfo.department + ' ' + subjInfo.level + ' – ' + subjInfo.subject_name)
            : 'this subject';

        // If date/time not chosen: show all tutors for this subject and their weekly availability
        if (!dateVal || !timeVal) {
            var matches = [];

            allTutors.forEach(function (t) {
                var tid = t.user_id.toString();
                if (!tutorSubjects[tid] || tutorSubjects[tid].indexOf(subjectId) === -1) {
                    return;
                }
                var slots = tutorAvail[tid] || [];
                matches.push({ name: t.full_name, slots: slots });
            });

            if (matches.length === 0) {
                suggestions.textContent = 'No tutors for ' + subjLabel + ' yet.';
                return;
            }

            suggestions.innerHTML = '';
            matches.forEach(function (m) {
                var container = document.createElement('div');
                container.className = 'mb-3';
                var title = document.createElement('div');
                title.innerHTML = '<strong>' + m.name + '</strong>';
                container.appendChild(title);

                if (!m.slots.length) {
                    var line = document.createElement('div');
                    line.className = 'text-muted';
                    line.textContent = 'No availability set.';
                    container.appendChild(line);
                } else {
                    var byDay = {};
                    m.slots.forEach(function (slot) {
                        var d = slot.day_of_week;
                        if (!byDay[d]) byDay[d] = [];
                        byDay[d].push(slot);
                    });
                    Object.keys(byDay).forEach(function (d) {
                        byDay[d].sort(function (a, b) {
                            return a.start_time.localeCompare(b.start_time);
                        });
                    });
                    Object.keys(byDay).sort(function (a, b) { return a - b; }).forEach(function (d) {
                        var dayName = dayNames[d] || ('Day ' + d);
                        var ranges = byDay[d].map(function (slot) {
                            return formatTimeLabel(slot.start_time) + ' – ' + formatTimeLabel(slot.end_time);
                        });
                        var line = document.createElement('div');
                        line.className = 'text-muted';
                        line.textContent = dayName + ': ' + ranges.join(', ');
                        container.appendChild(line);
                    });
                }

                suggestions.appendChild(container);
            });

            return;
        }

        // subject + date + time selected: show tutors available at that exact time
        var d = new Date(dateVal + "T00:00:00");
        var dayN   = d.getDay();          // 0..6
        var dayPHP = (dayN === 0) ? 7 : dayN; // 1..7

        var exactMatches = [];

        allTutors.forEach(function (t) {
            var tid = t.user_id.toString();

            if (!tutorSubjects[tid] || tutorSubjects[tid].indexOf(subjectId) === -1) {
                return;
            }

            var slots      = tutorAvail[tid] || [];
            var available  = false;
            var untilTime  = null;

            slots.forEach(function (slot) {
                if (slot.day_of_week === dayPHP &&
                    timeVal >= slot.start_time &&
                    timeVal <= slot.end_time) {
                    available = true;
                    untilTime = slot.end_time;
                }
            });

            if (available) {
                exactMatches.push({ name: t.full_name, until: untilTime });
            }
        });

        if (exactMatches.length === 0) {
            suggestions.textContent = 'No tutors for ' + subjLabel + ' are available at that time.';
            return;
        }

        suggestions.innerHTML = '';
        exactMatches.forEach(function (m) {
            var div = document.createElement('div');
            div.className = 'mb-2';
            div.innerHTML = '<strong>' + m.name +
                '</strong><br><span class="text-muted">Available until ' +
                formatTimeLabel(m.until) + '</span>';
            suggestions.appendChild(div);
        });
    }
</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
</body>
</html>
