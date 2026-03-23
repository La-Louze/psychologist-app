<?php
// index.php - ПОЛНАЯ ДЕМО-ВЕРСИЯ С РАБОТАЮЩИМ ДНЕВНИКОМ И ЧАТОМ

// Отключаем вывод ошибок в браузер
error_reporting(0);
ini_set('display_errors', 0);

// Функция для отправки JSON
function sendJson($data, $status = 200) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Хранилище настроений и чата в сессии
session_start();
if (!isset($_SESSION['mood_entries'])) {
    // Добавляем пример записей для демонстрации
    $_SESSION['mood_entries'] = [
        ['id' => 1, 'date' => date('Y-m-d', strtotime('-2 day')), 'mood_level' => 4, 'note' => 'Хороший день, много успел', 'emoji' => '🙂'],
        ['id' => 2, 'date' => date('Y-m-d', strtotime('-1 day')), 'mood_level' => 3, 'note' => 'Немного устал после работы', 'emoji' => '😐'],
        ['id' => 3, 'date' => date('Y-m-d'), 'mood_level' => 5, 'note' => 'Отличное настроение!', 'emoji' => '😊']
    ];
}

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [
        ['role' => 'ai', 'message' => 'Здравствуйте! 👋 Я ваш психологический ассистент. Как я могу помочь вам сегодня?', 'time' => date('H:i:s')]
    ];
}

// Демо-токен
$demoToken = 'demo_token_123';

// Получаем URI
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Функция для проверки токена
function checkToken() {
    global $demoToken;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = '';
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    return $token === $demoToken;
}

// Обработка API
if (strpos($uri, '/api/') !== false) {
    
    // Регистрация
    if (strpos($uri, '/api/register') !== false && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
            sendJson(['success' => false, 'error' => 'Заполните все поля'], 400);
        }
        
        if (strlen($input['password']) < 6) {
            sendJson(['success' => false, 'error' => 'Пароль должен быть не менее 6 символов'], 400);
        }
        
        sendJson([
            'success' => true,
            'message' => 'Регистрация успешна',
            'data' => [
                'user' => ['id' => 1, 'name' => $input['name'], 'email' => $input['email'], 'role' => 'user'],
                'token' => $demoToken
            ]
        ]);
    }
    
    // Вход
    if (strpos($uri, '/api/login') !== false && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($input['password'] === 'demo123') {
            sendJson([
                'success' => true,
                'message' => 'Вход выполнен',
                'data' => [
                    'user' => ['id' => 1, 'name' => 'Пользователь', 'email' => $input['email'], 'role' => 'user'],
                    'token' => $demoToken
                ]
            ]);
        } else {
            sendJson(['success' => false, 'error' => 'Неверный пароль. Используйте пароль: demo123'], 401);
        }
    }
    
    // Получение профиля
    if (strpos($uri, '/api/profile') !== false) {
        if (checkToken()) {
            sendJson([
                'success' => true,
                'data' => ['id' => 1, 'name' => 'Пользователь', 'email' => 'user@example.com', 'role' => 'user']
            ]);
        } else {
            sendJson(['success' => false, 'error' => 'Не авторизован'], 401);
        }
    }
    
    // Упражнения
    if (strpos($uri, '/api/exercises') !== false && $method === 'GET') {
        sendJson([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Дыхание квадратом', 'description' => 'Вдох на 4 счета → задержка на 4 → выдох на 4 → задержка на 4. Повторите 5 раз.', 'duration_minutes' => 5, 'category' => 'дыхание', 'completed' => false],
                ['id' => 2, 'title' => 'Сканирование тела', 'description' => 'Медленно пройдите вниманием по всему телу от макушки до пят, отмечая ощущения.', 'duration_minutes' => 10, 'category' => 'осознанность', 'completed' => false],
                ['id' => 3, 'title' => 'Дневник благодарности', 'description' => 'Запишите три вещи, за которые вы благодарны сегодня. Это могут быть маленькие радости.', 'duration_minutes' => 5, 'category' => 'позитивная психология', 'completed' => false],
                ['id' => 4, 'title' => 'Прогрессивная релаксация', 'description' => 'Поочередно напрягайте и расслабляйте мышцы ног, рук, плеч, лица.', 'duration_minutes' => 15, 'category' => 'расслабление', 'completed' => false],
                ['id' => 5, 'title' => '5-4-3-2-1 против тревоги', 'description' => 'Назовите 5 предметов вокруг, 4 звука, 3 тактильных ощущения, 2 запаха, 1 вкус.', 'duration_minutes' => 3, 'category' => 'тревога', 'completed' => false]
            ]
        ]);
    }
    
    // Выполнение упражнения
    if (preg_match('/\/api\/exercises\/(\d+)\/complete/', $uri, $matches) && $method === 'POST') {
        sendJson([
            'success' => true,
            'message' => 'Упражнение отмечено как выполненное! 🎉',
            'data' => ['exercise_id' => $matches[1]]
        ]);
    }
    
    // Медитации
    if (strpos($uri, '/api/audio') !== false && $method === 'GET') {
        sendJson([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Утренняя медитация', 'type' => 'meditation', 'duration_seconds' => 600, 'description' => 'Начните день с осознанности и спокойствия'],
                ['id' => 2, 'title' => 'Шум дождя', 'type' => 'nature', 'duration_seconds' => 1800, 'description' => 'Успокаивающий звук дождя за окном'],
                ['id' => 3, 'title' => 'Звуки леса', 'type' => 'nature', 'duration_seconds' => 1200, 'description' => 'Пение птиц и шелест листвы'],
                ['id' => 4, 'title' => 'Медитация на сон', 'type' => 'meditation', 'duration_seconds' => 900, 'description' => 'Подготовка ко сну, расслабление'],
                ['id' => 5, 'title' => 'Океан', 'type' => 'nature', 'duration_seconds' => 2400, 'description' => 'Шум прибоя и крики чаек']
            ]
        ]);
    }
    
    // Тесты
    if (strpos($uri, '/api/tests') !== false && $method === 'GET') {
        sendJson([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Шкала тревоги', 'description' => 'Оцените уровень вашей тревожности за последнюю неделю', 'category' => 'тревога', 'questions_count' => 7],
                ['id' => 2, 'title' => 'Тест на стресс', 'description' => 'Определите уровень стресса в вашей жизни', 'category' => 'стресс', 'questions_count' => 10],
                ['id' => 3, 'title' => 'Опросник депрессии', 'description' => 'Скрининг на наличие депрессивных симптомов', 'category' => 'депрессия', 'questions_count' => 9]
            ]
        ]);
    }
    
    // Психологи
    if (strpos($uri, '/api/psychologists') !== false && $method === 'GET') {
        sendJson([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Анна Петрова', 'specialization' => 'Когнитивно-поведенческая терапия', 'bio' => 'Опыт работы 8 лет. Помогаю справляться с тревогой, паническими атаками и выгоранием.', 'price' => 2500, 'rating' => 4.9],
                ['id' => 2, 'name' => 'Дмитрий Смирнов', 'specialization' => 'Гештальт-терапия', 'bio' => 'Специализируюсь на работе с отношениями, самооценкой и личностным ростом.', 'price' => 2200, 'rating' => 4.8],
                ['id' => 3, 'name' => 'Елена Козлова', 'specialization' => 'Арт-терапия', 'bio' => 'Помогаю через творчество находить ресурсы и справляться с эмоциями.', 'price' => 2000, 'rating' => 4.9]
            ]
        ]);
    }
    
    // Чек-листы
    if (strpos($uri, '/api/checklists') !== false && $method === 'GET') {
        sendJson([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Утренняя рутина', 'description' => 'Что сделать утром для хорошего дня', 'items' => ['Выпить стакан воды', 'Сделать 5 глубоких вдохов', 'Назвать 3 вещи, за которые я благодарен']],
                ['id' => 2, 'title' => 'Перед сном', 'description' => 'Ритуалы для спокойного сна', 'items' => ['Выключить телефон за час', 'Проветрить комнату', 'Сделать легкую растяжку', 'Почитать книгу']],
                ['id' => 3, 'title' => 'При стрессе', 'description' => 'Что делать, когда накрывает тревога', 'items' => ['Сделать 10 глубоких вдохов', 'Выйти на улицу', 'Написать о чувствах', 'Обратиться к поддержке']]
            ]
        ]);
    }
    
    // ========== ДНЕВНИК НАСТРОЕНИЯ - ПОЛУЧЕНИЕ ==========
    if (strpos($uri, '/api/mood') !== false && $method === 'GET') {
        if (!checkToken()) {
            sendJson(['success' => false, 'error' => 'Не авторизован'], 401);
        }
        
        $entries = array_values($_SESSION['mood_entries']);
        // Сортируем по дате (сначала новые)
        usort($entries, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        sendJson(['success' => true, 'data' => $entries]);
    }
    
    // ========== ДНЕВНИК НАСТРОЕНИЯ - СОХРАНЕНИЕ ==========
    if (strpos($uri, '/api/mood') !== false && $method === 'POST') {
        if (!checkToken()) {
            sendJson(['success' => false, 'error' => 'Не авторизован'], 401);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = [];
        if (empty($input['mood_level']) || $input['mood_level'] < 1 || $input['mood_level'] > 10) {
            $errors['mood_level'] = 'Уровень настроения должен быть от 1 до 10';
        }
        if (empty($input['date'])) {
            $errors['date'] = 'Дата обязательна';
        }
        
        if (!empty($errors)) {
            sendJson(['success' => false, 'errors' => $errors], 422);
        }
        
        $moodLevel = (int)$input['mood_level'];
        $date = $input['date'];
        $note = $input['note'] ?? '';
        
        // Эмодзи для уровня настроения (1-10)
        $emojis = ['😢', '😕', '😐', '🙂', '😊', '😍', '🤗', '🥰', '💪', '🔥'];
        $moodEmoji = $emojis[$moodLevel - 1];
        
        // Обновляем или добавляем запись
        $found = false;
        foreach ($_SESSION['mood_entries'] as $key => $entry) {
            if ($entry['date'] === $date) {
                $_SESSION['mood_entries'][$key]['mood_level'] = $moodLevel;
                $_SESSION['mood_entries'][$key]['note'] = $note;
                $_SESSION['mood_entries'][$key]['emoji'] = $moodEmoji;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $newId = count($_SESSION['mood_entries']) + 1;
            $_SESSION['mood_entries'][] = [
                'id' => $newId,
                'date' => $date,
                'mood_level' => $moodLevel,
                'note' => $note,
                'emoji' => $moodEmoji,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Рекомендация в зависимости от настроения
        $recommendation = '';
        if ($moodLevel <= 3) {
            $recommendation = '🌧️ Заметили, что настроение снизилось. Попробуйте упражнение "Дыхание квадратом" или напишите в чат — я помогу вам!';
        } elseif ($moodLevel <= 5) {
            $recommendation = '🌤️ День идет своим чередом. Хотите выполнить легкое упражнение для поднятия настроения?';
        } elseif ($moodLevel >= 8) {
            $recommendation = '☀️ Отличное настроение! Поделитесь радостью с близкими или запишите в дневник благодарности, что сделало ваш день таким хорошим.';
        } else {
            $recommendation = '🌸 Спасибо, что делитесь своими чувствами. Это помогает лучше понимать себя.';
        }
        
        sendJson([
            'success' => true,
            'message' => 'Настроение сохранено!',
            'data' => [
                'date' => $date,
                'mood_level' => $moodLevel,
                'note' => $note,
                'emoji' => $moodEmoji
            ],
            'recommendation' => $recommendation
        ]);
    }
    
    // ========== ЧАТ С AI - ОТПРАВКА СООБЩЕНИЯ ==========
    if (strpos($uri, '/api/ai/chat') !== false && $method === 'POST') {
        if (!checkToken()) {
            sendJson(['success' => false, 'error' => 'Не авторизован'], 401);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        
        if (empty($message)) {
            sendJson(['success' => false, 'error' => 'Сообщение не может быть пустым'], 400);
        }
        
        // Добавляем сообщение пользователя в историю
        $_SESSION['chat_history'][] = ['role' => 'user', 'message' => $message, 'time' => date('H:i:s')];
        
        // Генерируем осмысленный ответ в зависимости от сообщения
        $lowerMsg = mb_strtolower($message);
        
        // Расширенная база ответов
        $responses = [
            // Приветствия
            'привет' => 'Здравствуйте! 🌸 Рада вас видеть. Как прошёл ваш день? Расскажите, что у вас на душе.',
            'здравствуй' => 'Здравствуйте! 👋 Я здесь, чтобы поддержать вас. О чём хотите поговорить?',
            'добрый день' => 'Добрый день! 😊 Как я могу помочь вам сегодня?',
            'доброе утро' => 'Доброе утро! 🌅 Начните день с глубокого вдоха. Как ваше самочувствие?',
            'добрый вечер' => 'Добрый вечер! 🌙 Как прошёл ваш день? Есть что-то, чем хотите поделиться?',
            
            // Вопросы о состоянии
            'как дела' => 'Спасибо, что спрашиваете! 💫 Я здесь, чтобы поддержать вас. А как вы себя чувствуете сегодня? Расскажите подробнее.',
            'как ты' => 'Я в порядке, спасибо! Главное — как вы себя чувствуете. Расскажите, что у вас происходит?',
            'как настроение' => 'Ваше настроение важно для меня. Поделитесь, что вы чувствуете? Если грустно — я рядом, если радостно — давайте порадуемся вместе! 😊',
            'что делаешь' => 'Я здесь, чтобы помогать людям! Сейчас я слушаю вас. Расскажите, что вас волнует?',
            
            // Тревога и стресс
            'тревога' => 'Тревога — это неприятное чувство, но помните: оно пройдёт. 😌 Давайте сделаем вместе глубокий вдох: вдох на 4 счета, задержка на 4, выдох на 4. Повторите 3 раза. Как себя чувствуете?',
            'стресс' => 'Стресс — это естественная реакция организма. 🌿 Предлагаю сделать паузу: назовите 5 предметов, которые вы видите вокруг. Это поможет вернуться в настоящий момент. Попробуете?',
            'паника' => 'Паническая атака — это страшно, но вы справитесь! 🤗 Сосредоточьтесь на дыхании: медленно вдохните носом, выдохните ртом. Я рядом. Если нужно — обратитесь к близкому человеку или позвоните на горячую линию 8-800-2000-122.',
            'волнуюсь' => 'Волнение — это нормально. 🌊 Попробуйте представить, что волнение — это облако, которое медленно проплывает мимо. Вы наблюдаете, но не сливаетесь с ним. Стало немного легче?',
            'боюсь' => 'Страх — это сильная эмоция. 🦋 Попробуйте назвать свой страх вслух. Часто, когда мы его проговариваем, он становится менее пугающим. Чего вы боитесь?',
            
            // Упражнения
            'упражнение' => 'У меня есть для вас отличное упражнение! 🧘 Называется "Дыхание квадратом": вдох (4 счета) — задержка (4) — выдох (4) — задержка (4). Повторите 5 раз. Почувствуйте, как напряжение уходит. Хотите другое упражнение?',
            'дыхание' => 'Дыхание — наш главный инструмент. 🌬️ Попробуйте прямо сейчас: медленно вдохните через нос, чувствуя, как воздух наполняет живот. Задержитесь на мгновение и плавно выдохните через рот. Повторите 5 раз. Легче?',
            'медитация' => 'Медитация помогает успокоить ум. 🧠 Предлагаю простую: сядьте удобно, закройте глаза и просто наблюдайте за своим дыханием. Не пытайтесь его изменить, просто замечайте. 2 минуты — и вы почувствуете разницу.',
            'расслабление' => 'Чтобы расслабиться, попробуйте прогрессивную релаксацию: поочередно напрягайте и расслабляйте мышцы ног, рук, плеч, лица. Напряжение — вдох, расслабление — выдох. Готовы попробовать?',
            
            // Психолог
            'психолог' => 'Если вы чувствуете, что нужна поддержка специалиста, я помогу! 👨‍⚕️ В разделе "Психологи" вы можете выбрать специалиста и записаться на консультацию. Помните: обращаться за помощью — это проявление силы, а не слабости.',
            'записаться' => 'Запись к психологу доступна в соответствующем разделе. 🗓️ Выберите удобное время и специалиста. Первая консультация поможет понять, подходит ли вам этот специалист.',
            'консультация' => 'Консультации проводятся онлайн через видеозвонок. 💻 Это удобно и конфиденциально. Вы можете выбрать психолога по специализации и цене в разделе "Психологи".',
            
            // Благодарности
            'спасибо' => 'Пожалуйста! 💖 Я всегда рада помочь. Если захотите поговорить или нужна поддержка — я здесь. Берегите себя!',
            'помощь' => 'Я могу предложить упражнения для снятия стресса, помочь разобраться в чувствах или просто поддержать разговор. 🌼 О чём вы хотите поговорить?',
            
            // Сон
            'сон' => 'Хороший сон — основа здоровья. 😴 Попробуйте перед сном: выключите телефон за час, проветрите комнату, сделайте лёгкую растяжку. Хотите медитацию для сна?',
            'не сплю' => 'Бессонница бывает у многих. 🌙 Попробуйте упражнение "4-7-8": вдох на 4 счета, задержка на 7, выдох на 8. Это помогает расслабиться. Сделайте 3-4 цикла.',
            'бессонница' => 'Сложности со сном? 🛌 Попробуйте создать ритуал: тёплая ванна, травяной чай, чтение книги. Исключите гаджеты за час до сна. Я верю, у вас получится!',
            
            // Дневник
            'дневник' => 'Ведение дневника помогает лучше понимать себя. 📔 В разделе "Дневник" вы можете отмечать настроение каждый день. Это помогает отслеживать динамику и замечать, что на вас влияет.',
            
            // Прощание
            'пока' => 'До свидания! 🤗 Берегите себя и помните: вы не одни. Я всегда здесь, если захотите поговорить. Хорошего дня!',
            'до свидания' => 'Всего доброго! 🌺 Пусть ваш день будет наполнен спокойствием и радостью. Обращайтесь, если что-то понадобится.',
            'всего хорошего' => 'Спасибо за разговор! 🌈 Помните: забота о себе — это важно. До новых встреч!',
            
            // Любовь и отношения
            'любовь' => 'Любовь — это прекрасное чувство. 💕 Расскажите, что у вас происходит? Я могу помочь разобраться в отношениях или просто поддержать разговор.',
            'одиночество' => 'Одиночество — сложное чувство. 🌙 Помните, что вы не одни. Я здесь, чтобы поддержать вас. Расскажите, что вас тревожит?',
            
            // Работа и усталость
            'устал' => 'Усталость — сигнал, что пора отдохнуть. 🌿 Сделайте перерыв, выпейте воды, прогуляйтесь. Ваше благополучие важнее любых дел.',
            'работа' => 'Работа — важная часть жизни, но не забывайте отдыхать. 🧘 Попробуйте делать короткие паузы каждые 45 минут. Это повышает продуктивность и снижает стресс.'
        ];
        
        // Поиск подходящего ответа
        $response = null;
        foreach ($responses as $key => $val) {
            if (mb_strpos($lowerMsg, $key) !== false) {
                $response = $val;
                break;
            }
        }
        
        // Если нет подходящего ответа, используем общий
        if (!$response) {
            $generalResponses = [
                'Спасибо, что делитесь. 💭 Это важно. Расскажите подробнее, если хотите. Я здесь, чтобы поддержать вас.',
                'Я с вами. 🤝 Продолжайте, я внимательно слушаю. Если хотите, предложу упражнение или мы просто поговорим.',
                'Понимаю. 🌻 Такое бывает. Что вы чувствуете в этот момент? Называние чувств помогает их прожить.',
                'Спасибо за откровенность. 🌈 Иногда просто выговориться — уже облегчение. Я рядом.',
                'Хотите выполнить короткое упражнение? 🧘 Или продолжим разговор? Решать вам.',
                'Это важная тема. 💫 Если хотите, могу предложить несколько техник, которые помогают в таких ситуациях.',
                'Я внимательно слушаю. 👂 Расскажите ещё, что у вас на душе. Вместе мы сможем разобраться.',
                'Спасибо, что доверяете мне. 🤗 Я здесь, чтобы поддержать вас в любой ситуации.'
            ];
            $response = $generalResponses[array_rand($generalResponses)];
        }
        
        // Добавляем ответ AI в историю
        $_SESSION['chat_history'][] = ['role' => 'ai', 'message' => $response, 'time' => date('H:i:s')];
        
        // Ограничиваем историю 50 сообщениями
        if (count($_SESSION['chat_history']) > 50) {
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -50);
        }
        
        sendJson([
            'success' => true,
            'data' => [
                'id' => count($_SESSION['chat_history']),
                'message' => $message,
                'response' => $response,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    // ========== ИСТОРИЯ ЧАТА ==========
    if (strpos($uri, '/api/ai/history') !== false && $method === 'GET') {
        if (!checkToken()) {
            sendJson(['success' => false, 'error' => 'Не авторизован'], 401);
        }
        
        // Преобразуем историю в формат для фронтенда
        $formatted = [];
        $currentUserMsg = null;
        
        foreach ($_SESSION['chat_history'] as $msg) {
            if ($msg['role'] === 'user') {
                $currentUserMsg = ['message' => $msg['message'], 'response' => null, 'created_at' => date('Y-m-d H:i:s')];
                $formatted[] = $currentUserMsg;
            } elseif ($msg['role'] === 'ai' && count($formatted) > 0 && $formatted[count($formatted)-1]['response'] === null) {
                $formatted[count($formatted)-1]['response'] = $msg['message'];
            } elseif ($msg['role'] === 'ai') {
                $formatted[] = ['message' => null, 'response' => $msg['message'], 'created_at' => date('Y-m-d H:i:s')];
            }
        }
        
        sendJson(['success' => true, 'data' => $formatted]);
    }
    
    // Выход
    if (strpos($uri, '/api/logout') !== false && $method === 'POST') {
        session_destroy();
        sendJson(['success' => true, 'message' => 'Выход выполнен']);
    }
    
    // Если ничего не подошло
    sendJson(['success' => false, 'error' => 'Маршрут не найден'], 404);
}

// Если не API - показываем HTML
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Твой психолог - поддержка ментального здоровья</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .logo span {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 10px;
        }
        .btn-login { background: transparent; border: 2px solid white; color: white; padding: 8px 20px; border-radius: 25px; cursor: pointer; margin-left: 10px; }
        .btn-register { background: white; color: #4facfe; padding: 8px 20px; border: none; border-radius: 25px; cursor: pointer; margin-left: 10px; }
        .btn-logout { background: rgba(255,255,255,0.2); color: white; border: 1px solid white; padding: 8px 20px; border-radius: 25px; cursor: pointer; margin-left: 10px; }
        .user-name { color: white; font-weight: bold; margin-right: 10px; }
        
        .nav-tabs {
            display: flex;
            flex-wrap: wrap;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            padding: 0 20px;
            gap: 5px;
        }
        .tab-btn {
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: #6c757d;
            transition: 0.3s;
        }
        .tab-btn:hover { color: #4facfe; }
        .tab-btn.active { color: #4facfe; border-bottom: 3px solid #4facfe; }
        
        .content { padding: 30px; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            transition: 0.3s;
            border: 1px solid #e9ecef;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .card h3 { color: #333; margin-bottom: 10px; }
        .card p { color: #6c757d; margin-bottom: 10px; line-height: 1.5; }
        .card button {
            background: #4facfe;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 10px;
        }
        .card button.completed { background: #28a745; opacity: 0.7; cursor: default; }
        
        .mood-selector {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
            flex-wrap: wrap;
        }
        .mood-btn {
            font-size: 32px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: 0.3s;
        }
        .mood-btn:hover { transform: scale(1.1); background: #f0f0f0; }
        .mood-btn.selected { background: #e9ecef; transform: scale(1.1); }
        .input-field, .textarea-field {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin: 10px 0;
            font-size: 16px;
        }
        .textarea-field { resize: vertical; min-height: 80px; }
        .save-btn {
            background: #4facfe;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .chat-container {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            overflow: hidden;
        }
        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .message { margin-bottom: 15px; display: flex; }
        .message.user { justify-content: flex-end; }
        .message.user .message-content { background: #4facfe; color: white; }
        .message.ai .message-content { background: white; border: 1px solid #e9ecef; }
        .message-content {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.4;
        }
        .chat-input {
            display: flex;
            padding: 15px;
            background: white;
            border-top: 1px solid #e9ecef;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }
        .chat-input button {
            padding: 12px 25px;
            background: #4facfe;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .welcome-block { text-align: center; padding: 50px; }
        .welcome-block h1 { color: #333; margin-bottom: 20px; }
        .welcome-block p { color: #6c757d; line-height: 1.6; }
        .demo-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            margin: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .recommendation-box {
            background: #e8f4fd;
            border-left: 4px solid #4facfe;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 14px;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            .tab-btn { padding: 10px 12px; font-size: 12px; }
            .cards-grid { grid-template-columns: 1fr; }
            .message-content { max-width: 85%; }
            .mood-btn { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><span>🧠 Твой психолог</span></div>
            <div id="auth-section">
                <div id="unauth-buttons">
                    <button class="btn-login" onclick="showLoginModal()">Вход</button>
                    <button class="btn-register" onclick="showRegisterModal()">Регистрация</button>
                </div>
                <div id="auth-user" style="display: none;">
                    <span class="user-name" id="userName"></span>
                    <button class="btn-logout" onclick="logout()">Выйти</button>
                </div>
            </div>
        </div>
        
        <div class="demo-note">
            🔓 ДЕМО-РЕЖИМ: Для входа используйте любой email и пароль: <strong>demo123</strong>
        </div>
        
        <div id="navTabs" class="nav-tabs" style="display: none;">
            <button class="tab-btn active" data-tab="exercises">🧘 Упражнения</button>
            <button class="tab-btn" data-tab="audio">🎵 Медитации</button>
            <button class="tab-btn" data-tab="mood">😊 Дневник</button>
            <button class="tab-btn" data-tab="tests">📝 Тесты</button>
            <button class="tab-btn" data-tab="chat">💬 Чат с AI</button>
            <button class="tab-btn" data-tab="psychologists">👨‍⚕️ Психологи</button>
            <button class="tab-btn" data-tab="checklists">✅ Чек-листы</button>
        </div>
        
        <div class="content">
            <div id="welcome-message" class="welcome-block">
                <h1>🌸 Добро пожаловать в приложение "Твой психолог"</h1>
                <p>Войдите или зарегистрируйтесь, чтобы начать работу</p>
                <p style="margin-top: 20px;">Упражнения для снятия стресса, медитации, дневник настроения,<br>чат с нейросетью для поддержки и многое другое</p>
            </div>
            
            <div id="exercises-pane" class="tab-pane">
                <h2>🧘 Упражнения для осознанности и снятия стресса</h2>
                <div id="exercises-list" class="cards-grid"></div>
            </div>
            
            <div id="audio-pane" class="tab-pane">
                <h2>🎵 Медитации и звуки для расслабления</h2>
                <div id="audio-list" class="cards-grid"></div>
            </div>
            
            <div id="mood-pane" class="tab-pane">
                <h2>😊 Дневник настроения</h2>
                <p style="color: #6c757d; margin-bottom: 15px;">Отслеживайте свое эмоциональное состояние</p>
                
                <div class="mood-selector">
                    <button class="mood-btn" data-mood="1" title="Ужасно">😢</button>
                    <button class="mood-btn" data-mood="2" title="Плохо">😕</button>
                    <button class="mood-btn" data-mood="3" title="Нормально">😐</button>
                    <button class="mood-btn" data-mood="4" title="Хорошо">🙂</button>
                    <button class="mood-btn" data-mood="5" title="Отлично">😊</button>
                    <button class="mood-btn" data-mood="6" title="Замечательно">😍</button>
                    <button class="mood-btn" data-mood="7" title="Прекрасно">🤗</button>
                    <button class="mood-btn" data-mood="8" title="Восторг">🥰</button>
                    <button class="mood-btn" data-mood="9" title="Энергично">💪</button>
                    <button class="mood-btn" data-mood="10" title="Невероятно">🔥</button>
                </div>
                
                <input type="date" id="mood-date" class="input-field">
                <textarea id="mood-note" class="textarea-field" placeholder="Что повлияло на ваше настроение? (необязательно)"></textarea>
                <button class="save-btn" onclick="saveMood()">📝 Сохранить настроение</button>
                
                <div id="mood-recommendation" class="recommendation-box" style="display: none;"></div>
                
                <h3 style="margin-top: 30px;">📅 История настроения</h3>
                <div id="mood-history" class="cards-grid"></div>
            </div>
            
            <div id="tests-pane" class="tab-pane">
                <h2>📝 Психологические тесты</h2>
                <div id="tests-list" class="cards-grid"></div>
            </div>
            
            <div id="chat-pane" class="tab-pane">
                <h2>💬 Чат с нейросетью</h2>
                <p style="margin-bottom: 15px; color: #6c757d;">Задайте вопрос, поделитесь переживаниями или просто поговорите. Я здесь, чтобы поддержать вас.</p>
                <div class="chat-container">
                    <div id="chat-messages" class="chat-messages"></div>
                    <div class="chat-input">
                        <input type="text" id="chat-input" placeholder="Напишите сообщение..." onkeypress="if(event.key==='Enter') sendMessage()">
                        <button onclick="sendMessage()">Отправить</button>
                    </div>
                </div>
            </div>
            
            <div id="psychologists-pane" class="tab-pane">
                <h2>👨‍⚕️ Наши психологи</h2>
                <div id="psychologists-list" class="cards-grid"></div>
            </div>
            
            <div id="checklists-pane" class="tab-pane">
                <h2>✅ Чек-листы для хорошего настроения</h2>
                <div id="checklists-list" class="cards-grid"></div>
            </div>
        </div>
    </div>
    
    <!-- Модальные окна -->
    <div id="loginModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 400px;">
            <h3>Вход в аккаунт</h3>
            <input type="email" id="loginEmail" placeholder="Email" style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <input type="password" id="loginPassword" placeholder="Пароль (demo123)" style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button onclick="closeLoginModal()" style="padding: 10px 20px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;">Отмена</button>
                <button onclick="login()" style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer;">Войти</button>
            </div>
        </div>
    </div>
    
    <div id="registerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 400px;">
            <h3>Регистрация</h3>
            <input type="text" id="regName" placeholder="Имя" style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <input type="email" id="regEmail" placeholder="Email" style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <input type="password" id="regPassword" placeholder="Пароль (минимум 6 символов)" style="width: 100%; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #ddd;">
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button onclick="closeRegisterModal()" style="padding: 10px 20px; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;">Отмена</button>
                <button onclick="register()" style="padding: 10px 20px; background: #4facfe; color: white; border: none; border-radius: 8px; cursor: pointer;">Зарегистрироваться</button>
            </div>
        </div>
    </div>

    <script>
        let currentUser = null;
        let currentToken = null;
        
        function showToast(msg, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = msg;
            toast.style.background = isError ? '#dc3545' : '#28a745';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        function showLoginModal() { document.getElementById('loginModal').style.display = 'flex'; }
        function closeLoginModal() { document.getElementById('loginModal').style.display = 'none'; }
        function showRegisterModal() { document.getElementById('registerModal').style.display = 'flex'; }
        function closeRegisterModal() { document.getElementById('registerModal').style.display = 'none'; }
        
        async function apiRequest(endpoint, data = null) {
            const headers = { 'Content-Type': 'application/json' };
            if (currentToken) headers['Authorization'] = 'Bearer ' + currentToken;
            
            const options = { method: data ? 'POST' : 'GET', headers: headers };
            if (data) options.body = JSON.stringify(data);
            
            try {
                const response = await fetch(window.location.pathname.replace(/\/$/, '') + '/api' + endpoint, options);
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Не JSON:', text);
                    return { success: false, error: 'Ошибка сервера' };
                }
            } catch (error) {
                return { success: false, error: error.message };
            }
        }
        
        async function register() {
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;
            
            if (!name || !email || !password) { showToast('Заполните все поля', true); return; }
            if (password.length < 6) { showToast('Пароль должен быть не менее 6 символов', true); return; }
            
            const response = await apiRequest('/register', { name, email, password });
            if (response.success) {
                currentToken = response.data.token;
                currentUser = response.data.user;
                localStorage.setItem('token', currentToken);
                closeRegisterModal();
                showToast('Регистрация успешна!');
                loadApp();
            } else {
                showToast(response.error || 'Ошибка регистрации', true);
            }
        }
        
        async function login() {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            
            if (!email || !password) { showToast('Заполните email и пароль', true); return; }
            
            const response = await apiRequest('/login', { email, password });
            if (response.success) {
                currentToken = response.data.token;
                currentUser = response.data.user;
                localStorage.setItem('token', currentToken);
                closeLoginModal();
                showToast('Добро пожаловать, ' + currentUser.name + '!');
                loadApp();
            } else {
                showToast(response.error || 'Неверный пароль. Используйте: demo123', true);
            }
        }
        
        function logout() {
            currentToken = null;
            currentUser = null;
            localStorage.removeItem('token');
            document.getElementById('unauth-buttons').style.display = 'flex';
            document.getElementById('auth-user').style.display = 'none';
            document.getElementById('navTabs').style.display = 'none';
            document.getElementById('welcome-message').style.display = 'block';
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            showToast('Вы вышли из системы');
        }
        
        async function loadApp() {
            document.getElementById('unauth-buttons').style.display = 'none';
            document.getElementById('auth-user').style.display = 'flex';
            document.getElementById('userName').textContent = currentUser.name;
            document.getElementById('navTabs').style.display = 'flex';
            document.getElementById('welcome-message').style.display = 'none';
            document.getElementById('exercises-pane').classList.add('active');
            
            await Promise.all([
                loadExercises(),
                loadAudio(),
                loadMoodHistory(),
                loadTests(),
                loadPsychologists(),
                loadChecklists(),
                loadChatHistory()
            ]);
        }
        
        async function loadExercises() {
            const response = await apiRequest('/exercises');
            if (response.success) {
                const container = document.getElementById('exercises-list');
                container.innerHTML = '';
                response.data.forEach(ex => {
                    container.innerHTML += `
                        <div class="card">
                            <h3>${ex.title}</h3>
                            <p>${ex.description}</p>
                            <p>⏱️ ${ex.duration_minutes} мин | 📂 ${ex.category}</p>
                            <button onclick="completeExercise(${ex.id})">✅ Выполнить</button>
                        </div>
                    `;
                });
            }
        }
        
        async function completeExercise(id) {
            const response = await apiRequest(`/exercises/${id}/complete`, {});
            if (response.success) {
                showToast(response.message);
            } else {
                showToast(response.error || 'Ошибка', true);
            }
        }
        
        async function loadAudio() {
            const response = await apiRequest('/audio');
            if (response.success) {
                const container = document.getElementById('audio-list');
                container.innerHTML = '';
                const types = { meditation: '🧘 Медитация', sound: '🎵 Звук', nature: '🌿 Природа' };
                response.data.forEach(audio => {
                    container.innerHTML += `
                        <div class="card">
                            <h3>${audio.title}</h3>
                            <p>${types[audio.type] || 'Аудио'}</p>
                            <p>⏱️ ${Math.floor(audio.duration_seconds / 60)} мин</p>
                            <p>${audio.description || ''}</p>
                            <button onclick="showToast('Воспроизведение: ${audio.title} (демо-режим)')">▶ Воспроизвести</button>
                        </div>
                    `;
                });
            }
        }
        
        async function loadMoodHistory() {
            const response = await apiRequest('/mood');
            if (response.success) {
                const container = document.getElementById('mood-history');
                container.innerHTML = '';
                if (response.data.length === 0) {
                    container.innerHTML = '<p style="text-align: center; color: #6c757d;">Нет записей. Добавьте первую запись выше!</p>';
                    return;
                }
                response.data.forEach(mood => {
                    container.innerHTML += `
                        <div class="card">
                            <h3>${mood.date} ${mood.emoji || '😐'}</h3>
                            <p>Уровень: ${mood.mood_level}/10</p>
                            <p>${mood.note || 'Без заметки'}</p>
                        </div>
                    `;
                });
            }
        }
        
        async function saveMood() {
            const selectedBtn = document.querySelector('.mood-btn.selected');
            if (!selectedBtn) {
                showToast('Выберите настроение', true);
                return;
            }
            
            const moodLevel = selectedBtn.dataset.mood;
            const date = document.getElementById('mood-date').value;
            const note = document.getElementById('mood-note').value;
            
            if (!date) {
                showToast('Выберите дату', true);
                return;
            }
            
            const response = await apiRequest('/mood', { mood_level: moodLevel, date, note });
            if (response.success) {
                showToast(response.message);
                document.getElementById('mood-note').value = '';
                document.querySelectorAll('.mood-btn').forEach(btn => btn.classList.remove('selected'));
                
                if (response.recommendation) {
                    const recBox = document.getElementById('mood-recommendation');
                    recBox.innerHTML = response.recommendation;
                    recBox.style.display = 'block';
                    setTimeout(() => recBox.style.display = 'none', 5000);
                }
                
                loadMoodHistory();
            } else {
                showToast(response.error || 'Ошибка сохранения', true);
            }
        }
        
        async function loadTests() {
            const response = await apiRequest('/tests');
            if (response.success) {
                const container = document.getElementById('tests-list');
                container.innerHTML = '';
                response.data.forEach(test => {
                    container.innerHTML += `
                        <div class="card">
                            <h3>${test.title}</h3>
                            <p>${test.description}</p>
                            <p>📊 Вопросов: ${test.questions_count}</p>
                            <button onclick="showToast('Тест "${test.title}" будет доступен в следующей версии')">Пройти тест</button>
                        </div>
                    `;
                });
            }
        }
        
        async function loadPsychologists() {
            const response = await apiRequest('/psychologists');
            if (response.success) {
                const container = document.getElementById('psychologists-list');
                container.innerHTML = '';
                response.data.forEach(psych => {
                    container.innerHTML += `
                        <div class="card">
                            <h3>${psych.name} ⭐ ${psych.rating}</h3>
                            <p>📌 ${psych.specialization}</p>
                            <p>${psych.bio}</p>
                            <p>💰 ${psych.price} ₽ / сессия</p>
                            <button onclick="showToast('Запись к ${psych.name} (демо-режим)')">📅 Записаться</button>
                        </div>
                    `;
                });
            }
        }
        
        async function loadChecklists() {
            const response = await apiRequest('/checklists');
            if (response.success) {
                const container = document.getElementById('checklists-list');
                container.innerHTML = '';
                response.data.forEach(list => {
                    let itemsHtml = '';
                    if (list.items) {
                        itemsHtml = '<ul style="margin-top: 10px; padding-left: 20px;">';
                        list.items.forEach(item => {
                            itemsHtml += `<li>${item}</li>`;
                        });
                        itemsHtml += '</ul>';
                    }
                    container.innerHTML += `
                        <div class="card">
                            <h3>✅ ${list.title}</h3>
                            <p>${list.description}</p>
                            ${itemsHtml}
                            <button onclick="showToast('Чек-лист "${list.title}" выполнен! 🎉')">Выполнить</button>
                        </div>
                    `;
                });
            }
        }
        
        async function loadChatHistory() {
            const response = await apiRequest('/ai/history');
            if (response.success) {
                const container = document.getElementById('chat-messages');
                container.innerHTML = '';
                response.data.forEach(msg => {
                    if (msg.message) {
                        container.innerHTML += `
                            <div class="message user">
                                <div class="message-content">${escapeHtml(msg.message)}</div>
                            </div>
                        `;
                    }
                    if (msg.response) {
                        container.innerHTML += `
                            <div class="message ai">
                                <div class="message-content">${escapeHtml(msg.response)}</div>
                            </div>
                        `;
                    }
                });
                container.scrollTop = container.scrollHeight;
            }
        }
        
        async function sendMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;
            
            const container = document.getElementById('chat-messages');
            container.innerHTML += `<div class="message user"><div class="message-content">${escapeHtml(message)}</div></div>`;
            input.value = '';
            container.scrollTop = container.scrollHeight;
            
            const response = await apiRequest('/ai/chat', { message });
            if (response.success) {
                container.innerHTML += `<div class="message ai"><div class="message-content">${escapeHtml(response.data.response)}</div></div>`;
            } else {
                container.innerHTML += `<div class="message ai"><div class="message-content">😔 Извините, произошла ошибка. Попробуйте позже.</div></div>`;
            }
            container.scrollTop = container.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Выбор настроения
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
            });
        });
        
        // Установка даты по умолчанию
        document.getElementById('mood-date').value = new Date().toISOString().split('T')[0];
        
        // Навигация по вкладкам
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                document.getElementById(tab + '-pane').classList.add('active');
                if (tab === 'mood') loadMoodHistory();
            });
        });
        
        // Проверка сохраненного токена
        const savedToken = localStorage.getItem('token');
        if (savedToken) {
            currentToken = savedToken;
            apiRequest('/profile').then(response => {
                if (response.success) {
                    currentUser = response.data;
                    loadApp();
                } else {
                    logout();
                }
            });
        }
    </script>
</body>
</html>