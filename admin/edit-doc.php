<?php include "header.php"; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $oldemail = $_POST['oldemail'];
    $spec = isset($_POST['spec']) ? $_POST['spec'] : array();
    $email = $_POST['email'];
    $tele = $_POST['Tele'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $cpassword = isset($_POST['cpassword']) ? $_POST['cpassword'] : '';
    $id = $_POST['id00'];

    // Если пароль не введен или оба поля пустые, не обновляем пароль
    $password_check = empty($password) && empty($cpassword);
    
    if ($password_check || $password == $cpassword) {
        $result = $database->query("SELECT doctor.docid FROM doctor INNER JOIN webuser ON doctor.docemail=webuser.email WHERE webuser.email='$email';");
        if ($result->num_rows == 1) {
            $id2 = $result->fetch_assoc()["docid"];
        } else {
            $id2 = $id;
        }
        if ($id2 != $id) {
            $error = '1';
        } else {
            // Формируем запрос на обновление в зависимости от того, меняем ли мы пароль
            if ($password_check) {
                $sql1 = "UPDATE doctor SET docemail='$email',docname='$name',doctel='$tele' WHERE docid=$id;";
            } else {
                $sql1 = "UPDATE doctor SET docemail='$email',docname='$name',docpassword='$password',doctel='$tele' WHERE docid=$id;";
            }
            $database->query($sql1);
            $sql1 = "UPDATE webuser SET email='$email' WHERE email='$oldemail';";
            $database->query($sql1);
            // Обновляем doctor_specialty
            $database->query("DELETE FROM doctor_specialty WHERE docid=$id");
            if (!empty($spec)) {
                foreach ($spec as $spec_id) {
                    $spec_id = intval($spec_id);
                    $database->query("INSERT INTO doctor_specialty (docid, specialty_id) VALUES ($id, $spec_id)");
                }
            }
            $error = '4';
        }
    } else {
        $error = '2';
    }
    header("location: doctors.php?action=edit&error=" . $error . "&id=" . $id);
    exit();
}
?>

<!-- Здесь может быть форма или сообщение, если нужно -->

<?php include "footer.php"; ?>