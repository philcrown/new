<?php
session_start();
    // Database connection
    $servername = "localhost";
    $username = "root"; // Default XAMPP username
    $password = ""; // Default XAMPP password
    $dbname = "rasa_db";
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}
function talk($json_data){
        
    $ch = curl_init('http://localhost:5005/webhooks/rest/webhook');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
    // Rasa server is not running
    $response = 'Rasa server is not running. Error: ' . curl_error($ch).'. Please try again later 🔂.';
}
    return $response;
}
function Save_con($userMessage,$text) {
    $servername = "localhost";
    $username = "root"; // Default XAMPP username
    $password = ""; // Default XAMPP password
    $dbname = "rasa_db";
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        }
    $stmt = $conn->prepare("INSERT INTO chatconversations (sender, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $sender, $message);   
    $sender = 'User';
    $message = $userMessage;
    $stmt->execute();
    $sender = 'Bot';
    $message = $text;
    $stmt->execute();
    $stmt->close();
}
function change_time($time) {
    foreach(['change_time',$time,'yes'] as $userMessage){
        $data = array("sender" => "user", "message" => $date);
        $json_data = json_encode($data);
        $response = talk($json_data);
        //echo $response;
        }
        //echo $time;
    }
// If form is submitted with a new message, add it to the session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['userMessage']) && !empty($_POST['userMessage'])) {
        $userMessage = htmlspecialchars($_POST['userMessage']);
        // echo $userMessage;
        $data = array("sender" => "user", "message" => $userMessage);
        $json_data = json_encode($data);
        $response=talk($json_data);
        $jsonString = $response;
        // Decode the JSON string into a PHP array
        $jsonArray = json_decode($jsonString, true);
        // Check if decoding was successful
        if ($jsonArray !== null) {
            // Extract the text portion
            if (count($jsonArray) == 0) {
                $text = "I'm sorry, I didn't understand.";
            } else {
                $text = $jsonArray[0]['text'];
            }
            $end = $userMessage;
            if ($text=='For which date would you like to book the appointment?' or $text=='Please let me know at which date should I get the appointment? 🤔'){
                //Save_con($userMessage,$text);
                echo "<div class='button-container'>";
                echo '<form method="post" action="">
             <label for="appointmentDate">Select Appointment Date:</label>
            <input type="date" id="appointmentDate" name="appointmentDate">
            <button type="submit" name="send2">Send</button>
            </form>';
            echo "</div>";
            }

            if($text=='At what time would you like to book the appointment(Please mention AM or PM)?' or $text=='Please let me know at which time should I get the appointment (Please mention AM or PM)?'){
            echo "<div class='button-container'>";
            echo '<form method="post" action="">
                <label for="appointmentTime">Select Appointment Time:</label>
                <input type="time" id="appointmentTime" name="appointmentTime">
                <button type="submit" name="sendTime">Send</button>
                </form>';
            echo "</div>";
        }

            elseif ($text=='when'){
                $sql="SELECT available_date,start_time FROM `appointment` ORDER BY id DESC LIMIT 1";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    // Fetch the result and extract the values
                    $row = $result->fetch_assoc();
                    $w_date = $row['available_date'];
                    $w_time = $row['start_time'];
                    $text = "Your appointment is booked for ".$w_date." at ".$w_time.".";
                    }
                else{
                    $text="No appointments found.";
                }   
                Save_con($userMessage,$text);
            }
            elseif ($text=='cancel'){
                $sql="SELECT available_date,start_time FROM `appointment` ORDER BY id DESC LIMIT 1";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    // Fetch the result and extract the values
                    $row = $result->fetch_assoc();
                    $c_date = $row['available_date'];
                    $c_time = $row['start_time'];
                    $sql="DELETE FROM appointment WHERE available_date='".$c_date."' and start_time='".$c_time."'";
                    $result = $conn->query($sql);
                    $sql="UPDATE doctortiming SET state=0 where available_date='".$c_date."' and start_time='".$c_time."'";
                    $result = $conn->query($sql);
                    $userMessage = 'confirm cancel';
                    $data = array("sender" => "user", "message" => $userMessage);
                    $json_data = json_encode($data);
                    $response=talk($json_data);
                    $jsonString = $response;
                    // Decode the JSON string into a PHP array
                    $jsonArray = json_decode($jsonString, true);
                    $text = $jsonArray[0]['text'];
                    //print_r($jsonArray);
                    }
                else{
                    $text="No appointments found.";
                }   
                Save_con($userMessage,$text);
            }
            elseif ($end == 'yes') {
                $date = substr($text, 37, 10);
                //$time = substr($text, 50, 10);
                $pattern = '/(\d{1,2}(:\d{2})?)\s*(Am|PM)/i';
                preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
                if ($matches) {
                    foreach ($matches as $match) {
                        // Extract time and AM/PM parts
                        $time = $match[1] . (isset($match[2]) ? $match[2] : ''); // Append minutes if present
                        $am_pm = strtoupper($match[3]); // Convert to uppercase for consistency
                        // echo "Extracted Time: $time, AM/PM: $am_pm\n";
                        // Output the extracted parts
                        if ($am_pm == 'PM') {
                            if (is_numeric($time)) {
                                $time += 12;
                                if ($time==24){
                                    $time=12;
                                }
                                $minutes = '00';
                            } else {
                                list($hours, $minutes) = explode(":", $time);
                                $time = $hours + 12;
                                if ($time==24){
                                    $time=12;
                                }
                            }
                        } else {
                            if (is_numeric($time)) {
                                $minutes = '00';
                                if ($time==12){
                                    $time=0;
                                }
                            } else {
                                list($hours, $minutes) = explode(":", $time);
                                $time = $hours;
                                if ($time==12){
                                    $time=0;
                                }
                            }
                        }
                        $time = (string)$time;
                        $time = $time . ':' . $minutes . ':00';
                        // echo "Extracted Time: $time, AM/PM: $am_pm\n";
                    }
                }
                else{
                    $time = substr($text, 50, 10);
                    $time= $time.':00';
                }
                list($hours, $minutes, $seconds) = explode(":", $time);
                $hours=intval($hours);
                $minutes=intval($minutes);
                if ($minutes > 29) {
                    $minutes -= 30;
                    $hours += 1;
                } else {
                    $minutes += 30;
                }
                // echo $time;
                $hours = (string)$hours;
                $minutes = (string)$minutes;
                $seconds = (string)$seconds;
                $time2 = $hours . ':' . $minutes . ':' . $seconds;
                
                if ($date[4]!='-'){
                    $dayWithSuffix = substr($date, 0, 4);
                    //echo $date.' .';
                    // Remove the suffix to get the day
                    $day = preg_replace('/[^0-9]/', '', $dayWithSuffix);
                    //echo $day.' .';
                    // Define the month and year
                    $today = date('d');
                    $month = date('m');
                    $year  = date('Y');
                    if ($today>23){
                        if ($day<9){
                            if ($month==12){
                                $year+=1;
                                $month=0;
                            }
                            $month+=1;
                        }
                    }
                    // Create a date string
                    $dateString = "$year-$month-$day";

                    // Create a DateTime object
                    $date =  $dateString;
                    //echo $dateString.' .';
                    // Check if the date was created successfully
                    
                }
                $stmt = "SELECT * FROM doctortiming WHERE (start_time BETWEEN '$time' AND '$time2' OR end_time BETWEEN '$time' AND '$time2') AND state=0 and available_date='$date'" ;
                $result = $conn->query($stmt);
                $num_rows = $result->num_rows;
                //echo "Number of available slots: " . $num_rows;
                if ($num_rows == 0) {
                    $text="No appointments at this time";
                    Save_con($userMessage,$text);
                } else {
                    echo "<div class='button-container'>";
                    echo "<form method='post'>";
                    while ($row = $result->fetch_assoc()) {
                       echo "<button type='submit' name='bookAppointment' value='" . $row['start_time'] . "," . $date . "," . $userMessage . "'>Book " . $row['start_time'] . "</button><br>";
                    }
                    echo "</form>";
                    echo "</div>";
                }
            }
            else{
                Save_con($userMessage,$text);
            }         
        }
        else{
                $text = $response;
                Save_con($userMessage,$text);
            } 
    }
    if (isset($_POST['sendTime'])){
        $time=$_POST['appointmentTime'];
        $data = array("sender" => "user", "message" => $time);
        $json_data = json_encode($data);
        $response=talk($json_data);
        $jsonString = $response;
        // Decode the JSON string into a PHP array
        $jsonArray = json_decode($jsonString, true);
        if ($jsonArray !== null) {
            // Extract the text portion
            if (count($jsonArray) == 0) {
                $text = "I'm sorry, I didn't understand.";
            } else {
                $text = $jsonArray[0]['text'];
            }
        }
        Save_con($time,$text);
    }

    if (isset($_POST['appointmentDate'])){
        $date=$_POST['appointmentDate'];
        $data = array("sender" => "user", "message" => $date);
        $json_data = json_encode($data);
        $response=talk($json_data);
        $jsonString = $response;
        // Decode the JSON string into a PHP array
        $jsonArray = json_decode($jsonString, true);
        if ($jsonArray !== null) {
            // Extract the text portion
            if (count($jsonArray) == 0) {
                $text = "I'm sorry, I didn't understand.";
            } else {
                $text = $jsonArray[0]['text'];
            }
        }
        Save_con($date,$text);
        if($text=='At what time would you like to book the appointment(Please mention AM or PM)?' or $text=='Please let me know at which time should I get the appointment (Please mention AM or PM)?'){
            echo "<div class='button-container'>";
            echo '<form method="post" action="">
                <label for="appointmentTime">Select Appointment Time:</label>
                <input type="time" id="appointmentTime" name="appointmentTime">
                <button type="submit" name="sendTime">Send</button>
                </form>';
            echo "</div>";
        }
    }
    // Handle the appointment booking
    if (isset($_POST['bookAppointment'])) {
        $user='Philemon';
        list($start_time,$date,$userMessage) = explode(',', $_POST['bookAppointment']);
        $sql = "SELECT * FROM appointment where user='Philemon'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $sql = "SELECT available_date,start_time FROM appointment WHERE user='Philemon'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
                    
            $o_date = $row['available_date'];
            $o_time = $row['start_time'];
            $sql = "UPDATE doctortiming SET state=0 where available_date='".$o_date."' and start_time='".$o_time."'";
            $conn->query($sql);
            $sql = "DELETE FROM `appointment` WHERE user='Philemon'";
            $result = $conn->query($sql);
            
        }
        //Use the $date variable for the available_date
        $stmt = $conn->prepare("INSERT INTO appointment (available_date, start_time,user) VALUES (?, ?,?)");
        $stmt->bind_param("sss", $date, $start_time,$user);
        $stmt->execute();
        $stmt->close();
        $text = 'Appointment booked for '.$date.' at '. $start_time;
        Save_con($userMessage,$text);
        $sql = "UPDATE doctortiming SET state=1 where available_date='".$date."' and start_time='".$start_time."'";
        $conn->query($sql);
    }
}
if (isset($_POST['clearMessages'])) {
        $stmt = $conn->prepare("DELETE FROM chatconversations WHERE 1");
        $stmt->execute();
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Simple Text Display</title>
</head>
<body>
<div id="chat">
    <div id="messages">
        <p>CHATBOT 🤖:</p>
        <?php
        $stmt = "SELECT * FROM chatconversations";
        $result = $conn->query($stmt);
        if ($result->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr><th>id</th><th>sender</th><th>message</th><th>time</th></tr>";
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["sender"] . "</td>";
                echo "<td>" . $row["message"] . "</td>";
                echo "<td>" . $row["timestamp"] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } 
        ?>
        <form method="post" action="">
             <input type="text" name="userMessage" placeholder="Type your message here...">
            <button type="submit" name="send">Send</button>
            <button type="submit" name="clearMessages" value="clear">Clear All</button>
        </form>
    </div>
</div>
</body>
</html>