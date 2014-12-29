<?php

namespace Formativ;

use Exception;
use Ratchet\ConnectionInterface as Connection;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

class TokenService
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function create(array $data)
    {
        return [
            "status" => "ok",
            "data" => [
                "token" => "new token",
            ],
        ];
    }
}

class PostService
{
    /**
     * @param string $token
     * @param array  $data
     *
     * @return array
     */
    public function index($token, array $data)
    {
        if ($token === false) {
            return [
                "status" => "error",
            ];
        }

        return [
            "status" => "ok",
            "data" => [
                "post #1",
                "post #2",
                "post #3",
            ],
        ];
    }
}

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
     * @param Connection $connection
     */
    public function onOpen(Connection $connection)
    {
        $this->connections->attach($connection);
    }

    /**
     * @param Connection $connection
     * @param string     $message
     */
    public function onMessage(Connection $connection, $message)
    {
        $message = json_decode($message, true);

        if ($this->getTypeFrom($message) === "tokens.create") {
            $service = new TokenService();

            $response = $service->create(
                $this->getDataFrom($message)
            );

            if ($this->getStatusFrom($response) === "ok") {
                $this->connections[$connection] = [
                    "data" => [
                        "token" => $this->getTokenFrom($response),
                    ],
                ];

                return $this->respondWithOk($connection, "tokens.create");
            }

            return $this->respondWithError($connection, "tokens.create");
        }

        if ($this->getTypeFrom($message) === "posts.index") {
            $meta = $this->connections[$connection];

            if (empty($meta)) {
                $meta = [];
            }

            $service = new PostService();

            $response = $service->index(
                $this->getTokenFrom($meta),
                $this->getDataFrom($message)
            );

            if ($this->getStatusFrom($response) === "ok") {
                return $this->respondWithOk(
                    $connection,
                    "posts.index",
                    $this->getDataFrom($response)
                );
            }

            return $this->respondWithError(
                $connection,
                "posts.index"
            );
        }
    }

    /**
     * @param Connection $connection
     * @param string     $type
     * @param string     $status
     * @param array      $data
     */
    protected function respond($connection, $type, $status, array $data = [])
    {
        $message = [
            "type" => $type,
            "status" => $status,
        ];

        if (count($data) > 0) {
            $message["data"] = $data;
        }

        $connection->send(json_encode($message));
    }

    /**
     * @param Connection $connection
     * @param string     $type
     * @param array      $data
     */
    protected function respondWithOk($connection, $type, array $data = [])
    {
        $this->respond($connection, $type, "ok", $data);
    }

    /**
     * @param Connection $connection
     * @param string     $type
     * @param array      $data
     */
    protected function respondWithError($connection, $type, array $data = [])
    {
        $this->respond($connection, $type, "error", $data);
    }

    /**
     * @param array  $message
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getFrom($message, $key, $default = null)
    {
        if (isset($message[$key])) {
            return $message[$key];
        }

        return $default;
    }

    /**
     * @param array $message
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getTypeFrom(array $message, $default = "unknown")
    {
        return $this->getFrom($message, "type", $default);
    }

    /**
     * @param array $message
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getDataFrom(array $message, $default = [])
    {
        return $this->getFrom($message, "data", $default);
    }

    /**
     * @param array $message
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getStatusFrom(array $message, $default = [])
    {
        return $this->getFrom($message, "status", $default);
    }

    /**
     * @param array $message
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getTokenFrom(array $message, $default = false)
    {
        if (isset($message["data"])) {
            return $this->getFrom($message["data"], "token", $default);
        }

        return $default;
    }

    /**
     * @param Connection $connection
     */
    public function onClose(Connection $connection)
    {
        $this->connections->detach($connection);
    }

    /**
     * @param Connection $connection
     * @param Exception  $exception
     */
    public function onError(Connection $connection, Exception $exception)
    {
        $connection->close();
    }
}