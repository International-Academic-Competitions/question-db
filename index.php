<?php
function redirect($path) {
  header("Location: $path");
  die();
}

function db_connect($file) {
  try {
    $db = new PDO("sqlite:$file", '', '');
  }
  catch(PDOException $e) {
    error_log(var_dump($e));
    die("Could not connect to database.");
  }
  return $db;
}

function db_query($db, $sql, $params=[]) {
  try {
    $result = null;
    $db->exec('BEGIN;');
    $statement = $db->prepare($sql);
    $db->exec('COMMIT;');
    $statement->execute($params);
    $result = $statement->fetchall();
    if ($result == false) {
      return [];
    }
    return $result;
  }
  catch(Exception $e) {
    error_log(var_dump($e));
    http_response_code(500);
    die();
  }
}

$db = db_connect('./questions.db');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!DOCTYPE html>
<title>IAC Question Database</title>
<style>
:root {
  --text-color: #333;
  --blue-accent: #425b7e;
  --salmon-accent: #e6706e;
  --yes-color: #96FFB4;
  --maybe-color: #FFE467;
  --no-color: #FFAFA5;
  --gray: #979b9d;
}

html {
  scrollbar-gutter: stable;
}

body {
  font-family: Verdana;
  margin: 40px 5%;
  line-height: 1.6;
  font-size: 16px;
  color: var(--text-color);
  padding: 0 10px;

}

h1, h2, h3 {
  color: var(--blue-accent);
  font-family: Georgia;
  margin: 20px 0;
  line-height: 1.2

}

h1 {
  font-size: 48px;
  background-color: var(--blue-accent);
  color: white;
  padding: 1rem;
}

header a {
 text-decoration: none;
}


fieldset.packet-types {
  display: flex;
  border: none;
  width: fit-content;
  padding: 0;
}

.packet-types legend {
  color: var(--blue-accent);
  font-weight: bold;
}

.packet-types label {
  margin-right: 1rem;
}

input[type=checkbox] {
  accent-color: var(--blue-accent);
}

table {
  border: 1px solid black;
  width: 100%;
}

table ul {
  margin: 0;
}

.packet tr {
  display: grid;
  grid-template-columns: 3fr 1fr;
  width: 100%;
}

.search tr {
  display: grid;
  grid-template-columns: 3fr 1fr 1fr;
  width: 100%;
}

th, td {
  padding: 1rem .5rem;
}

tr .answer {
  text-align: center;
}

form div, form fieldset {
  margin: 10px 0;
}

form label {
  display: block;
}

details {
  border: 1px solid #aaa;
  border-radius: 4px;
  padding: 0.5em 0.5em 0;
}

summary {
  font-weight: bold;
  user-select: none;
  margin: -0.5em -0.5em 0;
  padding: 0.5em;
}

details[open] {
  position: absolute;
  background-color: white;
}
</style>


<header>
  <a href=/><h1>IAC Question Database</h1></a>
</header>

<?php if (str_starts_with($path, '/packets')):
$id = explode('/', $path)[2] ?? '';

if ($id == '') {
  $packets = db_query($db, "SELECT packet_id, name, filename FROM packets ORDER BY filename");
  echo("<h2>All Packets</h2>");
  echo("<ul>");
  foreach($packets as $packet) {
    $id = $packet['packet_id'];
    $filename = $packet['filename'];
    echo("<li><a href=/packets/$id>$filename</a>");
  }
  echo("</ul>");
  die();
}

$packet = db_query($db, "SELECT name, filename FROM packets WHERE packet_id = ?", [$id])[0];
$filename = $packet['filename'];
echo("<h2>$filename</h2>");

$questions = db_query($db, "
  SELECT
    question,
    '<strong>' || replace(answer, '(', '</strong><br>(') as answer
  FROM questions WHERE packet_id = ?
  ", [$id]);
?>
<table class=packet>
  <tr>
    <th>Question</th>
    <th>Answer</th>
  </tr>
  <?php foreach($questions as $question): ?>
  <tr>
    <td class=question><?= $question["question"]?></td>
    <td class=answer><?= $question["answer"]?></td>
  </tr>
  <?php endforeach ?>
</table>
<?php
die();
endif
?>

<h2>Packets</h2>
<p>
Questions loaded:
<?= db_query($db, "SELECT count(*) as count FROM questions")[0][0]?>

<p>
See <a href=/packets>a list of all packets</a> in the database.

<h2>Search</h2>
<form method=GET action=/search>

<?php
$types = array();
$query = $_GET['q'] ?? "";
if ($path == '/search') {
  if ($query == '') { redirect('/'); }

  if ($_GET['history'] ?? "" == 'on') { $types[] = "'HISTORY'"; }
  if ($_GET['science'] ?? "" == 'on') { $types[] = "'SCIENCE'"; }
  if ($_GET['geography'] ?? "" == 'on') { $types[] = "'GEOGRAPHY'"; }
}

$history_checked = in_array("'HISTORY'", $types) || $path == '/';
$science_checked = in_array("'SCIENCE'", $types) || $path == '/';
$geography_checked = in_array("'GEOGRAPHY'", $types) || $path == '/';
?>

<fieldset class=packet-types>
  <legend>Packet Types</legend>
  <label>
    <input name=history type=checkbox <?= $history_checked ? 'checked' : '' ?> autocomplete=off>
    History
  </label>
  <label>
    <input name=science type=checkbox <?=  $science_checked ? 'checked' : '' ?> autocomplete=off>
    Science
  </label>
  <label>
    <input name=geography type=checkbox <?= $geography_checked ? 'checked' : '' ?> autocomplete=off>
    Geography
  </label>
</fieldset>

<div>
  <input name=q type=text value="<?=$query?>" autocomplete=off>
  <button>Search</button>
</div>
</form>

<?php if ($path == '/search'):
  // Note that $types is created above
  $types = implode(", ", $types);

  $questions = db_query($db, "
    WITH all_questions as (
      SELECT
        question,
        '<strong>' || replace(answer, '(', '</strong><br>(') as answer,
        '<a href=/packets/' || packet_id || '>' || filename as link
        FROM questions
        LEFT JOIN packets USING (packet_id)
        WHERE answer like '%$query%' AND type IN ($types)
    )
    SELECT
      question,
      answer,
      '<li>' || group_concat(link, '<li>') as links
    FROM all_questions
    GROUP BY question
    LIMIT 500;
  ");
?>

<h2>Results</h2>
<?= count($questions) ?> Results

<table class=search>
  <tr>
    <th>Question</th>
    <th>Answer</th>
    <th>Packet(s)</th>
  </tr>
  <?php foreach($questions as $question): ?>
  <tr>
    <td class=question><?= $question["question"]?></td>
    <td class=answer><?= $question["answer"]?></td>
    <td class=packets>
      <details>
        <summary>Show</summary>
        <ul><?= $question["links"] ?></ul>
      </details>
    </td>
  </tr>
  <?php endforeach ?>
</table>

<?php endif ?>
