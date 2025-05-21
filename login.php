<?php
/**
 * Файл login.php для не авторизованного пользователя выводит форму логина.
 * При отправке формы проверяет логин/пароль и создает сессию,
 * записывает в нее логин и id пользователя.
 * После авторизации пользователь перенаправляется на главную страницу
 * для изменения ранее введенных данных.
 **/
// Отправляем браузеру правильную кодировку,
// файл login.php должен быть в кодировке UTF-8 без BOM.
header('Content-Type: text/html; charset=UTF-8');
// В суперглобальном массиве $_SESSION хранятся переменные сессии.
// Будем сохранять туда логин после успешной авторизации.
$session_started = false;
if ($_COOKIE[session_name()] && session_start()) {
  $session_started = true;
  if (!empty($_SESSION['login'])) {
    // Если есть логин в сессии, то пользователь уже авторизован.
    // Делаем перенаправление на форму.
    header('Location: index.php');
    exit();
  }
}
require 'db.php';
$messages = [];
// В суперглобальном массиве $_SERVER PHP сохраняет некторые заголовки запроса HTTP
// и другие сведения о клиненте и сервере, например метод текущего запроса $_SERVER['REQUEST_METHOD'].
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$session_started) {
        session_start();
      }
      // Если все ок, то авторизуем пользователя.
      $_SESSION['login'] = $_POST['login'];
      // Записываем ID пользователя.
      $_SESSION['uid'] = $_POST['id'];
      header('Location: index.php');
    try {
        $sql_1 = "SELECT * FROM applications WHERE login = ?";
        $stmt = $pdo->prepare($sql_1);
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['pass_hash'])) {
            $_SESSION['login'] = $user['login'];
            $_SESSION['uid'] = $user['id'];
            exit();
        } else {
            $messages[] = '<div >Неверный логин или пароль</div>';
        }
    } catch (PDOException $e) {
        $messages[] = '<div >Ошибка входа: '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <link rel="stylesheet">
</head>
<body>
    <div>
        <?php if (!empty($messages)): ?>
            <div>
                <?php foreach ($messages as $message): ?>
                    <?= $message ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div >
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required>
            </div>
            <div >
                <label for="pass">Пароль</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>

