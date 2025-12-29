<?php
require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") fail("Method not allowed", 405);

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) fail("Invalid JSON body");

$subscriberId  = (int)($input["subscriberId"] ?? 0);
$accountNumber = s($input["accountNumber"] ?? "");
$billingMonth  = s($input["billingMonth"] ?? "");

$prev   = $input["previousReading"] ?? null;
$curr   = $input["currentReading"] ?? null;
$cons   = $input["consumption"] ?? null;
$rate   = $input["ratePerM3"] ?? null;
$amount = $input["amount"] ?? null;

$type = s($input["type"] ?? "");
$meterNumber = s($input["meterNumber"] ?? ""); // only used to update subscribers


if ($subscriberId <= 0 || $accountNumber === "" || $billingMonth === "" || $type === "") {
  fail("Missing required fields (subscriberId, accountNumber, billingMonth, or type)");
}


foreach (["previousReading"=>$prev, "currentReading"=>$curr, "consumption"=>$cons, "ratePerM3"=>$rate, "amount"=>$amount] as $k=>$v) {
  if (!is_numeric($v)) fail("Invalid numeric field: {$k}");
}

if ((float)$curr < (float)$prev) {
  fail("Current reading cannot be less than previous reading");
}

try {
  $db = getDbConnection();

  // billingMonth = YYYY-MM
  $parts = explode("-", $billingMonth);
  $year = isset($parts[0]) ? (int)$parts[0] : (int)date("Y");
  $monthNum = isset($parts[1]) ? (int)$parts[1] : (int)date("m");
  $monthName = date("F", mktime(0, 0, 0, $monthNum, 10));

  $dateIssued = date("Y-m-d");
  $dueDate = date("Y-m-d", strtotime("+15 days"));

  // Optional: prevent duplicate bill for same subscriber, month, and type
  $check = $db->prepare("SELECT id FROM meter_billing WHERE subscriber_id = ? AND billing_month = ? AND type = ? LIMIT 1");
  $check->execute([$subscriberId, $billingMonth, $type]);
  if ($check->fetch()) {
    fail("Billing for '{$type}' already exists for this subscriber and month");
  }


  $stmt = $db->prepare("
    INSERT INTO meter_billing (
      subscriber_id, account_number,
      billing_month, year, month,
      previous_reading, current_reading, consumption, rate_per_m3, amount,
      type, status, date_issued, due_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid', ?, ?)

  ");

  $stmt->execute([
    $subscriberId,
    $accountNumber,
    $billingMonth,
    $year,
    $monthName,
    (float)$prev,
    (float)$curr,
    (float)$cons,
    (float)$rate,
    (float)$amount,
    $type,
    $dateIssued,
    $dueDate
  ]);


  // Update subscriber meter_number if provided
  if ($meterNumber !== "") {
    $upd = $db->prepare("UPDATE subscribers SET meter_number = ? WHERE id = ?");
    $upd->execute([$meterNumber, $subscriberId]);
  }

  ok(["message" => "Billing saved successfully"]);
} catch (Throwable $e) {
  fail("Error saving billing", 500, ["error" => $e->getMessage()]);
}
