<?php

namespace Formativ;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class Server implements MessageComponentInterface
{
    /**
     * @var SplObjectStorage
     */
    protected $connections;

    public function __construct()
    {
        $this->connections = new SplObjectStorage();
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this->connections->attach($connection);
    }

    /**
     * @param ConnectionInterface $connection
     * @param string              $message
     */
    public function onMessage(ConnectionInterface $connection, $message)
    {
        $connection->send($message);
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->connections->detach($connection);
    }

    /**
     * @param ConnectionInterface $connection
     * @param Exception           $exception
     */
    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        $connection->close();
    }
}