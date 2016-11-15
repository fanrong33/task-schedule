<?php

// require_once 'PriorityQueue.class.php';

class TaskScheduler {

    protected $_queue = null;

    public function __construct($queue) {
        $this->_queue = $queue; // new PriorityQueue();
    }

    public function init(){
        $this->_queue->init();
    }

    public function insert($task, $priority){
        $this->_queue->insert($task, $priority);
    }

    public function extract(){
        return $this->_queue->extract();
    }

    public function getHighestPriorityTask(){
        return $this->_queue->getHighestPriorityTask();
    }

}


