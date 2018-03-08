<?php

namespace common\components;

use yii\db\Exception;
/**
 * Description of Connection
 *
 * @author Don.T
 */
class RedisConnection extends \yii\redis\Connection {
    
    /**
     * 设置兼容字符串和数组两种写法的命令
     */
    public $strOrArrCommands = [
        'sadd',
        'sinter',
    ];
    
    public function __call($name, $params) {
        if (in_array($name, $this->strOrArrCommands)) {
            if (count($params)===1 && is_array($params[0])) {
                $params = $params[0];
            } elseif (count($params)===2 && is_array($params[1])) {
                array_unshift($params[1], $params[0]);
                $params = $params[1];
            }
        }
        return parent::__call($name, $params);
    }
    
    public function open() {
        if ($this->_socket !== null) {
            return;
        }
        $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port);
        if ($this->database!=-1) {
            $connection .= ', database=' . $this->database;
        }
        \Yii::trace('Opening redis DB connection: ' . $connection, __METHOD__);
        $this->_socket = @stream_socket_client(
            $this->unixSocket ? 'unix://' . $this->unixSocket : 'tcp://' . $this->hostname . ':' . $this->port,
            $errorNumber,
            $errorDescription,
            $this->connectionTimeout ? $this->connectionTimeout : ini_get("default_socket_timeout")
        );
        if ($this->_socket) {
            if ($this->dataTimeout !== null) {
                stream_set_timeout($this->_socket, $timeout = (int) $this->dataTimeout, (int) (($this->dataTimeout - $timeout) * 1000000));
            }
            if ($this->password !== null) {
                $this->executeCommand('AUTH', [$this->password]);
            }
            if ($this->database!=-1) {
                $this->executeCommand('SELECT', [$this->database]);
            }
            $this->initConnection();
        } else {
            \Yii::error("Failed to open redis DB connection ($connection): $errorNumber - $errorDescription", __CLASS__);
            $message = YII_DEBUG ? "Failed to open redis DB connection ($connection): $errorNumber - $errorDescription" : 'Failed to open DB connection.';
            throw new Exception($message, $errorDescription, (int) $errorNumber);
        }
    }
}