<?php
/**
 * LittleLands Database Seeder
 * Use this to create the initial Superadmin account.
 */

require_once __DIR__ . '/db_connect.php';

echo "<h2>LittleLands Database Seeder</h2>";

// Superadmin data
$sa_id = '0000-0000';
$sa_firstName = 'Super';
$sa_lastName = 'Admin';
$sa_middleInitial = 'L';
$sa_extension = '';
$sa_sex = 'male';
$sa_birthdate = '1990-01-01';
$sa_age = 34;
$sa_purok = 'Admin Purok';
$sa_barangay = 'Admin Barangay';
$sa_city = 'Admin City';
$sa_province = 'Admin Province';
$sa_zipCode = '1234';
$sa_country = 'Philippines';
$sa_username = 'superadmin';
$sa_email = 'admin@littlelands.com';
$sa_password = password_hash('superadmin123', PASSWORD_DEFAULT);
$sa_role = 'superadmin';
$sa_is_blocked = 0;

// Security Questions (Optional for Superadmin but schema requires columns)
$sa_sq1 = "Who is your bestfriend in elementary?";
$sa_sa1 = password_hash('admin', PASSWORD_DEFAULT);
$sa_sq2 = "What is your favorite food?";
$sa_sa2 = password_hash('admin', PASSWORD_DEFAULT);
$sa_sq3 = "What is the name of your first pet?";
$sa_sa3 = password_hash('admin', PASSWORD_DEFAULT);

// Check if superadmin already exists
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR id = ?");
$check->bind_param("ss", $sa_username, $sa_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    echo "<p style='color: orange;'>Superadmin already exists! Skipping...</p>";
} else {
    $stmt = $conn->prepare("INSERT INTO users (
        id, firstName, lastName, middleInitial, extension, sex, birthdate, age,
        purok, barangay, city, province, zipCode, country,
        username, email, password, role, is_blocked,
        secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "sssssssissssssssssissssss",
            $sa_id, $sa_firstName, $sa_lastName, $sa_middleInitial, $sa_extension, $sa_sex, $sa_birthdate, $sa_age,
            $sa_purok, $sa_barangay, $sa_city, $sa_province, $sa_zipCode, $sa_country,
            $sa_username, $sa_email, $sa_password, $sa_role, $sa_is_blocked,
            $sa_sq1, $sa_sa1, $sa_sq2, $sa_sa2, $sa_sq3, $sa_sa3
        );

        if ($stmt->execute()) {
            echo "<p style='color: green;'>Superadmin created successfully!</p>";
            echo "<ul>
                    <li>Username: <b>$sa_username</b></li>
                    <li>Password: <b>superadmin123</b></li>
                  </ul>";
        } else {
            echo "<p style='color: red;'>Error seeding superadmin: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>Error preparing statement: " . $conn->error . "</p>";
    }
}

$conn->close();
echo "<p><a href='../forms/login.php'>Go to Login Page</a></p>";
?>
