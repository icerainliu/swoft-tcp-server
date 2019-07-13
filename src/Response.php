<?php declare(strict_types=1);

namespace Swoft\Tcp\Server;

use ReflectionException;
use Swoft;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Tcp\Protocol;
use Swoft\Tcp\Server\Contract\ResponseInterface;
use Swoft\Tcp\Server\Exception\TcpResponseException;
use Swoole\Server;

/**
 * Class Response
 *
 * @since 2.0
 * @Bean(name="tcpResponse", scope=Bean::PROTOTYPE)
 */
class Response extends \Swoft\Tcp\Response implements ResponseInterface
{
    /**
     * Response fd
     *
     * @var int
     */
    private $fd = -1;

    /**
     * Request fd
     *
     * @var int
     */
    private $reqFd = -1;

    /**
     * @var bool
     */
    private $sent = false;

    /**
     * @param int $fd
     *
     * @return self
     * @throws ContainerException
     * @throws ReflectionException
     */
    public static function new(int $fd = -1): self
    {
        /** @var self $self */
        $self = bean('tcpResponse');

        // Set properties
        $self->fd    = $fd;
        $self->sent  = false;
        $self->reqFd = $fd;

        return $self;
    }

    /**
     * @param Server|null $server
     *
     * @return int
     * @throws ContainerException
     * @throws ReflectionException
     * @throws TcpResponseException
     */
    public function send(Server $server = null): int
    {
        if ($this->sent) {
            return 0;
        }

        $server = $server ?: Swoft::server()->getSwooleServer();

        if (!$str = $this->content) {
            /** @var Protocol $protocol */
            $protocol = \bean('tcpServerProtocol');
            $protocol->packing($this);
        }

        if ($server->send($this->fd, $this->data) === false) {
            $code = $server->getLastError();
            throw new TcpResponseException("Error on send data to client #{$this->fd}", $code);
        }

        return 1;
    }

    /**
     * @return int
     */
    public function getReqFd(): int
    {
        return $this->reqFd;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->fd;
    }

    /**
     * @param int $fd
     */
    public function setFd(int $fd): void
    {
        $this->fd = $fd;
    }
}
