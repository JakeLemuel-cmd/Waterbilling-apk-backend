<?php
require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/config.php";

try {
  $db = getDbConnection();

  $stmt = $db->query("
    SELECT id, name, account_number, meter_number, type
    FROM subscribers
    ORDER BY name ASC
  ");
  $rows = $stmt->fetchAll();

  $subs = array_map(function($r) {
    $raw = strtolower(trim($r["type"] ?? "residential"));

    $hasRes = strpos($raw, "residential") !== false;
    $hasCom = strpos($raw, "commercial") !== false;

    $type = "Residential";
    if ($hasRes && $hasCom) $type = "Both";
    else if ($hasCom) $type = "Commercial";

    return [
      "id" => (string)$r["id"],
      "name" => (string)$r["name"],
      "accountNumber" => $r["account_number"],
      "type" => $type,                          // ✅ what RN expects
      "meterNumber" => $r["meter_number"] ?? "", // ✅ hidden in RN but saved
    ];
  }, $rows);

  ok(["subscribers" => $subs]);
} catch (Throwable $e) {
  fail("Failed to fetch subscribers", 500, ["error" => $e->getMessage()]);
}
