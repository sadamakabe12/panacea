<?php 
include "header.php";
?>

<?php

    $selecttype="Мои";
    $current="Только о моих пациентах";
    if($_POST){

        if(isset($_POST["search"])){
            $keyword=$_POST["search12"];
            /*TODO: make and understand */
            $sqlmain= "select * from patient where pemail='$keyword' or pname='$keyword' or pname like '$keyword%' or pname like '%$keyword' or pname like '%$keyword%' ";
            $selecttype="my";
        }
        
        if(isset($_POST["filter"])){
            if($_POST["showonly"]=='all'){
                $sqlmain= "select * from patient";
                $selecttype="All";
                $current="All patients";
            }else{
                $sqlmain= "select * from appointment inner join patient on patient.pid=appointment.pid inner join schedule on schedule.scheduleid=appointment.scheduleid where schedule.docid=$userid;";
                $selecttype="Мои";
                $current="Только о моих пациентах";
            }
        }
    }else{
        $sqlmain= "select * from appointment inner join patient on patient.pid=appointment.pid inner join schedule on schedule.scheduleid=appointment.scheduleid where schedule.docid=$userid;";
        $selecttype="Мои";
    }

?>

<div class="dash-body">
    <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
        <tr>
            <td width="13%">
            <a href="javascript:history.back()" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-top:11px;padding-bottom:11px;margin-left:20px;width:125px"><font class="tn-in-text">Назад</font></button></a>

            </td>
            <td>
                
                <form action="" method="post" class="header-search">

                    <input type="search" name="search12" class="input-text header-searchbar" placeholder="Поиск по имени пациента или по электронной почте" list="patient">&nbsp;&nbsp;
                    
                    <?php
                        echo '<datalist id="patient">';
                        $list11 = $database->query($sqlmain);
                       //$list12= $database->query("select * from appointment inner join patient on patient.pid=appointment.pid inner join schedule on schedule.scheduleid=appointment.scheduleid where schedule.docid=1;");

                        for ($y=0;$y<$list11->num_rows;$y++){
                            $row00=$list11->fetch_assoc();
                            $d=$row00["pname"];
                            $c=$row00["pemail"];
                            echo "<option value='$d'><br/>";
                            echo "<option value='$c'><br/>";
                        };

                    echo ' </datalist>';
?>
                    <input type="Submit" value="Поиск" name="search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                
                </form>
                
            </td>
            <td width="15%">
                <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                Сегодняшняя дата
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php 
                        date_default_timezone_set('Asia/Yekaterinburg');

                        $today = date('d-m-Y');
                        echo $today;
                ?>
                </p>
            </td>
            <td width="10%">
                <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
            </td>


        </tr>
       
        
        <tr>
            <td colspan="4" style="padding-top:10px;">
                <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)"><?php echo $selecttype." пациенты (".$list11->num_rows.")"; ?></p>
            </td>
            
        </tr>
        <tr>
            <td colspan="4" style="padding-top:0px;width: 100%;" >
                <center>
                <table class="filter-container" border="0" >

                <form action="" method="post">
                
                <td  style="text-align: right;">
                Показать детали о : &nbsp;
                </td>
                <td width="30%">
                <select name="showonly" id="" class="box filter-container-items" style="width:90% ;height: 37px;margin: 0;" >
                            <option value="" disabled selected hidden><?php echo $current   ?></option><br/>
                            <option value="my">Мои пациенты Only</option><br/>
                            <option value="all">All Пациенты</option><br/>
                            

                </select>
            </td>
            <td width="12%">
                <input type="submit"  name="filter" value=" Фильтр" class=" btn-primary-soft btn button-icon btn-filter"  style="padding: 15px; margin :0;width:100%">
                </form>
            </td>

            </tr>
                    </table>

                </center>
            </td>
            
        </tr>
          
        <tr>
           <td colspan="4">
               <center>
                <div class="abc scroll">
                <table width="93%" class="sub-table scrolldown"  style="border-spacing:0;">
                <thead>
                <tr>
                        <th class="table-headin">
                            
                        
                        Имя
                        
                        </th>
                        
                    
                        Номер телефона
                        
                        </th>
                        <th class="table-headin">
                            Email
                        </th>
                        <th class="table-headin">
                            
                            Дата рождения
                            
                        </th>
                        <th class="table-headin">
                            
                            Действия
                            
                        </tr>
                </thead>
                <tbody>
                
                    <?php

                        
                        $result= $database->query($sqlmain);
                        //echo $sqlmain;
                        if($result->num_rows==0){
                            echo '<tr>
                            <td colspan="4">
                            <br><br><br><br>
                            <center>
                            <img src="../img/notfound.svg" width="25%">
                            
                            <br>
                            <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">Мы не смогли найти ничего, связанного с вашими ключевыми словами!</p>
                            <a class="non-style-link" href="patient.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;">&nbsp; Показать всех пациентов &nbsp;</font></button>
                            </a>
                            </center>
                            <br><br><br><br>
                            </td>
                            </tr>';
                            
                        }
                        else{
                        for ( $x=0; $x<$result->num_rows;$x++){
                            $row=$result->fetch_assoc();
                            $pid=$row["pid"];
                            $name=$row["pname"];
                            $email=$row["pemail"];
                            $nic=$row["pnic"];
                            $dob=$row["pdob"];
                            $tel=$row["ptel"];
                            
                            echo '<tr>
                                <td> &nbsp;'.
                                substr($name,0,35)
                                .'</td>
                                <td>
                                '.substr($nic,0,12).'
                                </td>
                                <td>
                                    '.substr($tel,0,10).'
                                </td>
                                <td>
                                '.substr($email,0,20).'
                                 </td>
                                <td>
                                '.substr($dob,0,10).'
                                </td>
                                <td >
                                <div style="display:flex;justify-content: center;">
                                
                                <a href="?action=view&id='.$pid.'" class="non-style-link"><button  class="btn-primary-soft btn button-icon btn-view"  style="padding-left: 40px;padding-top: 12px;padding-bottom: 12px;margin-top: 10px;"><font class="tn-in-text">Подробности</font></button></a>
                               
                                </div>
                                </td>
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
               
                
                
    </table>
</div>

<?php 
if($_GET){
    
        $id=$_GET["id"];
        $action=$_GET["action"];
        $sqlmain= "select * from patient where pid=?";
        $stmt = $database->prepare($sqlmain);
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row=$result->fetch_assoc();
        $name=$row["pname"];
        $email=$row["pemail"];
        $nic=$row["pnic"];
        $dob=$row["pdob"];
        $tele=$row["ptel"];
        $address=$row["paddress"];
        echo '
        <div id="popup1" class="overlay">
                <div class="popup">
                <center>
                    <a class="close" href="patient.php">&times;</a>
                    <div class="content">

                    </div>
                    <div style="display: flex;justify-content: center;">
                    <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                    
                        <tr>
                            <td>
                                <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Просмотр деталей</p><br><br>
                            </td>
                        </tr>
                        <tr>
                            
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Patient ID: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                P-'.$id.'<br><br>
                            </td>
                            
                        </tr>
                        
                        <tr>
                            
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Имя: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                '.$name.'<br><br>
                            </td>
                            
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Email" class="form-label">Email: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                            '.$email.'<br><br>
                            </td>
                        </tr>

                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Tele" class="form-label">Номер телефона: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                            '.$tele.'<br><br>
                            </td>
                        </tr>
                        <tr>
                            
                            <td class="label-td" colspan="2">
                                <label for="name" class="form-label">Дата рождения: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                '.$dob.'<br><br>
                            </td>
                            
                        </tr>
                        <tr>
                            <td colspan="2">
                                <a href="patient.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                            
                                
                            </td>
            
                        </tr>
                       

                    </table>
                    </div>
                </center>
                <br><br>
        </div>
        </div>
        ';
    
};

?>

<?php include "footer.php"; ?>