<?php

namespace bupy7\notify\ss\base;

use Yii;
use yii\base\Component;
use bupy7\notify\ss\forms\Notification;
use bupy7\notify\ss\Module;
use yii\db\Connection;

/**
 * Abstract class of manager notification component.
 * @author Belosludcev Vasilij <https://github.com/bupy7>
 * @since 1.0.0
 */
abstract class Manager extends Component
{
    /**
     * @var Notification[] Added notifications which will be flush to database.
     */
    protected $collection = [];
    /**
     * @var Module Instance of notify module class.
     */
    protected $module;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->module = Module::getInstance();
        register_shutdown_function([$this, 'flush']);
    }
    
    /**
     * Flush notifications to database.
     * @return boolean
     */
    public function flush()
    {
        if (!empty($this->collection)) {
            list($sql, $params) = $this->prepareQuery();
            if (!(bool)$this->getDb()->createCommand($sql, $params)->execute()) {
                return false;
            }
            $this->collection = [];
        }
        return true;
    }
    
    /**
     * Prepare SQL query uses collection of notification.
     * @return string
     */
    protected function prepareQuery()
    {
        $queryBuilder = $this->getDb()->getQueryBuilder();    
        $sql = [];
        $params = [];
        for ($i = 0; $i != count($this->collection); $i++) {
            $row = $this->collection[$i]->attributes;
            $sql[] = $queryBuilder->insert($this->getTableName(), $row, $params);
        }       
        return [implode(';' . PHP_EOL, $sql), $params];
    }
    
    /**
     * Adding notification to collection.
     * @param integer $type Type of notification.
     * @param integer $recipient Id of recipient user.
     * @param string $message Message of notification.
     * @param string $title Title of notification.
     * @param array $params Custom params of notification.
     * @return static
     */
    protected function add($type, $recipient, $message, $title, $params = [])
    {
        $notification = new Notification([
            'type' => $type,
            'recipient' => $recipient,
            'message' => $message,
            'title' => $title,
            'unread' => true,
            'created_at' => time(),
            'params' => $params,
        ]);
        if ($notification->validate()) {
            $notification->params = serialize($notification->params);
            $this->collection[] = $notification;
        }
        return $this;
    }
    
    private $_db = null;
    
    /**
     * @return Connection Database connection.
     */
    protected function getDb()
    {
        if ($this->_db === null) {
            $this->_db = $this->module->db;
        }
        return $this->_db;
    }
    
    private $_tableName = null;
    
    /**
     * @return string Table name of notification module.
     */
    protected function getTableName()
    {
        if ($this->_tableName === null) {
            $this->_tableName = $this->module->tableName;
        }
        return $this->_tableName;
    }
}

