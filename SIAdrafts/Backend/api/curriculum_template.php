<?php
session_start();
require_once '../roles.php';
require_once '../require_role.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

require_role([ROLE_REGISTRAR_STAFF, ROLE_HEAD_REGISTRAR], true);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="curriculum_template.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['course_code', 'subject_code', 'subject_name', 'units', 'category_name', 'year_level', 'semester', 'prerequisite_code']);
fputcsv($out, ['BSIT', 'IT101', 'Introduction to Programming', '3', 'IT', '1', '1', '']);
fputcsv($out, ['BSIT', 'IT102', 'Data Structures', '3', 'IT', '1', '2', 'IT101']);
fputcsv($out, ['BSIT', 'GE101', 'Environmental Science', '3', 'GEN ED', '1', '2', '']);
fclose($out);
