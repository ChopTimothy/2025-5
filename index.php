<?php
/**
 * Реализовать возможность входа с паролем и логином с использованием
 * сессии для изменения отправленных данных в предыдущей задаче,
 * пароль и логин генерируются автоматически при первоначальной отправке формы.
 */

// Отправляем браузеру правильную кодировку,
// файл index.php должен быть в кодировке UTF-8 без BOM.
header('Content-Type: text/html; charset=UTF-8');

// В суперглобальном массиве $_SERVER PHP сохраняет некторые заголовки запроса HTTP
// и другие сведения о клиненте и сервере, например метод текущего запроса $_SERVER['REQUEST_METHOD'].
session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Массив для временного хранения сообщений пользователю.
    $messages = array();
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        $messages[] = 'Спасибо, результаты сохранены.';

        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = sprintf(
                '<a href="login.php">Войти</a> для изменения данных с логином <strong>%s</strong> и паролем <strong>%s</strong>.',
                htmlspecialchars($_COOKIE['login']),
                htmlspecialchars($_COOKIE['pass'])
            );
        }
    }
    // Складываем признак ошибок в массив.
    $errors = array();
     // При этом санитизуем все данные для безопасного отображения в браузере.
    $values = array();
    $field_names = ['name', 'phone', 'email', 'birthdate', 'gender', 'languages', 'bio', 'agreement'];
    foreach ($field_names as $field) {
        $errors[$field] = !empty($_COOKIE[$field.'_error']) ? $_COOKIE[$field.'_error'] : '';
        if (!empty($errors[$field])) {
            setcookie($field.'_error', '', time() - 3600);
        }
        $values[$field] = empty($_COOKIE[$field.'_value']) ? '' : $_COOKIE[$field.'_value'];
    }

    if (!empty($_SESSION['login'])) {
        try {
            $sql_1="SELECT a.*, GROUP_CONCAT(l.name) as languages
                FROM applications a
                LEFT JOIN application_languages al ON a.id = al.application_id
                LEFT JOIN languages l ON al.language_id = l.id
                WHERE a.login = ?
                GROUP BY a.id";
            $stmt = $pdo->prepare($sql_1);
            $stmt->execute([$_SESSION['login']]);
            $user_data = $stmt->fetch();

            if ($user_data) {
                $values = array_merge($values, $user_data);
                $values['languages'] = $user_data['languages'] ? explode(',', $user_data['languages']) : [];
            }
        } catch (PDOException $e) {
            $messages[] = '<div >Ошибка загрузки данных: '.htmlspecialchars($e->getMessage()).'</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru-RU">

  <head>
    <link rel="stylesheet" crossorigin="anonymous">
   <script crossorigin="anonymous"></script>
   <meta charset="UTF-8">
    <title>index</title>
  </head>

  <body>
    <?php if (!empty($messages)): ?>
           <div>
               <?php foreach ($messages as $message): ?>
                   <div ><?= $message ?></div>
               <?php endforeach; ?>
           </div>
       <?php endif; ?>

       <?php
       $has_errors = false;
       foreach ($errors as $error) {
           if (!empty($error)) {
               $has_errors = true;
               break;
           }
       }
       ?>

       <?php if ($has_errors): ?>
           <div>
               <h4>Ошибки:</h4>
               <ul>
                   <?php foreach ($errors as $field => $error): ?>
                       <?php if (!empty($error)): ?>
                           <li><?= htmlspecialchars($error) ?></li>
                       <?php endif; ?>
                   <?php endforeach; ?>
               </ul>
           </div>
       <?php endif; ?>
    <form action="Osh.php" method="POST" id="form">

          <label >
            1) ФИО:<br>
            <input type="text" <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?> placeholder="Введите ФИО" name="name" id = "name" required
                           value="<?php echo htmlspecialchars($values['name'] ?? ''); ?>">
                    <?php if (!empty($errors['name'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($errors['name']); ?></div>
                    <?php endif; ?>
          </label><br>

          <label>
            2) Телефон:<br>
            <input type="tel" placeholder="+700000-00-00" name="phone" id="phone" required
                           value="<?php echo htmlspecialchars($values['phone'] ?? ''); ?>"
                           class="<?php echo !empty($errors['phone']) ? 'is-invalid' : ''; ?>">
                           <?php if (!empty($errors['phone'])): ?>
                      <div class="invalid-feedback"><?php echo htmlspecialchars($errors['phone']); ?></div>
                  <?php endif; ?>
          </label><br>

          <label >
            3) e-mail:<br>
            <input type="email" placeholder="Введите почту" name="email" id="email" required
                           value="<?php echo htmlspecialchars($values['email'] ?? ''); ?>"
                           class="<?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>">
                    <?php if (!empty($errors['birthdate'])): ?>
                       <div class="invalid-feedback"><?php echo htmlspecialchars($errors['birthdate']); ?></div>
                   <?php endif; ?>
          </label><br>

          <label >
            4) Дата рождения:<br>
            <input value="2005-01-1" type="date" name="birthdate" id="birthdate" required
                           value="<?php echo htmlspecialchars($values['birthdate'] ?? ''); ?>"
                           class="<?php echo !empty($errors['birthdate']) ? 'is-invalid' : ''; ?>">
                     <?php if (!empty($errors['birthdate'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errors['birthdate']); ?></div>
                    <?php endif; ?>
           </label><br>
          <div><br>
            5) Пол:<br>
          <label><input type="radio" checked="checked" value="male" id="male" name="gender" required
                               <?php echo ($values['gender'] ?? '') === 'male' ? 'checked' : ''; ?>
                               class="<?php echo !empty($errors['gender']) ? 'is-invalid' : ''; ?>">
            Мужской</label>
          <label><input type="radio" value="female" id="female" name="gender"
                               <?php echo ($values['gender'] ?? '') === 'female' ? 'checked' : ''; ?>
                               class="<?php echo !empty($errors['gender']) ? 'is-invalid' : ''; ?>">
            Женский</label><br>
            <?php if (!empty($errors['gender'])): ?>
                <div><?php echo htmlspecialchars($errors['gender']); ?></div>
            <?php endif; ?>

          </div><br>

          <label >
            6) Любимый язык программирования:<br>
            <select id="languages" name="languages[]" multiple="multiple" required class="<?php echo !empty($errors['languages']) ? 'is-invalid' : ''; ?>" size="5">
            <?php
              $allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala'];
              $selectedLanguages = isset($values['languages']) ? (is_array($values['languages']) ? $values['languages'] : explode(',', $values['languages'])) : [];

              foreach ($allLanguages as $lang): ?>
                  <option value="<?php echo htmlspecialchars($lang); ?>"
                      <?php echo in_array($lang, $selectedLanguages) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($lang); ?>
                  </option>
              <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['languages'])): ?>
              <div><?php echo htmlspecialchars($errors['languages']); ?></div>
          <?php endif; ?>
          </label><br>

          <label >
            7) Биография:<br>
            <input type="text" id="bio" name="bio" required
                              class="<?php echo !empty($errors['bio']) ? 'is-invalid' : ''; ?>"><?php
                              echo htmlspecialchars($values['bio'] ?? ''); ?></textarea>
                      <?php if (!empty($errors['bio'])): ?>
                          <div class="invalid-feedback"><?php echo htmlspecialchars($errors['bio']); ?></div>
                      <?php endif; ?>
          </label><br>

            8):<br>
          <label class="form-check-label"><input type="checkbox"name="agreement" id="agreement" value="1" required
                           class="<?php echo !empty($errors['agreement']) ? 'is-invalid' : ''; ?>">
            <?php echo ($values['agreement'] ?? '') ? 'checked' : ''; ?>
            С контрактом ознакомлен(а)
            <?php if (!empty($errors['agreement'])): ?>
                <div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['agreement']); ?></div>
            <?php endif; ?>
          </label><br>

            9)Кнопка:<br>
          <button type="submit" name="save" class="btn btn-primary">Сохранить</button>

          <?php if (!empty($_SESSION['login'])): ?>
                <a href="Out.php" class="btn btn-danger">Выйти</a>
            <?php endif; ?>
    </form>
  </body>

</html>
csrf