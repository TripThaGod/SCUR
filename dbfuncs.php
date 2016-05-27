<?php

    // Connect to database
    include 'try.php';

    // Tail last line of a file
    function tailShell($filepath, $lines = 1) {
        ob_start();
        passthru('tail -'  . $lines . ' ' . escapeshellarg($filepath));
        return trim(ob_get_clean());
    }
    // Check if songs exists in the database  
    function songExists($title){
        global $dbh;
        $stmt = $dbh->prepare("SELECT * FROM scur_shoutcast_playlist WHERE Title = :cTitle");
        $stmt->bindParam(':cTitle',$title);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = htmlentities($row['ID']);
            $artist = htmlentities($row['Artist']);
            $songtitle = htmlentities($row['Title']);
            $uploaded = htmlentities($row['Uploaded']);
            $played = htmlentities($row['Played']);
            $genre = htmlentities($row['Genre']);
            $votes = htmlentities($row['Votes']);
            $grade = htmlentities($row['Grade']);
            $count = htmlentities($row['Count']);
            $email = htmlentities($row['Email']); 
            // echo $title . "\n Song Located!";
        }
        // If the song exists, update Last, Count. If the song does not exist, add it to the database.
        if ($title == $songtitle){
            return 1;
        }
    }

    function nowPlaying($title){
        global $dbh;
        $last = date("Y-m-d G:i:s");
        $stmt = $dbh->prepare("UPDATE `scur_shoutcast_playlist` SET `last` = :last, `count` = count + 1 WHERE `title` = :cTitle");
        $stmt->bindParam(':cTitle',$title);
        $stmt->bindParam(':last',$last);
        $stmt->execute();
    }

    function newSong($artist,$title){
        global $dbh;
        $last = date("Y-m-d G:i:s");
        $aired = date("Y-m-d G:i:s");
        $genre = "Rap";
        $votes = "0";
        $count = "1";
        $stmt = $dbh->prepare("INSERT INTO `scur_shoutcast_playlist` SET `artist` = :cArtist, `title` = :cTitle, `last` = :last, `aired` = :aired, `genre` = :genre, `votes` = :votes, `count` = :count");
        $stmt->execute(array(
            ':cArtist' => $artist,
            ':cTitle' => $title,
            ':last' => $last,
            ':aired' => $aired,
            ':genre' => $genre,
            ':votes' => $votes,
            ':count' => $count,
        ));
    }
    
    function getTotalSongs(){
         global $dbh;
         $stmt = $dbh->prepare("SELECT COUNT(*) FROM scur_shoutcast_playlist");
         $stmt->execute(); 
         $totalsongs = $stmt->fetchColumn();
         return $totalsongs;
    }
    
    function getAlbumArt($title){
        // Generate fresh date for album art download
                $fresh = date("G-i-s");
                // Grab album art from the song that is currently playing
                $ch = curl_init('http://soundcheck.xyz:8000/playingart?sid=1?'.$fresh);
                $fp = fopen('/home/soundcheck/public_html/images/artwork/artwork.jpg', 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);
    }
    
    
  
?>