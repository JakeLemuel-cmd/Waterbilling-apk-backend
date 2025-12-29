<?php
header("Content-Type: application/json; charset=utf-8");

// CORS (safe for RN + web)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(204);
  exit;
}

function ok($data = []) {
  echo json_encode(array_merge(["success" => true], $data));
  exit;
}

function fail($msg = "Request failed", $code = 400, $extra = []) {
  http_response_code($code);
  echo json_encode(array_merge(["success" => false, "message" => $msg], $extra));
  exit;
}

function s($v) {
  return $v === null ? "" : trim((string)$v);
}
