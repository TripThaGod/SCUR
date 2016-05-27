<?php

    start:

    // Start the session here so that we can pass date from the session to our ascii script
    session_start();
    date_default_timezone_set('America/Detroit');

    //echo "   SSSSSSSSSSSSSSS         CCCCCCCCCCCCCUUUUUUUU     UUUUUUUURRRRRRRRRRRRRRRRR";
    //echo " SS:::::::::::::::S     CCC::::::::::::CU::::::U     U::::::UR::::::::::::::::R";
    //echo "S:::::SSSSSS::::::S   CC:::::::::::::::CU::::::U     U::::::UR::::::RRRRRR:::::R";
    //echo "S:::::S     SSSSSSS  C:::::CCCCCCCC::::CUU:::::U     U:::::UURR:::::R     R:::::R";
    //echo "S:::::S             C:::::C       CCCCCC U:::::U     U:::::U   R::::R     R:::::R";
    //echo "S:::::S            C:::::C               U:::::D     D:::::U   R::::R     R:::::R";
    //echo " S::::SSSS         C:::::C               U:::::D     D:::::U   R::::RRRRRR:::::R ";
    //echo "  SS::::::SSSSS    C:::::C               U:::::D     D:::::U   R:::::::::::::RR";
    //echo "    SSS::::::::SS  C:::::C               U:::::D     D:::::U   R::::RRRRRR:::::R";
    //echo "       SSSSSS::::S C:::::C               U:::::D     D:::::U   R::::R     R:::::R";
    //echo "            S:::::SC:::::C               U:::::D     D:::::U   R::::R     R:::::R";
    //echo "            S:::::S C:::::C       CCCCCC U::::::U   U::::::U   R::::R     R:::::R";
    //echo "SSSSSSS     S:::::S  C:::::CCCCCCCC::::C U:::::::UUU:::::::U RR:::::R     R:::::R";
    //echo "S::::::SSSSSS:::::S   CC:::::::::::::::C  UU:::::::::::::UU  R::::::R     R:::::R";
    //echo "S:::::::::::::::SS      CCC::::::::::::C    UU:::::::::UU    R::::::R     R:::::R";
    //echo " SSSSSSSSSSSSSSS           CCCCCCCCCCCCC      UUUUUUUUU      RRRRRRRR     RRRRRRR";

    // Load MySQL database functions
    include 'dbfuncs.php';

    // Load Twitter libraries
    require "twitter/autoload.php";

    use Abraham\TwitterOAuth\TwitterOAuth;
    
    // Insert your keys/tokens for twitter app
    $consumerKey = 'd3PEc8Hayd6wAR7EYSy09m8p4';
    $consumerSecret = 'vfotpVFL6WWDxaHWb4jRjRa79b1jI4C6IjvDYYzsNgwbY28MfF';
    $accessToken = '2857497852-kPmwd5NqFEknuAq0U8193Ik7EDmr2Om6DJdwzaA';
    $accessTokenSecret = 'EUU47LGeBwkNRzRzXBkaYIrTazcH9ni0eqo5NLW2Stk8o';

    // Create new instance of Twitter and create variables for generated tweet.

    $twitter = new TwitterOAuth($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
    $front = "♫ #NowPlaying: ";
    $back = "♫ #ComingUp: ";
    $tag = (' @ soundcheck.xyz #SCUR #Radio #TBT');
    $line = '';

    // Watch log for song change event

    $line = tailShell('/home/scur/SHOUTcast/scripts/log/SHOUTcast.log',1);

    $needle = "now";
    // Find the word now in the logs and extract the song information from the same line

    if (strpos($line,$needle) !== false) {
	// Extract the song information from the current line matched

        if (preg_match_all('`"([^"]*)"`', $line, $results))
        
        // Put the current song and the next song coming up into variables separating artist and title

        $split_now = explode("-", $results[0][0]);
        $split_next = explode("-", $results[0][1]);

        // Define variables to hold the artist and title for the current and future song

        $cArtist = ltrim($split_now[0], " ");
        $cTitle = ltrim($split_now[1], " ");
        $nArtist = ltrim($split_next[0], " ");
        $nTitle = ltrim($split_next[1], " ");

        $fixedArtistNow = trim($cArtist, '"');
        $fixedTitleNow = trim($cTitle, '"');
        $fixedArtistNext = trim($nArtist, '"');
        $fixedTitleNext = trim($nTitle, '"');
        
        // Analyze prepared tweet for issues with length
        
	$tweeted = ($front . $fixedArtistNow . "-" . " " . $fixedTitleNow . $tag);
        	if (strlen($tweeted) <= 140) {

            		try {
                		getAlbumArt($fixedTitleNow);
                
                		// Post now playing to Twitter feed

                		// Media to be included in the tweet

                			$artwork = $twitter->upload('media/upload', ['media' => '/home/soundcheck/public_html/images/artwork/artwork.jpg']);
                			$parameters = [
                    			'status' => $tweeted, // Word part of the tweet here
                    			'media_ids' => implode(',', [$artwork->media_id_string]),
                			];
                		   $result = $twitter->post('statuses/update', $parameters);
						// Remove the album art from the server after sending the tweet

                				unlink('/home/soundcheck/public_html/images/artwork/artwork.jpg');
            				} catch (TwitterException $e) {
                				echo "Twitter Status: ", $e->getMessage();
                				$twiterror = $e->getMessage() . $line;
                				$myfile = fopen("log/twitter.log", "w") or die("Unable to open file!");
                				fwrite($myfile, $twiterror);
                				fclose($myfile);

            				}

        } 

        // Populate the SHOUTcast nowplaying table with the current song

        $stmt = $dbh->prepare("UPDATE scur_nowplaying SET artist = :cArtist, title = :cTitle, played = :played");
        $stmt->bindParam(':cArtist', $fixedArtistNow);
        $stmt->bindParam(':cTitle', $fixedTitleNow);
        $stmt->bindParam(':played', $played);
        $stmt->execute();

        // Populate the SHOUTcast nextsong table with the next song

        $stmt = $dbh->prepare("UPDATE scur_nextsong SET artist = :nArtist, title = :nTitle, played = :played");
        $stmt->bindParam(':nArtist', $fixedArtistNext);
        $stmt->bindParam(':nTitle', $fixedTitleNext);
        $stmt->bindParam(':played', $played);
        $stmt->execute();

        // Check the database for current song being played. If exists, update it's count and last time played. If song does not exists, add a new entry in the database

        if (songExists($fixedTitleNow)){

            nowPlaying($fixedTitleNow); 

        }else{

            newSong($fixedArtistNow,$fixedTitleNow);
        }

        $line = '';

    }else {
        // What to do when song is not found
        // Get the last song played from the SHOUTcast playlist
        $dayofweek = date("m.d.y");
        $timeofday = date("h:i:s A");
        $stmt = $dbh->prepare("SELECT * FROM scur_shoutcast_playlist ORDER BY Last DESC LIMIT 1");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lId = $row['ID'];
            $lArtist = $row['Artist'];
            $lTitle = $row['Title'];
            $lUploaded = $row['Uploaded'];
            $lLast = $row['Last'];
            $lPlayed = $row['Played'];
            $lCount = $row['Count'];
            $lGenre = $row['Genre'];
            $lVotes = $row['Votes'];
            $lGrade = $row['Grade'];
            $lRates = $row['Rates'];
            $lAverage = $row['Average'];
            $lEmail = $row['Email'];
        }

    }

    // If Statements to change 0 to worded responses
    if ($lVotes == "0") {
        // echo "Song doesn't have any votes yet!";
        $lVotes = "No Votes";
    }
    if ($lRates == "0") {
        // echo "Song doesn't have any votes yet!";
        $lRates = "Not Rated";
    }
echo"    .d8888.  .o88b. db    db d8888b.\n"; 
echo"    88'  YP d8P  Y8 88    88 88  `8D\n"; 
echo"    `8bo.   8P      88    88 88oobY'\n"; 
echo"      `Y8b. 8b      88    88 88`8b\n";   
echo"    db   8D Y8b  d8 88b  d88 88 `88.\n"; 
echo"    `8888Y'  `Y88P' ~Y8888P' 88   YD\n";
    
    // Output current track information to the console/webpage
    echo "\n------------------------------ TRACK INFORMATION -----\n" . "------------------------------------------------------\n" . $lArtist . "\n---- SONG TITLE -----------------------------------\n" . $lTitle . "\n---- UPLOADED -------------------------------------\n" . $lUploaded . "\n---- PLAYED ---------------------------------------\n" . $lLast . "\n---- GENRE ----------------------------------------\n" . $lGenre . "\n---- VOTES ----------------------------------------\n" . $lVotes . "\n---- GRADE ----------------------------------------\n" . $lGrade . "\n---- RATINGS --------------------------------------\n" . $lRates . "\n---- AVERAGE --------------------------------------\n" . $lAverage . "\n==== EMAIL ----------------------------------------\n" . $lEmail;
    // What to do if song did not change below this line
    // error_log("error_get_last()", 3, "test.log");

    $line = '';

?>
