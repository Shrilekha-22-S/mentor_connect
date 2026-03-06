<?php
/**
 * Mentor: Manage Relationships & Sessions
 */

define('BASE_URL', 'http://localhost/mentor_connect');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

require_role('mentor');

$user_id = get_user_id();
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
?>

<!DOCTYPE html>
<html>

<head>

<title>Manage Sessions</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#f8f9fa;
font-family:Segoe UI;
}

.sidebar{
width:230px;
background:#667eea;
min-height:100vh;
color:white;
position:fixed;
padding:20px;
}

.sidebar a{
display:block;
color:white;
padding:10px;
text-decoration:none;
margin-bottom:5px;
border-radius:5px;
}

.sidebar a:hover{
background:rgba(255,255,255,0.2);
}

.main{
margin-left:250px;
padding:30px;
}

.card-box{
background:white;
padding:20px;
border-radius:10px;
margin-bottom:20px;
box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.stat{
display:flex;
gap:20px;
margin-top:10px;
}

.stat div{
background:#f1f3f5;
padding:10px;
border-radius:6px;
flex:1;
text-align:center;
}

.session{
background:#f8f9fa;
padding:10px;
margin-top:8px;
border-left:4px solid #667eea;
border-radius:5px;
display:flex;
justify-content:space-between;
}

.btn-small{
padding:4px 10px;
font-size:12px;
}

</style>

</head>

<body>

<div class="sidebar">

<h4>Mentor Connect</h4>

<a href="dashboard.php">Dashboard</a>
<a href="pending_requests.php">Pending Requests</a>
<a href="my_mentees.php">My Mentees</a>
<a href="manage_sessions.php">Manage Sessions</a>

<a href="messages.php">Messages</a>
<a href="profile.php">Profile</a>

<a href="../logout.php">Logout</a>

</div>

<div class="main">

<h2>Manage Sessions</h2>

<?php

$mentor_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT 
c.id AS connection_id,
c.mentee_id,
c.started_at,
c.ended_at,
c.sessions_scheduled,
c.sessions_completed,
u.name,
u.email,
mp.learning_goals,
DATEDIFF(c.ended_at, NOW()) AS days_remaining
FROM mentor_mentee_connections c
JOIN users u ON c.mentee_id = u.id
LEFT JOIN mentee_profiles mp ON u.id = mp.user_id
WHERE c.mentor_id = ?
AND c.status = 'active'
ORDER BY c.ended_at ASC
");

$stmt->execute([$mentor_id]);
$relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($relationships)){
    echo "<p>No mentees connected yet</p>";
}

foreach($relationships as $rel){

$stmt2 = $pdo->prepare("
SELECT * FROM mentor_mentee_sessions
WHERE connection_id=?
ORDER BY scheduled_date ASC
");

$stmt2->execute([$rel['connection_id']]);
$sessions = $stmt2->fetchAll();

$total = $rel['sessions_scheduled'] + $rel['sessions_completed'];
$remaining = 6 - $total;

?>

<div class="card-box">

<h4><?php echo htmlspecialchars($rel['name']); ?></h4>

<p>Email: <?php echo htmlspecialchars($rel['email']); ?></p>

<p>Goal: <?php echo htmlspecialchars($rel['learning_goals'] ?? 'Not specified'); ?></p>

<div class="stat">

<div>
<b><?php echo $rel['sessions_completed']; ?></b><br>
Completed
</div>

<div>
<b><?php echo $rel['sessions_scheduled']; ?></b><br>
Scheduled
</div>

<div>
<b><?php echo $remaining; ?></b><br>
Remaining
</div>

<div>
<b><?php echo $rel['days_remaining']; ?></b><br>
Days Left
</div>

</div>

<br>

<b>Sessions</b>

<?php

if(!$sessions){

echo "<p>No sessions yet</p>";

}else{

foreach($sessions as $s){

?>

<div class="session">

<div>

<?php echo date("M d Y H:i",strtotime($s['scheduled_date'])); ?>

<br>

Status: <?php echo $s['status']; ?>

</div>

<div>

<?php if($s['status']=="scheduled"){ ?>

<button class="btn btn-success btn-small"
onclick="completeSession(<?php echo $s['session_id']; ?>)">
Complete
</button>

<?php } ?>

</div>

</div>

<?php
}
}
?>

<?php if($remaining>0){ ?>

<br>

<button class="btn btn-primary"
onclick="scheduleSession(<?php echo $rel['connection_id']; ?>)">
Schedule Session
</button>

<?php } ?>

</div>

<?php } ?>

</div>

<script>

function scheduleSession(id){

let date = prompt("Enter Date Time (YYYY-MM-DD HH:MM)");

fetch("../api/schedule_session.php",{

method:"POST",
headers:{'Content-Type':'application/json'},

body:JSON.stringify({

connection_id:id,
scheduled_date:date

})

})

.then(res=>res.json())

.then(data=>{

alert(data.message)
location.reload()

})

}

function completeSession(id){

fetch("../api/complete_session.php",{

method:"POST",
headers:{'Content-Type':'application/json'},

body:JSON.stringify({

session_id:id

})

})

.then(res=>res.json())

.then(data=>{

alert(data.message)
location.reload()

})

}

</script>

</body>
</html>