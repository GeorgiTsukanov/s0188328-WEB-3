<?php
$config = require_once __DIR__ . '/../../config/s0188328_WEB_3.php';
try {
    $conn = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

$fio = validateInput($_POST['fio']);
$phone = "8".(str_replace("-", "", validateInput($_POST['phone'])));
$email = validateInput($_POST['email']);
$birthday = validateInput($_POST['birthday']);
$gender = validateInput($_POST['gender']);
$biography = validateInput($_POST['biography']);
$languages = $_POST['languages'];

isValidatePost($fio, $email, $phone, $birthday, $gender, $languages);

$applicationId = findApplication($conn, $fio, $phone, $email);
if ($applicationId == False){
    //добавляем
    insertApplication($conn, $fio, $phone, $email, $birthday, $gender, $biography, $languages);
}
else{
    //обновляем
    updateApplication($applicationId['ID'], $conn, $fio, $phone, $email, $birthday, $gender, $biography, $languages);
}

function findApplication($conn, $fio, $phone, $email) {
    try {
        $sql = "SELECT ID FROM Application WHERE FIO = :fio AND Phone_number = :phone AND Email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fio', $fio);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC); // Вернёт ассоциативный массив или false
    } 
    catch (PDOException $e) {
        throw new Exception("Ошибка при поиске заявки: " . $e->getMessage());
    }
}

function insertApplication($conn, $fio, $phone, $email, $birthday, $gender, $biography, $languages){
    $conn->beginTransaction();
    try{
        $sql = "INSERT INTO Application (FIO, Phone_number, Email, Birth_day, Gender, Biography) 
                VALUES (:fio, :phone, :email, :birthday, :gender, :biography)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':fio', $fio);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':birthday', $birthday);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':biography', $biography);

        $stmt->execute();

        $applicationId = $conn->lastInsertId();

        $sql = "INSERT INTO Favorite_pl (ID, Programming_language) VALUES (:id, :language)";
        $stmt = $conn->prepare($sql);
    
        foreach ($languages as $language) {
            $stmt->bindParam(':id', $applicationId);
            $stmt->bindParam(':language', $language, PDO::PARAM_INT);
            $stmt->execute();
        }
        $conn->commit();
        echo "Заявка успешно добавлена";
    }
    catch (PDOException $e) {
        $conn->rollBack();
        throw new Exception("Ошибка при добавлении заявки: " . $e->getMessage());
    }
    finally {
        $conn = null;
    }
}

function updateApplication($applicationId, $conn, $fio, $phone, $email, $birthday, $gender, $biography, $languages){
    $conn->beginTransaction();
    try{
        $sql = "UPDATE Application 
                SET FIO = :fio, Phone_number = :phone, Email = :email, Birth_day = :birthday, Gender = :gender, Biography = :biography 
                WHERE ID = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $applicationId);
        $stmt->bindParam(':fio', $fio);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':birthday', $birthday);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':biography', $biography);

        $stmt->execute();

        $sql = "DELETE FROM Favorite_pl WHERE ID = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $applicationId);
        $stmt->execute();

        $sql = "INSERT INTO Favorite_pl (ID, Programming_language) VALUES (:id, :language)";
        $stmt = $conn->prepare($sql);
        foreach ($languages as $language) {
            $stmt->bindParam(':id', $applicationId);
            $stmt->bindParam(':language', $language, PDO::PARAM_INT);
            $stmt->execute();
        }
        $conn->commit();
        echo "Заявка успешно обновлена!";
    }
    catch (PDOException $e) {
        $conn->rollBack();
        throw new Exception("Ошибка при обновлении заявки: " . $e->getMessage());
    }
    finally {
        $conn = null;
    }
}


function validateInput($data) {
    $data = trim($data); //убирает лишние пробелы в конце и в начале
    $data = stripslashes($data); //убирает экранирующие слэши
    $data = htmlspecialchars($data); //преобразует специальные символы HTML в их HTML-сущности
    return $data;
}

function validateLanguages(array $languages): bool
{
    if (!is_array($languages)){
        return false;
    }
    if (empty($languages)) {
        return false;
    }
    foreach ($languages as $value) {
        if (!is_numeric($value)) {
            return false;
        }
    }
    return true;
}

function isValidatePost($fio, $email, $phone, $birthday, $gender, $languages) {
    if (!preg_match('/^[\p{L}\s]+$/u', $fio)){
        die("Неверный формат ФИО");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Неверный формат email");
    }
    if (!preg_match('/^8\d{10}$/', $phone)){
        die("Неверный формат номера телефона");
    }
    if (!preg_match('/^(?:(?:19|20)\d\d)-(?:0[1-9]|1[012])-(?:0[1-9]|[12][0-9]|3[01])$/', $birthday)){
        die("Неверный формат номера даты");
    }
    if (!($gender == "female" or $gender == "male")){
        die("Неверный формат пола");
    }
    if (!validateLanguages($languages)){
        die("Неверный формат массива яп");
    }
}
?>
