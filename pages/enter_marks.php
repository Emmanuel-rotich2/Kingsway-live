<?php
$conn = new mysqli("localhost","root","","kingswayacademy");

/* Preserve Selected Filters */
$subject_id    = $_POST['subject_id'] ?? '';
$term_id       = $_POST['term_id'] ?? '';
$assessment_id = $_POST['assessment_id'] ?? '';
$search        = $_GET['search'] ?? '';

/* FETCH DROPDOWNS */
$subjects = $conn->query("SELECT id, subject_name FROM subjects");
$terms = $conn->query("SELECT id, term_name FROM terms");
$assessments = $conn->query("SELECT id, title FROM assessments");

/* SAVE RESULTS */
if(isset($_POST['save_results'])){

    $total = 0;
    $count = 0;

    foreach($_POST['scores'] as $student_id => $score){

        if($score === "") continue;

        $score = intval($score);
        $total += $score;
        $count++;

        if($score >= 75){ $level = "EE"; }
        elseif($score >= 50){ $level = "ME"; }
        elseif($score >= 25){ $level = "AE"; }
        else { $level = "BE"; }

        $check = $conn->prepare("
            SELECT id FROM marks
            WHERE student_id=? AND subject_id=? AND term_id=? AND assessment_id=?
        ");
        $check->bind_param("iiii",$student_id,$subject_id,$term_id,$assessment_id);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $update = $conn->prepare("
                UPDATE marks
                SET score=?, performance_level=?
                WHERE student_id=? AND subject_id=? AND term_id=? AND assessment_id=?
            ");
            $update->bind_param("isiiii",
                $score,$level,
                $student_id,$subject_id,$term_id,$assessment_id
            );
            $update->execute();
        } else {
            $insert = $conn->prepare("
                INSERT INTO marks
                (student_id,subject_id,term_id,assessment_id,score,performance_level)
                VALUES (?,?,?,?,?,?)
            ");
            $insert->bind_param("iiiiis",
                $student_id,$subject_id,$term_id,$assessment_id,$score,$level
            );
            $insert->execute();
        }
    }

    $average = $count > 0 ? round($total/$count,2) : 0;

    echo "<div class='success'>
        Results Saved Successfully! | Class Average: <strong>$average</strong>
    </div>";
}

/* FETCH STUDENTS WITH SEARCH */
$query = "
    SELECT id, admission_no,
    CONCAT(first_name,' ',last_name) AS full_name
    FROM students
    WHERE status='active'
";

if($search != ''){
    $query .= " AND (first_name LIKE '%$search%' 
                 OR last_name LIKE '%$search%' 
                 OR admission_no LIKE '%$search%')";
}

$query .= " ORDER BY first_name ASC";

$students = $conn->query($query);
?>

<style>
body{font-family:Arial;background:#f4f6f9;}
.card{background:white;padding:25px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
select,input{padding:6px;border-radius:5px;border:1px solid #ccc;}
button{padding:10px 20px;border:none;border-radius:6px;background:#007bff;color:white;cursor:pointer;}
button:hover{background:#0056b3;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:6px;margin-bottom:15px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th,td{padding:8px;border:1px solid #ddd;text-align:left;}
th{background:#007bff;color:white;}
.search-box{margin-bottom:15px;}
</style>

<div class="card">


<form method="GET" class="search-box">
<input type="text" name="search" placeholder="Search by name or admission number" value="<?= $search ?>">
<button type="submit">Search</button>
</form>

<form method="POST">

<div style="display:flex;gap:15px;margin-bottom:15px;">

<select name="subject_id" required>
<option value="">Select Subject</option>
<?php while($row = $subjects->fetch_assoc()){ ?>
<option value="<?= $row['id']; ?>" <?= ($subject_id==$row['id'])?'selected':'' ?>>
<?= $row['subject_name']; ?>
</option>
<?php } ?>
</select>

<select name="term_id" required>
<option value="">Select Term</option>
<?php while($row = $terms->fetch_assoc()){ ?>
<option value="<?= $row['id']; ?>" <?= ($term_id==$row['id'])?'selected':'' ?>>
<?= $row['term_name']; ?>
</option>
<?php } ?>
</select>

<select name="assessment_id" required>
<option value="">Select Assessment</option>
<?php while($row = $assessments->fetch_assoc()){ ?>
<option value="<?= $row['id']; ?>" <?= ($assessment_id==$row['id'])?'selected':'' ?>>
<?= $row['title']; ?>
</option>
<?php } ?>
</select>

</div>

<table>
<tr>
<th>Adm No</th>
<th>Learner Name</th>
<th>Score</th>
<th>Level</th>
</tr>

<?php while($row = $students->fetch_assoc()){ 

$existing_score = '';
$existing_level = '';

if($subject_id && $term_id && $assessment_id){
    $check = $conn->query("
        SELECT score, performance_level 
        FROM marks
        WHERE student_id=".$row['id']."
        AND subject_id=$subject_id
        AND term_id=$term_id
        AND assessment_id=$assessment_id
    ");
    if($check->num_rows > 0){
        $data = $check->fetch_assoc();
        $existing_score = $data['score'];
        $existing_level = $data['performance_level'];
    }
}
?>

<tr>
<td><?= $row['admission_no']; ?></td>
<td><?= $row['full_name']; ?></td>
<td>
<input type="number"
       name="scores[<?= $row['id']; ?>]"
       value="<?= $existing_score ?>"
       class="score-input"
       min="0"
       max="100">
</td>
<td class="level-cell"><?= $existing_level ?></td>
</tr>

<?php } ?>

</table>

<br>
<button type="submit" name="save_results">Save Results</button>

</form>
</div>

<script>
document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function(){
        let value = parseInt(this.value);
        let cell = this.parentElement.nextElementSibling;

        if(isNaN(value)){ cell.innerHTML=""; return; }

        if(value >= 75){ cell.innerHTML="EE"; }
        else if(value >= 50){ cell.innerHTML="ME"; }
        else if(value >= 25){ cell.innerHTML="AE"; }
        else{ cell.innerHTML="BE"; }
    });
});
</script>
