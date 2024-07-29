<!DOCTYPE html>
<title>Packets</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/static/favicon.png" type="image/x-icon" >
<link rel="apple-touch-icon" sizes="180x180" href="/static/favicon57.png" >
<link rel="icon" sizes="192x192" href="/static/favicon57.png">
<link rel="stylesheet" href="/static/stylesheet.css">

<header>
  <a href=/><h1>IAC Question Database</h1></a>
</header>

<?php if ($path == '/packets' || $path == '/packets/'): ?>
<h2>All Packets</h2>
<table class=packet-list>
  <thead>
  <tr>
    <th>Filename
    <th>Packet Name
    <th>Year
    <th>Division
  </tr>
  </thead>

<?php
$packets = db_query($db,
  "SELECT packet_id, name, filename, year, division FROM packets ORDER BY filename
");

foreach($packets as $packet) {
  $id = $packet['packet_id'];
  $name = $packet['name'];
  $filename = $packet['filename'];
  $year = $packet['year'];
  $division = $packet['division'];
  echo("
    <tr>
      <td><a href=/packets/$id>$filename</a>
      <td>$name
      <td>$year
      <td>$division
    </tr>
  ");
  }
echo ("</table>");
die();
endif;
?>

<?php
$id = explode('/', $path)[2] ?? '';

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
  <thead>
  <tr>
    <th>Question</th>
    <th>Answer</th>
  </tr>
  </thead>
  <?php foreach($questions as $question): ?>
  <tr>
    <td class=question><?= $question["question"]?></td>
    <td class=answer><?= $question["answer"]?></td>
  </tr>
  <?php endforeach ?>
</table>

<?php
die();
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
  <thead>
  <tr>
    <th>Question</th>
    <th>Answer</th>
    <th>Packet(s)</th>
  </tr>
  </thead>
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

