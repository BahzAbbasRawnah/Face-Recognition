<?php
session_start();
include '../../../database/database_connection.php';

// جلب ID المشرف من الجلسة
$adminId = $_SESSION['admin_id'] ?? 1;

// تحديث البيانات
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];

    // تحديث الاسم والبريد
    $updateQuery = "UPDATE tbladmin SET firstName = ?, lastName = ?, emailAddress = ? WHERE Id = ?";
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([$firstName, $lastName, $emailAddress, $adminId]);

    // فحص إذا تم إدخال كلمة مرور جديدة
    if (!empty($_POST['newPassword']) && !empty($_POST['confirmPassword'])) {
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updatePasswordQuery = "UPDATE tbladmin SET password = ? WHERE Id = ?";
            $stmtPassword = $pdo->prepare($updatePasswordQuery);
            $stmtPassword->execute([$hashedPassword, $adminId]);
            $success = "Profile and password updated successfully!";
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $success = "Profile updated successfully!";
        header("Location: ../../pages/administrator/home.php");
    }
}

// جلب بيانات المشرف
$stmt = $pdo->prepare("SELECT * FROM tbladmin WHERE Id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="resources/assets/css/styles.css">
    <style>
        .profile-container {
            max-width: 400px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 0 10px #ccc;
        }
        .profile-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-container input {
            width: 97%;
            padding: 12px;
            margin: 10px 0;
        }
        .profile-container button {
            padding: 12px 20px;
            background: #1e90ff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .profile-container .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }
        .profile-container .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<section class="profile-container">
    <h2>Admin Profile</h2>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <label>First Name</label>
        <input type="text" name="firstName" value="<?= htmlspecialchars($admin['firstName']) ?>" required>

        <label>Last Name</label>
        <input type="text" name="lastName" value="<?= htmlspecialchars($admin['lastName']) ?>" required>

        <label>Email Address</label>
        <input type="email" name="emailAddress" value="<?= htmlspecialchars($admin['emailAddress']) ?>" required>

        <hr>

        <label>New Password</label>
        <input type="password" name="newPassword" placeholder="Leave blank to keep current password">

        <label>Confirm Password</label>
        <input type="password" name="confirmPassword" placeholder="Repeat new password">

        <button type="submit">Save Changes</button>
    </form>
</section>

</body>
</html>
