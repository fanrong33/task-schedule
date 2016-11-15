<?php

class PriorityQueue implements Iterator, Countable {

    private $priority_task_queue_file   = 'priority_task_queue';
    private $highest_priority_task_file = 'highest_priority_task';
    private $data;
    private $mem;

    public function __construct() {
        $this->data = array();
        $this->mem = new Memcache;
        $this->mem->connect("127.0.0.1", 11211);
    }

    function init(){
        
        // 初始化优先级队列
        $map = $this->mem->get('priority_task_queue');

        // $map = file_get_contents($this->priority_task_queue_file);
        // $map = json_decode($map, true);

        if($map){
            $this->data = $map; // 已排序
        }else{
            $this->data = array();
        }
    }

    function compare($priority1, $priority2) {}

    function count() {
        return count($this->data);
    }

    function insert($name, $priority) {
        if(is_array($name)){
            $name = json_encode($name);
        }

        $this->data[$name] = $priority;
        asort($this->data);


        // flush();
        $highest_priority_task = key($this->data);
        
	    //if(count($this->data) == 0){
        //    $this->data = null;
        //}

        // 保存priority queue到文件
        // $d = $this->save_write_file($this->priority_task_queue_file, json_encode($this->data));
        $this->mem->set("priority_task_queue", $this->data, 0, 0);


        // 保存下一个待运行的current到内存缓存
        // $d2 = $this->save_write_file($this->highest_priority_task_file, $highest_priority_task);
        $this->mem->set('highest_priority_task', $highest_priority_task, 0, 0);

        return $this;
    }

    function extract() {
        $current = $this->current();
        var_dump('current: '. $current);

        //if(empty($current)){
        //    return null;
        //}

        // 如果提取成功，调度队列中会清除这个任务
        $key = key($this->data);
        var_dump('key: '. $key);

        var_dump($this->data);
	
	    $this->next();
        unset($this->data[$key]);
        //$this->next();

        var_dump($this->data);

        // flush();
        $highest_priority_task = key($this->data);

	    if(count($this->data) == 0){
            $this->data = null;
        }
        // 保存priority queue到文件
        // $d =$this->save_write_file($this->priority_task_queue_file, json_encode($this->data));
        $this->mem->set("priority_task_queue", $this->data, 0, 0);

        // 保存下一个待运行的current到内存缓存
        // $d2 = $this->save_write_file($this->highest_priority_task_file, $highest_priority_task);
        $this->mem->set('highest_priority_task', $highest_priority_task, 0, 0);

        return array($key, $current);
    }

    
    function current() {
        return current($this->data);
    }

    function key() {
        return key($this->data);
    }

    function next() {
        return next($this->data);
    }

    function isEmpty() {
        return empty($this->data);
    }

    function recoverFromCorruption() {}

    function rewind() { reset($this->data); }

    function valid() {
        return (null === key($this->data)) ? false : true;
    }


    function getHighestPriorityTask(){
        // $task = file_get_contents($this->highest_priority_task_file);
        // $task = json_decode($task, true);
        $task = $this->mem->get('highest_priority_task');
    	$task = json_decode($task, true);
        return $task;
    }

    /**
     * PHP文件加锁确保多线程写入安全
     */
    function save_write_file($filename, $content){
        $lock = $filename . '.lck';
        $write_length = 0;
        while(true) {
            if( file_exists($lock) ) {
                usleep(100);
            } else {
                touch($lock);
                $write_length = file_put_contents($filename, $content);
                break;
            }
        }
        if( file_exists($lock) ) {
            unlink($lock);
        }
        return $write_length;
    }
}
