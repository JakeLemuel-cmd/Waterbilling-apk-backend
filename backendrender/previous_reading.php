<?php
require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/config.php";

$subscriberId = s($_GET["subscriberId"] ?? "");
$type = s($_GET["type"] ?? "");
if ($subscriberId === "") fail("subscriberId is required");

try {
  $db = getDbConnection();

  $stmt = $db->prepare("
    SELECT current_reading
    FROM meter_billing
    WHERE subscriber_id = ? AND type = ?
    ORDER BY date_issued DESC, id DESC
    LIMIT 1
  ");
  $stmt->execute([$subscriberId, $type]);
  $row = $stmt->fetch();


  $prev = 0;
  if ($row && isset($row["current_reading"])) $prev = (float)$row["current_reading"];

  ok(["previous_reading" => $prev]);
} catch (Throwable $e) {
  fail("Failed to fetch previous reading", 500, ["error" => $e->getMessage()]);
}
