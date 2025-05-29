<?php 
include "header.php";
?>

<?php
// Поиск
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST["search"])) {
    $keyword = $_POST["search"];
    $sqlmain = "SELECT d.docid, d.docname, d.docemail, GROUP_CONCAT(s.sname SEPARATOR ', ') as specialties FROM doctor d LEFT JOIN doctor_specialty ds ON d.docid = ds.docid LEFT JOIN specialties s ON ds.specialty_id = s.id WHERE d.docemail=? OR d.docname=? OR d.docname LIKE CONCAT(?,'%') OR d.docname LIKE CONCAT('%',?) OR d.docname LIKE CONCAT('%',?,'%') GROUP BY d.docid ORDER BY d.docid DESC";
    $stmt = $database->prepare($sqlmain);
    $stmt->bind_param("sssss", $keyword, $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sqlmain = "SELECT d.docid, d.docname, d.docemail, GROUP_CONCAT(s.sname SEPARATOR ', ') as specialties FROM doctor d LEFT JOIN doctor_specialty ds ON d.docid = ds.docid LEFT JOIN specialties s ON ds.specialty_id = s.id GROUP BY d.docid ORDER BY d.docid DESC";
    $result = $database->query($sqlmain);
}
?>

<tr>
    <td colspan="4" style="padding-top:10px;">
        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">Все врачи (<?php echo $result->num_rows; ?>)</p>
    </td>
</tr>

<tr>
   <td colspan="4">
       <center>
        <div class="abc scroll">
        <table width="93%" class="sub-table scrolldown" border="0">
        <thead>
        <tr>
                <th class="table-headin">Имя врача</th>
                <th class="table-headin">Электронная почта</th>
                <th class="table-headin">Специальность</th>
                <th class="table-headin">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows == 0) {
            echo '<tr><td colspan="4"><br><br><br><br><center><img src="../img/notfound.svg" width="25%"><br><p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p><a class="non-style-link" href="doctors.php"><button class="login-btn btn-primary-soft btn" style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех врачей &nbsp;</button></a></center><br><br><br><br></td></tr>';
        } else {
            while ($row = $result->fetch_assoc()) {
                $docid = $row["docid"];
                $name = $row["docname"];
                $email = $row["docemail"];
                $spcil_name = $row["specialties"] ? $row["specialties"] : '<span style="color:#888">Не указано</span>';
                echo '<tr>
                    <td>&nbsp;'.htmlspecialchars($name).'</td>
                    <td>'.htmlspecialchars($email).'</td>
                    <td>'.htmlspecialchars($spcil_name).'</td>
                    <td><div style="display:flex;justify-content: center;">
                        <a href="?action=view&id='.$docid.'" class="non-style-link"><button class="btn-primary-soft btn button-icon btn-view" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Просмотреть</font></button></a>&nbsp;&nbsp;&nbsp;
                        <a href="?action=session&id='.$docid.'&name='.urlencode($name).'" class="non-style-link"><button class="btn-primary-soft btn button-icon menu-icon-session-active" style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Записи</font></button></a>
                    </div></td>
                </tr>';
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        </center>
   </td> 
</tr>

<?php
if ($_GET) {
    $id = $_GET["id"] ?? null;
    $action = $_GET["action"] ?? null;
    if ($action == 'view' && $id) {
        $sqlmain = "SELECT d.docname, d.docemail, d.doctel, GROUP_CONCAT(s.sname SEPARATOR ', ') as specialties FROM doctor d LEFT JOIN doctor_specialty ds ON d.docid = ds.docid LEFT JOIN specialties s ON ds.specialty_id = s.id WHERE d.docid=? GROUP BY d.docid";
        $stmt = $database->prepare($sqlmain);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $name = $row["docname"];
        $email = $row["docemail"];
        $spcil_name = $row["specialties"] ? $row["specialties"] : '<span style="color:#888">Не указано</span>';
        $tele = $row["doctel"] ? $row["doctel"] : '<span style="color:#888">Не указано</span>';
        echo '
        <div id="popup1" class="overlay">
            <div class="popup">
                <center>
                    <h2></h2>
                    <a class="close" href="doctors.php">&times;</a>
                    <div class="content"><br></div>
                    <div style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <tr><td><p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Смотреть детали.</p><br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label for="name" class="form-label">Имя: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.htmlspecialchars($name).'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label for="Email" class="form-label">Email: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.htmlspecialchars($email).'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label for="Tele" class="form-label">Номер телефона: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.htmlspecialchars($tele).'<br><br></td></tr>
                            <tr><td class="label-td" colspan="2"><label for="spec" class="form-label">Специальность: </label></td></tr>
                            <tr><td class="label-td" colspan="2">'.htmlspecialchars($spcil_name).'<br><br></td></tr>
                            <tr><td colspan="2"><a href="doctors.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn"></a></td></tr>
                        </table>
                    </div>
                </center>
                <br><br>
            </div>
        </div>';
    } elseif ($action == 'session' && isset($_GET["name"])) {
        $name = $_GET["name"];
        echo '<script>window.location.href = "schedule.php?search=' . urlencode($name) . '";</script>';
        exit;
    }
}
?>

<?php include "footer.php"; ?>