<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/config.php';
exit;
$vk = new \BW\Vkontakte($config['vk']);
$dbConn = new \Simplon\Mysql\Mysql($config['db']['host'],$config['db']['user'],$config['db']['password'],$config['db']['database']);

require __DIR__ . '/prepare-db.php.php';
// get group info
$group = $vk->api('groups.getById', [
    'group_id' => $config['group_id'],
    'fields' => 'members_count'
])[0];
$dbConn->insert('groups', [
    'vk_id' => $group['id']
]);

$request_count = 1000;
$members_count = $group['members_count'];
$iterations_count = $members_count / $request_count;

for ($i = 0; $i < $iterations_count; $i++) {
    echo 'Fetching users ' . ($request_count * $i) . ' - ' . ($request_count * $i + $request_count) . PHP_EOL;

    $members = $vk->api('groups.getMembers', [
        'group_id' => $config['group_id'],
        'fields' => 'sex,bdate',
        'offset' => $request_count * $i,
        'count' => $request_count
    ]);

    echo 'Inserting them to database' . PHP_EOL;
    foreach ($members['items'] as $member) {
        $birth_date = null;
        if (isset($member['bdate'])) {
            $bdateParts = explode('.', $member['bdate']);
            $birth_date = count($bdateParts) == 3 ? implode('-', array_reverse($bdateParts)) : null;
        }

        $putMemeber = [
            'vk_id' => $member['id'],
            'sex' => $member['sex'],
            'birth_date' => $birth_date
        ];

        try {
            $dbConn->insert('members', $putMemeber);
        } catch (Exception $e) {}
    }
}

$dbConn->executeSql('TRUNCATE interests;');

$request_count = 200;
$members_count = $dbConn->fetchRow('SELECT count(*) as c from members;')['c'];
$iterations_count = $members_count / $request_count;

for ($i = 0; $i < $iterations_count; $i++) {
    echo 'Fetching users ' . ($request_count * $i) . ' - ' . ($request_count * $i + $request_count) . PHP_EOL;
    $members = $dbConn->fetchRowMany('SELECT vk_id from members ORDER by vk_id LIMIT :skip, :limit;', [
        'skip' => $request_count * $i,
        'limit' => $request_count
    ]);

    $vk_ids = [];

    foreach ($members as $member) {
        $vk_ids[] = $member['vk_id'];
    }

    $vk_ids_string = implode(',', $vk_ids);

    $users = $vk->api('users.get', [
        'user_ids' => $vk_ids_string,
        'fields' => 'interests'
    ]);

    if (!count($users)) {
        echo 'error 1 ';
        var_dump($users);
        var_dump($vk_ids_string);
        exit;
    }

    foreach ($users as $user) {
        if (!isset($user['interests'])) {
            continue;
        }
        $interests = explode(',', $user['interests']);

        if (count($interests) == 0) {
            continue;
        }

        if (count($interests) == 1 && $interests[0] == '') {
            continue;
        }

        foreach ($interests as $interest) {
            try {
                $dbConn->insert('interests', [
                    'user_id' => $user['id'],
                    'interest' => $interest
                ]);
            } catch (Exception $e) {}
        }
    }
}
$dbConn->executeSql('UPDATE interests SET interest = TRIM(interest)');
$dbConn->executeSql('UPDATE interests SET interest = LOWER(interest)');