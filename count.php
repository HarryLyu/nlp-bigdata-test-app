<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';

$dbConn = new \Simplon\Mysql\Mysql($config['db']['host'],$config['db']['user'],$config['db']['password'],$config['db']['database']);

$result = [
    'gender' => [],
    'age' => [],
    'top5_interest' => []
];

$gender_distribution = $dbConn->fetchRowMany('SELECT sex, COUNT(*) as count FROM members GROUP BY sex ORDER BY count DESC;');
$gender_id_to_string = [
    1 => 'female',
    2 => 'male',
    0 => '?'
];

foreach ($gender_distribution as $gender) {
    $result['gender'][$gender_id_to_string[$gender['sex']]] = $gender['count'];
}

$date_format = 'Y-m-d';
$column_name = '`birth_date`';
$age_dates = [
    '<=10' =>   "{column} >= '" . date($date_format, strtotime("-10 years", time())) . "'",
    '11-20' =>  "{column} >= '" .
                date($date_format, strtotime("-20 years", time())) .
                "' AND {column} < '" .
                date($date_format, strtotime("-10 years", time())) .
                "'",
    '21-30' =>  "{column} >= '" .
                date($date_format, strtotime("-30 years", time())) .
                "' AND {column} < '" .
                date($date_format, strtotime("-20 years", time())) .
                "'",
    '>31' => "{column} < '" . date($date_format, strtotime("-30 years", time())) . "'",
    '?' => "{column} IS NULL"
];

$total = 0;

foreach ($age_dates as $name => $condition) {
    $query = str_ireplace('{column}', $column_name, "SELECT COUNT(*) as count FROM `members` WHERE " . $condition);
    $age_distribution = $dbConn->fetchRow($query);
    $result['age'][$name] = $age_distribution['count'];
}

$interests_distribution = $dbConn->fetchRowMany('SELECT interest, COUNT( * ) AS count FROM interests GROUP BY interest ORDER BY count DESC LIMIT 0 , 6');

$interests = [];
foreach ($interests_distribution as $interest) {
    if ($interest['interest'] == 'music') {
        continue;
    }
    $interests[] = $interest['interest'];
}

$result['top5_interest'] = $interests;

print_r(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));