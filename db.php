<?php
#    // Connect to the database

    $hostname='localhost';
    $username='soundche_dj';
    $password='ZT&Qur0x&%$#';

    try {
        $dbh = new PDO("mysql:host=$hostname;dbname=soundche_radio",$username,$password);

        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
# // <== add this line
//   echo 'Connected to Database<br/>';


    } catch(PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }


 # //  $dbh = null;
?>
