<?php

class shopHelpimLogsModel extends waModel
{
    protected $table = 'shop_helpim_logs';

    public function addLog($action, $code, $message)
    {
        $sql = 'INSERT INTO ' . $this->table . ' SET '
            . '`datetime`=NOW(),'
            . '`action`=:action,'
            . '`code`=:code,'
            . '`message`=:message';
        $data = array(
            'action' => $action,
            'code' => $code,
            'message' => $message,
        );
        return $this->exec($sql, $data);
    }

    public function getLogs($limit = 100, $offset = 0)
    {
        $byLimit = '';
        if ((int)$limit) {
            $byLimit = sprintf('LIMIT %d, %d', $offset, $limit);
        }
        
        $sql = sprintf('SELECT * FROM `%s` ORDER BY `datetime` DESC %s', $this->table, $byLimit);
        return $this->query($sql)->fetchAll();
    }
}
