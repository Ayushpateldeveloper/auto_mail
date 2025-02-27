<?php
include '../includes/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    switch ($action) {
        case 'create_group':
            $groupName = $_POST['group_name'];
            $sql = 'INSERT INTO groups (name) VALUES (?)';
            $params = array($groupName);
            sqlsrv_query($conn, $sql, $params);
            break;

        case 'edit_group':
            $groupId = $_POST['group_id'];
            $groupName = $_POST['group_name'];
            // Update group name
            $sql = 'UPDATE groups SET name = ? WHERE id = ?';
            $params = array($groupName, $groupId);
            sqlsrv_query($conn, $sql, $params);

            // Update keywords: remove all existing keywords then add new ones
            $sql = 'DELETE FROM group_keywords WHERE group_id = ?';
            $params = array($groupId);
            sqlsrv_query($conn, $sql, $params);
            if (isset($_POST['keywords'])) {
                $keywords = array_map('trim', explode(',', $_POST['keywords']));
                foreach ($keywords as $keyword) {
                    if ($keyword !== '') {
                        $sql = 'INSERT INTO group_keywords (group_id, keyword) VALUES (?, ?)';
                        $params = array($groupId, $keyword);
                        sqlsrv_query($conn, $sql, $params);
                    }
                }
            }

            // Update members: loop over submitted members data
            if (isset($_POST['members']) && is_array($_POST['members'])) {
                foreach ($_POST['members'] as $memberId => $memberData) {
                    $name = $memberData['name'];
                    $email = $memberData['email'];
                    if ($memberId != '') {
                        // Update existing member
                        $sql = 'UPDATE members SET name = ?, email = ? WHERE id = ?';
                        $params = array($name, $email, $memberId);
                        sqlsrv_query($conn, $sql, $params);
                    } else {
                        // Insert new member
                        $sql = 'INSERT INTO members (group_id, name, email) VALUES (?, ?, ?)';
                        $params = array($groupId, $name, $email);
                        sqlsrv_query($conn, $sql, $params);
                    }
                }
            }
            break;

        case 'add_member':
            $groupId = $_POST['group_id'];
            $memberName = $_POST['member_name'];
            $memberEmail = $_POST['member_email'];
            $sql = 'INSERT INTO members (group_id, name, email) VALUES (?, ?, ?)';
            $params = array($groupId, $memberName, $memberEmail);
            sqlsrv_query($conn, $sql, $params);
            break;

        case 'delete_member':
            $memberId = $_POST['member_id'];
            $sql = 'DELETE FROM members WHERE id = ?';
            $params = array($memberId);
            sqlsrv_query($conn, $sql, $params);
            break;

        case 'delete_group':
            $groupId = $_POST['group_id'];
            $sql = 'DELETE FROM groups WHERE id = ?';
            $params = array($groupId);
            sqlsrv_query($conn, $sql, $params);
            break;

        case 'add_keyword':
            $groupId = $_POST['group_id'];
            // Check if multiple keywords were submitted
            if (isset($_POST['keywords'])) {
                foreach ($_POST['keywords'] as $keyword) {
                    $sql = 'INSERT INTO group_keywords (group_id, keyword) VALUES (?, ?)';
                    $params = array($groupId, $keyword);
                    sqlsrv_query($conn, $sql, $params);
                }
            } elseif (isset($_POST['keyword'])) {
                // Fallback for single keyword
                $keyword = $_POST['keyword'];
                $sql = 'INSERT INTO group_keywords (group_id, keyword) VALUES (?, ?)';
                $params = array($groupId, $keyword);
                sqlsrv_query($conn, $sql, $params);
            }
            break;

        case 'delete_keyword':
            $keywordId = $_POST['keyword_id'];
            $sql = 'DELETE FROM group_keywords WHERE id = ?';
            $params = array($keywordId);
            sqlsrv_query($conn, $sql, $params);
            break;
    }
}

sqlsrv_close($conn);
?>
