<?php

$courseCode = isset($_GET['course']) ? $_GET['course'] : '';
$unitCode = isset($_GET['unit']) ? $_GET['unit'] : '';

$coursename = "";
if (!empty($courseCode)) {
    $coursename_query = "SELECT name FROM tblcourse WHERE courseCode = '$courseCode'";
    $result = fetch($coursename_query);
    if ($result) {
        foreach ($result as $row) {
            $coursename = $row['name'];
        }
    }
}
$unitname = "";
if (!empty($unitCode)) {
    $unitname_query = "SELECT name FROM tblunit WHERE unitCode = '$unitCode'";
    $result = fetch($unitname_query);
    if ($result) {
        foreach ($result as $row)
            $unitname = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lecture Dashboard</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/topbar.php'; ?>
<section class="main">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main--content">
        <form class="lecture-options" id="selectForm">
            <select required name="course" id="courseSelect" onChange="updateTable()">
                <option value="" selected>Select Course</option>
                <?php
                $courseNames = getCourseNames();
                foreach ($courseNames as $course) {
                    echo '<option value="' . $course["courseCode"] . '">' . $course["name"] . '</option>';
                }
                ?>
            </select>
            <select required name="unit" id="unitSelect" onChange="updateTable()">
                <option value="" selected>Select Unit</option>
                <?php
                $unitNames = getUnitNames();
                foreach ($unitNames as $unit) {
                    echo '<option value="' . $unit["unitCode"] . '">' . $unit["name"] . '</option>';
                }
                ?>
            </select>
        </form>

        <button class="add" onclick="exportTableToExcel('attendaceTable', '<?php echo $unitCode . '_on_' . date('Y-m-d'); ?>','<?php echo $coursename ?>', '<?php echo $unitname ?>')">Export Attendance As Excel</button>

        <div class="table-container">
            <div class="title">
                <h2 class="section--title">Attendance Preview</h2>
            </div>
            <div class="table attendance-table" id="attendaceTable">
                <table>
                    <thead>
                    <tr>
                        <th>Registration No</th>
                        <th>Student Name</th>
                        <?php
                        // Get all unique dates
                        $distinctDatesQuery = "SELECT DISTINCT dateMarked FROM tblattendance WHERE course = :courseCode AND unit = :unitCode ORDER BY dateMarked";
                        $stmtDates = $pdo->prepare($distinctDatesQuery);
                        $stmtDates->execute([
                            ':courseCode' => $courseCode,
                            ':unitCode' => $unitCode,
                        ]);
                        $dateRows = $stmtDates->fetchAll(PDO::FETCH_ASSOC);

                        $dateList = [];
                        foreach ($dateRows as $row) {
                            $date = $row['dateMarked'];
                            $dateList[] = $date;
                            echo "<th>$date</th>";
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Get all students with attendance records for this course & unit
                    $studentsQuery = "
                        SELECT DISTINCT s.registrationNumber, s.firstName, s.lastName 
                        FROM tblattendance a
                        JOIN tblstudents s ON s.registrationNumber = a.studentRegistrationNumber
                        WHERE a.course = :courseCode AND a.unit = :unitCode
                        ORDER BY s.firstName, s.lastName
                    ";
                    $stmtStudents = $pdo->prepare($studentsQuery);
                    $stmtStudents->execute([
                        ':courseCode' => $courseCode,
                        ':unitCode' => $unitCode,
                    ]);
                    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($students as $student) {
                        echo "<tr>";
                        echo "<td>" . $student['registrationNumber'] . "</td>";
                        echo "<td>" . $student['firstName'] . " " . $student['lastName'] . "</td>";

                        foreach ($dateList as $date) {
                            $attendanceQuery = "SELECT attendanceStatus FROM tblattendance 
                                WHERE studentRegistrationNumber = :studentRegistrationNumber 
                                AND dateMarked = :date 
                                AND course = :courseCode 
                                AND unit = :unitCode";
                            $stmtAttendance = $pdo->prepare($attendanceQuery);
                            $stmtAttendance->execute([
                                ':studentRegistrationNumber' => $student['registrationNumber'],
                                ':date' => $date,
                                ':courseCode' => $courseCode,
                                ':unitCode' => $unitCode,
                            ]);
                            $attendance = $stmtAttendance->fetch(PDO::FETCH_ASSOC);

                            $status = $attendance ? $attendance['attendanceStatus'] : "Absent";
                            echo "<td>$status</td>";
                        }

                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
</body>

<?php js_asset(["min/js/filesaver", "min/js/xlsx", "active_link"]) ?>
<script>
    function updateTable() {
        const courseSelect = document.getElementById("courseSelect");
        const unitSelect = document.getElementById("unitSelect");
        const selectedCourse = courseSelect.value;
        const selectedUnit = unitSelect.value;
        let url = "download-record";
        if (selectedCourse && selectedUnit) {
            url += "?course=" + encodeURIComponent(selectedCourse) + "&unit=" + encodeURIComponent(selectedUnit);
            window.location.href = url;
        }
    }

    function exportTableToExcel(tableId, filename = '', courseCode = '', unitCode = '') {
        const table = document.getElementById(tableId);
        const currentDate = new Date().toLocaleDateString();
        const headerContent = '<p style="font-weight:700;">Attendance for: ' + courseCode + ' Unit: ' + unitCode + ' On: ' + currentDate + '</p>';

        const tbody = document.createElement('tbody');
        const additionalRow = tbody.insertRow(0);
        const additionalCell = additionalRow.insertCell(0);
        additionalCell.innerHTML = headerContent;
        table.insertBefore(tbody, table.firstChild);

        const wb = XLSX.utils.table_to_book(table, { sheet: "Attendance" });
        const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'binary' });
        const blob = new Blob([s2ab(wbout)], { type: 'application/octet-stream' });

        if (!filename.toLowerCase().endsWith('.xlsx')) {
            filename += '.xlsx';
        }

        saveAs(blob, filename);
    }

    function s2ab(s) {
        const buf = new ArrayBuffer(s.length);
        const view = new Uint8Array(buf);
        for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
        return buf;
    }
</script>

</html>
