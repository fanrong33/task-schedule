<?php
/*
setsid /usr/bin/php /data/www/taskschedule/command/cron.php 2>&1 > /dev/null &
队列
只负责定时执行，具体执行什么内容，由消息队列中的数据自己决定
*/

//////////////////////////////////////////////////////////////////////////////////////////
error_reporting(0);
set_time_limit(0);


// 执行定时任务（异步处理优先级队列）
require_once dirname(__FILE__).'/Core/TaskScheduler.class.php';
require_once dirname(__FILE__).'/Core/PriorityQueue.class.php';
require_once dirname(__FILE__).'/Core/MySQL.class.php';



$priority_queue = new PriorityQueue();
$task_scheduler = new TaskScheduler($priority_queue);


while(true){
    // 获取存储容器优先级最高的任务
    $task = $task_scheduler->getHighestPriorityTask();

    if($task && $task['schedule_time'] < time()){
        var_dump($task);

        $begin_time = microtime(true);

        // 执行任务，并将该任务优先级队列删除
        $result = api($task['url'], $task['data'], $task['type']);
        // echo 'api';

        $task_scheduler->init();
        $top = $task_scheduler->extract();
        // echo var_dump($top).'---->[out]';

        $end_time = microtime(true);

        // 解析任务队列中提取出来的消息，得到任务task
        $top_data = json_decode($top[0], true);
        // var_dump($top_data);

        if($top_data['run'] == 'REPEATED'){
            // $top_data = array(
            //     'type'      => 'POST',
            //     'url'       => 'http://fanrong33.com/cron_test.php', // 跨平台
            //     'data'      => array('name'=>'task schedule 12'),
            //     'time'      => 12, // 时间, 10秒后
            //     'run'       => 'REPEATED', // 任务类型，ONCE一次性任务或REPEATED长期定时任务
            //     'limit'     => 1, // 限制任务次数的周期任务，为执行任务的次数
            // );
            $timestamp = time() + $top_data['time'];
            $top_data['schedule_time'] = $timestamp;

            // 以时间戳为优先级，添加到优先级任务队列中
            $task_scheduler->insert($top_data,  $timestamp);
        }

        // 记录执行记录到数据库中
        $data = array(
            'run'                => $task['run'],
            'type'               => $task['type'],
            'url'                => $task['url'],
            'data'               => json_encode($task['data']),
            'result'             => json_encode($result),
            'schedule_time'      => $task['schedule_time'],
            's'                  => date('Y-m-d H:i:s', $task['schedule_time']),
            'execute_begin_time' => $begin_time,
            'execute_end_time'   => $end_time,
            'run_time'           => $end_time - $begin_time,
            'e'                  => date('Y-m-d H:i:s'), 
            'create_time'        => time(),
        );

        $mysql = new MySQL('2016_taskschedule', 'root', 'root', '127.0.0.1', '3306');
        $mysql->insert('t_task_result', $data);
        $mysql->closeConnection();
    }else{
        sleep(1);
    }

}




function api($url, $params, $method='GET'){
    if($method=='GET'){
        $result_str = http($url.'?'.http_build_query($params));
    }else{
        $result_str = http($url, http_build_query($params), 'POST');
    }
    $result = array();
    if($result_str != '') $result = json_decode($result_str, true);
    return $result;
}

function http($url, $postfields='', $method='GET', $headers=array()){
    $ci=curl_init();
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); 
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ci, CURLOPT_TIMEOUT, 30);
    if($method=='POST'){
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if($postfields!='')curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
    }
    $headers[] = "User-Agent: ".$_SERVER['HTTP_USER_AGENT'];
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);
    curl_close($ci);
    return $response;
}


?>
