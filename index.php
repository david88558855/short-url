<?php
// index.php
define('ADMIN_PATH', '/short');
define('API_PATH', '/api');
define('URL_KEY', 'longUrl');
define('URL_NAME', 'shortCode');
define('SHORT_URL_KEY', 'shorturl');

function getChinaTime() {
    $now = new DateTime("now", new DateTimeZone('Asia/Shanghai'));
    return $now->format('Y-m-d H:i:s');
}

function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function getPostData() {
    return json_decode(file_get_contents('php://input'), true);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path == ADMIN_PATH) {
    // 从shortlink获取short_rules数据
    $shortRulesData = json_decode(file_get_contents('short_rules.json'), true);
        $today_new_rules = (int)$shortRulesData['today_new_rules'];
        $todayVisits = (int)$shortRulesData['today_visits'];
        $lastVisitsUpdate = $shortRulesData['last_visits_update'];
        $last_rule_update = $shortRulesData['last_rule_update'];
        $today = (new DateTime("now", new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');

        if ($lastVisitsUpdate !== $today || $last_rule_update !== $today) {
            $todayVisits = 0;
            $today_new_rules = 0;
            $shortRulesData['today_new_rules'] = (string)$today_new_rules;
            $shortRulesData['today_visits'] = (string)$todayVisits;
            $shortRulesData['last_visits_update'] = $today;
            $shortRulesData['last_rule_update'] = $today;

            file_put_contents('short_rules.json', json_encode($shortRulesData));
        }

    if (!$shortRulesData) {
        $shortRulesData = [
            'total_rules' => '0',
            'today_new_rules' => '0',
            'total_visits' => '0',
            'today_visits' => '0',
        ];
        file_put_contents('short_rules.json', json_encode($shortRulesData));
    } else {
        if (!isset($shortRulesData['total_rules'])) {
            $shortRulesData['total_rules'] = '0';
        }
        if (!isset($shortRulesData['today_new_rules'])) {
            $shortRulesData['today_new_rules'] = '0';
        }
        if (!isset($shortRulesData['total_visits'])) {
            $shortRulesData['total_visits'] = '0';
        }
        if (!isset($shortRulesData['today_visits'])) {
            $shortRulesData['today_visits'] = '0';
        }
        if (!isset($shortRulesData['last_rule_update'])) {
            $shortRulesData['last_rule_update'] = '2024';
        }
                if (!isset($shortRulesData['last_rule_update'])) {
                        $shortRulesData['last_rule_update'] = '2024';
                }
        file_put_contents('short_rules.json', json_encode($shortRulesData));
    }

    // 设置默认值为0
    $totalRules = $shortRulesData['total_rules'];
    $todayNewRules = $shortRulesData['today_new_rules'];
    $totalVisits = $shortRulesData['total_visits'];
    $todayVisits = $shortRulesData['today_visits'];

    ob_start();
    include('template.html');
    $indexWithStats = ob_get_clean();
    echo str_replace(["{{totalRules}}", "{{todayNewRules}}", "{{totalvisits}}", "{{todayvisits}}"], [$totalRules, $todayNewRules, $totalVisits, $todayVisits], $indexWithStats);
    exit;
}

if ($path == API_PATH) {
    $body = getPostData();
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $short_type = $body['type'] ?? 'link';
    $body[URL_NAME] = $body[URL_NAME] ?? substr(md5(uniqid()), 0, 6);
    // 从请求体中获取数据
    $body = json_decode(file_get_contents('php://input'), true);

    // 如果没有提供后缀，生成一个随机后缀
    if (empty($body[URL_NAME])) {
    $body[URL_NAME] = generateRandomString();
    }
    if ($body[URL_NAME] === 'api') {
    echo json_encode(['error' => '错误！该后缀为API调用，请使用其他后缀。'], JSON_UNESCAPED_UNICODE);
    exit;
    }
    if (empty($body[URL_KEY])) {
    echo json_encode(['error' => '错误！长网址或文本或html源代码不能为空。'], JSON_UNESCAPED_UNICODE);
    exit;
    }
    // 检查文件夹是否存在，如果不存在则创建
    $folderPath = "shortlinks";
    if (!file_exists($folderPath)) {
    mkdir($folderPath, 0777, true); // 创建文件夹，0777 是文件夹的权限设置，true 表示递归创建所有需要的文件夹
    }

    $existingData = file_exists("shortlinks/{$body[URL_NAME]}.json") ? json_decode(file_get_contents("shortlinks/{$body[URL_NAME]}.json"), true) : null;
    $isNewRule = !$existingData;

    if ($existingData && $existingData['password'] && $existingData['password'] !== $body['password']) {
        echo json_encode(['error' => '密码错误！该后缀已经被使用，请使用正确的密码修改或使用其他后缀。'], JSON_UNESCAPED_UNICODE);        exit;
    }

    $expiration = (int)$body['expiration'];
    $expiresAt = $expiration > 0 ? (new DateTime("now", new DateTimeZone('Asia/Shanghai')))->modify("+{$expiration} minutes")->format('Y-m-d H:i:s') : null;
        
        // 假设 $body 是一个包含链接数据的数组
        if ($short_type == "link" && strpos($body[URL_KEY], 'http') !== 0) {
         $body[URL_KEY] = 'http://' . $body[URL_KEY];
         }
    $linkData = [
        'lastUpdate' => getChinaTime(),
        'clientIp' => $clientIp,
        'type' => $short_type,
        'value' => $body[URL_KEY],
        'password' => $body['password'],
        'expiresAt' => $expiresAt,
        'burn_after_reading' => $body['burn_after_reading']
    ];

    $jsonString = json_encode($linkData, JSON_UNESCAPED_UNICODE);
    file_put_contents("shortlinks/{$body[URL_NAME]}.json", $jsonString);
    if ($isNewRule) {
        $shortRulesData = json_decode(file_get_contents('short_rules.json'), true);
        // 获取 shortlinks 文件夹中的 .json 文件数量
        $files = glob('shortlinks/*.json');
        $totalRules = count($files);
        $todayNewRules = (int)$shortRulesData['today_new_rules'];
        $lastRuleUpdate = $shortRulesData['last_rule_update'];
        $today = (new DateTime("now", new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');

        if ($lastRuleUpdate !== $today) {
            $todayNewRules = 0;
        }

        
        $todayNewRules += 1;

        $shortRulesData['total_rules'] = (string)$totalRules;
        $shortRulesData['today_new_rules'] = (string)$todayNewRules;
        $shortRulesData['last_rule_update'] = $today;

        file_put_contents('short_rules.json', json_encode($shortRulesData));
    }

    $responseBody = [
        'type' => $body['type'],
        SHORT_URL_KEY => "http://{$_SERVER['HTTP_HOST']}/{$body[URL_NAME]}",
        URL_NAME => $body[URL_NAME],
    ];

    echo json_encode($responseBody);
    exit;
}

$key = ltrim($path, '/');
$key = urldecode($key);
if ($key !== "" && !file_exists("shortlinks/{$key}.json")) {
    header("Location: " . ADMIN_PATH, true, 302);
    exit;
}

if ($key === "") {
    echo "
<!DOCTYPE html>
<html lang='zh-CN'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>404 错误</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .error-container {
            text-align: center;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .error-container h1 {
            font-size: 120px;
            font-weight: 600;
            color: #e74c3c;
        }
        .error-container p {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
        }
        .error-container a {
            font-size: 18px;
            color: #007bff;
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid #007bff;
            border-radius: 30px;
            transition: all 0.3s;
        }
        .error-container a:hover {
            background-color: #007bff;
            color: #fff;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>404</h1>
        <p>哎呀，您访问的页面不存在！</p>
        <a href='" . ADMIN_PATH . "'>返回主页</a>
    </div>
</body>
</html>
";
    exit;
}

$link = json_decode(file_get_contents("shortlinks/{$key}.json"), true);
if ($link) {
    $expiresAt = isset($link['expiresAt']) ? new DateTime($link['expiresAt'], new DateTimeZone('Asia/Shanghai')) : null;
    $now = new DateTime("now", new DateTimeZone('Asia/Shanghai'));
    if ($expiresAt && $now >= $expiresAt) {
        echo "链接已过期";
        unlink("shortlinks/{$key}.json");
        exit;
    }

    if ($link['burn_after_reading'] === "true") {
    unlink("shortlinks/{$key}.json");
    }

        $shortRulesData = json_decode(file_get_contents('short_rules.json'), true);
        $totalVisits = (int)$shortRulesData['total_visits'];
        $todayVisits = (int)$shortRulesData['today_visits'];
        $lastVisitsUpdate = $shortRulesData['last_visits_update'];
        $today = (new DateTime("now", new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');

        if ($lastVisitsUpdate !== $today) {
            $todayVisits = 0;
        }

        $totalVisits += 1;
        $todayVisits += 1;

        $shortRulesData['total_visits'] = (string)$totalVisits;
        $shortRulesData['today_visits'] = (string)$todayVisits;
        $shortRulesData['last_visits_update'] = $today;

        file_put_contents('short_rules.json', json_encode($shortRulesData));

    if ($link['type'] === 'link') {
        header("Location: {$link['value']}", true, 302);
        exit;
    }

    if ($link['type'] === 'html') {
        header("Content-Type: text/html; charset=utf-8");
        echo $link['value'];
        exit;
    } else {
        header("Content-Type: text/plain; charset=utf-8");
        echo "{$link['value']}";
exit;
    }
}

http_response_code(403);
echo "403";
?>
