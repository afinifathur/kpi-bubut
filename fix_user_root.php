<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=masterdata_kpi', 'root', '123456788');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, email, department_code FROM users WHERE email = 'adminbubuttimur@peroniks.com'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Found user in masterdata_kpi:\n";
        print_r($user);

        $updateStmt = $pdo->prepare("UPDATE users SET department_code = '404.7' WHERE id = :id");
        $updateStmt->execute(['id' => $user['id']]);

        echo "\nSuccessfully updated department_code to '404.7' for user in masterdata_kpi\n";
    } else {
        echo "\nUser adminbubuttimur@peroniks.com not found in masterdata_kpi.\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
