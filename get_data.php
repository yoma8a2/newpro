<?php
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

try {
    if ($type == 'programs' && $id > 0) {
        $stmt = $pdo->prepare("SELECT program_id, name FROM programs WHERE dept_id IN (SELECT dept_id FROM departments WHERE college_id = ?)");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($type == 'levels' && $id > 0) {
        $stmt = $pdo->prepare("SELECT level_id, level_name FROM levels WHERE program_id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($type == 'sections' && $id > 0) {
        // نستخدم DISTINCT لجلب أسماء الجروبات (A, B, C) الفريدة من جدول الشعب
        $stmt = $pdo->prepare("SELECT DISTINCT group_name FROM sections WHERE level_id = ?");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode([]);
}