<?php
require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/config.php";

$billingMonth = s($_GET["billingMonth"] ?? "");
if ($billingMonth === "") fail("billingMonth is required");

try {
  $db = getDbConnection();

  // subscribers who already have at least one bill in this billing month
  $stmt = $db->prepare("
    SELECT DISTINCT mb.subscriber_id, s.subscriber_type
    FROM meter_billing mb
    JOIN subscribers s ON s.id = mb.subscriber_id
    WHERE mb.billing_month = ?
  ");
  $stmt->execute([$billingMonth]);
  $rows = $stmt->fetchAll();

  $used = [];
  foreach ($rows as $r) {
    $sid = (string)$r["subscriber_id"];
    $raw = strtolower(trim($r["subscriber_type"] ?? ""));

    $hasRes = strpos($raw, "residential") !== false;
    $hasCom = strpos($raw, "commercial") !== false;

    $arr = [];
    if ($hasRes) $arr[] = "Residential";
    if ($hasCom) $arr[] = "Commercial";
    if (!$hasRes && !$hasCom) $arr[] = "Residential"; // fallback

    $used[$sid] = $arr;
  }

  ok(["usedTypes" => $used]);
} catch (Throwable $e) {
  fail("Failed to fetch used types", 500, ["error" => $e->getMessage()]);
}
