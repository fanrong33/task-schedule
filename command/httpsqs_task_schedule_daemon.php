<?php
/*
setsid /usr/bin/php /data/www/taskschedule/command/httpsqs_task_schedule_daemon.php 2>&1 > /dev/null &
队列
异步处理添加任务的消息队列
*/

//////////////////////////////////////////////////////////////////////////////////////////
error_reporting(0);
set_time_limit(0);



include_once dirname(__FILE__)."/Core/httpsqs_client.php";
require_once dirname(__FILE__).'/Core/TaskScheduler.class.php';
require_once dirname(__FILE__).'/Core/PriorityQueue.class.php';

$httpsqs  = new httpsqs("127.0.0.1", 1218, "", "utf-8");
$priority_queue = new PriorityQueue();
$task_scheduler = new TaskScheduler($priority_queue);


while(true){
    $json = $httpsqs->gets("cron");
    $pos  = $json['pos'];
    $data = $json['data'];
    if($data && $data != 'HTTPSQS_GET_END' && $data != 'HTTPSQS_ERROR'){
        
        // 有消息，则处理，优先级队列->insert(data, proity) 保存到存储容器


        // 初始化优先级队列
        $task_scheduler->init();


        // 解析消息队列中的消息，得到任务task
        $queue_data = json_decode($data, true);

        // $queue_data = array(
        //     'type'      => 'POST',
        //     'url'       => 'http://fanrong33.com/cron_test.php', // 跨平台
        //     'data'      => array('name'=>'task schedule 12'),
        //     'time'      => 12, // 时间, 10秒后
        //     'run'       => 'ONCE', // 任务类型，一次性任务或长期定时任务
        //     'limit'     => 1, // 限制任务次数的周期任务，为执行任务的次数
        // );
        $timestamp  =  time() + $queue_data['time'];
        $queue_data['schedule_time'] = $timestamp;

        // 以时间戳为优先级，添加到优先级任务队列中
        $task_scheduler->insert($queue_data,  $timestamp);


    }else{
        sleep(1); // 暂停1秒钟后，再次循环
    }
}
/////////////////////////////////////////////////////////////////////////////////////////




?>
