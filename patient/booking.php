<?php 
include "header.php";
require_once "../includes/appointment_functions.php";

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"]=="" || $_SESSION['usertype']!='p') {
    header("location: ../login.php");
    exit;
}
$useremail = $_SESSION["user"];

// Get patient data using the function from appointment_functions.php
$userfetch = get_patient_by_email($database, $useremail);
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

date_default_timezone_set('Asia/Yekaterinburg');
$today = date('Y-m-d');
?>

<div class="dash-body">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
            <td width="13%">
                <a href="javascript:history.back()"><button class="login-btn btn-primary-soft btn btn-icon-back" style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>
            </td>
            <td>
                <form action="schedule.php" method="post" class="header-search">
                    <input type="search" name="search" class="input-text header-searchbar" placeholder="Поиск по имени врача или электронной почте или по дате (YYYY-MM-DD)" list="doctors" >&nbsp;&nbsp;
                    <?php
                        echo '<datalist id="doctors">';
                        $list11 = $database->query("SELECT DISTINCT docname FROM doctor;");
                        while ($row00 = $list11->fetch_assoc()) {
                            $d = $row00["docname"];
                            echo "<option value='$d'>";
                        }
                        echo ' </datalist>';
                    ?>
                    <input type="Submit" value="Поиск" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                </form>
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">Сегодняшняя дата</p>
                <p class="heading-sub12" style="padding: 0;margin: 0;">
                    <?php echo $today; ?>
                </p>
            </td>
            <td width="10%">
                <button class="btn-label" style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top:10px;width: 100%;" >
                <!-- Scheduled Sessions / Booking / Review Booking -->
            </td>
        </tr>
        <tr>
            <td colspan="4">
                <center>
                <div class="abc scroll">
                <table width="100%" class="sub-table scrolldown" border="0" style="padding: 50px;border:none">
                    <tbody>
                        <?php
                        if ($_GET && isset($_GET["id"])) {
                            $id = $_GET["id"];
                            $sqlmain = "SELECT * FROM schedule INNER JOIN doctor ON schedule.docid=doctor.docid WHERE schedule.scheduleid=? ORDER BY schedule.scheduledate DESC";
                            $stmt = $database->prepare($sqlmain);
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            if ($row) {
                                $scheduleid = $row["scheduleid"];
                                $docname = $row["docname"];
                                $docemail = $row["docemail"];
                                $scheduledate = $row["scheduledate"];
                                $scheduletime = $row["scheduletime"];
                                $sql2 = "SELECT * FROM appointment WHERE scheduleid=?";
                                $stmt2 = $database->prepare($sql2);
                                $stmt2->bind_param("i", $id);
                                $stmt2->execute();
                                $result12 = $stmt2->get_result();
                                $apponum = ($result12->num_rows) + 1;
                                echo '                                <form action="booking.php" method="post">
                                    <input type="hidden" name="scheduleid" value="'.$scheduleid.'" >
                                    <input type="hidden" name="apponum" value="'.$apponum.'" >
                                    <input type="hidden" name="date" value="'.$today.'" >
                                    <td style="width: 50%;" rowspan="2">
                                        <div class="dashboard-items search-items">                                                <div style="width:100%">
                                                <div class="h1-search" style="font-size:25px;">Детали Сеанса</div><br><br>
                                                <div class="h3-search" style="font-size:18px;line-height:30px">Имя врача:  &nbsp;&nbsp;<b>'.$docname.'</b><br></div>
                                                <div class="h3-search" style="font-size:18px;"></div><br>
                                                <div class="h3-search" style="font-size:18px;">Запланированное время записи: '.$scheduledate.'<br>Сеанс начинается : '.$scheduletime.'<br></div>
                                                <br>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 25%;">
                                        <div class="dashboard-items search-items">
                                            <div style="width:100%;padding-top: 15px;padding-bottom: 15px;">
                                                <div class="h1-search" style="font-size:20px;line-height: 35px;margin-left:8px;text-align:center;">Номер записи</div>
                                                <center>
                                                    <div class="dashboard-icons" style="margin-left: 0px;width:90%;font-size:70px;font-weight:800;text-align:center;color:var(--btnnictext);background-color: var(--btnice)">'.$apponum.'</div>
                                                </center>
                                            </div><br><br><br>
                                        </div>
                                    </td>
                                    </tr><tr>
                                    <td>
                                        <input type="Submit" class="login-btn btn-primary btn btn-book" style="margin-left:10px;padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;width:95%;text-align: center;" value="Записаться сейчас" name="booknow">
                                    </form>
                                    </td>
                                    </tr>';
                            }
                        }
                        
                        // Обработка запроса на бронирование
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["booknow"])) {
                            $apponum = $_POST["apponum"];
                            $scheduleid = $_POST["scheduleid"];
                            $date = $_POST["date"];
                            
                            // Используем централизованную функцию для завершения бронирования
                            if (complete_booking($database, $userid, $apponum, $scheduleid, $date)) {
                                header("location: appointment.php?action=booking-added&id=".$apponum."&titleget=none");
                            } else {
                                header("location: schedule.php?action=booking-failed");
                            }
                            exit;
                        }
                        ?>
                    </tbody>
                </table>
                </div>
                </center>
            </td> 
        </tr>
    </table>
</div>

<?php include "footer.php"; ?>