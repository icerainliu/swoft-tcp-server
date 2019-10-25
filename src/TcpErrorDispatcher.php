<?php declare(strict_types=1);

namespace Swoft\Tcp\Server;

use Swoft;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Error\ErrorManager;
use Swoft\Error\ErrorType;
use Swoft\Log\Helper\CLog;
use Swoft\Log\Helper\Log;
use Swoft\Tcp\Server\Contract\TcpConnectErrorHandlerInterface;
use Swoft\Tcp\Server\Contract\TcpReceiveErrorHandlerInterface;
use Throwable;

/**
 * Class TcpErrorDispatcher
 *
 * @since 2.0.3
 * @Bean()
 */
class TcpErrorDispatcher
{
    /**
     * @Inject()
     * @var ErrorManager
     */
    private $errManager;

    /**
     * @param Throwable $e
     * @param int       $fd
     */
    public function connectError(Throwable $e, int $fd): void
    {
        /** @var TcpConnectErrorHandlerInterface $errHandler */
        if ($errHandler = $this->errManager->match($e, ErrorType::TCP_CNT)) {
            $errHandler->handle($e, $fd);
            return;
        }

        $this->defaultHandle('Connect', $e);
    }

    /**
     * @param Throwable $e
     * @param Response  $response
     *
     * @return Response
     */
    public function receiveError(Throwable $e, Response $response): Response
    {
        /** @var TcpReceiveErrorHandlerInterface $errHandler */
        if ($errHandler = $this->errManager->match($e, ErrorType::TCP_RCV)) {
            return $errHandler->handle($e, $response);
        }

        $this->defaultHandle('Receive', $e);

        // Set response code and message
        $response->setCode($e->getCode());
        $response->setMsg($e->getMessage());

        return $response;
    }

    /**
     * @param Throwable $e
     * @param int       $fd
     */
    public function closeError(Throwable $e, int $fd): void
    {
        /** @var Swoft\Tcp\Server\Contract\TcpCloseErrorHandlerInterface $errHandler */
        if ($errHandler = $this->errManager->match($e, ErrorType::TCP_CLS)) {
            $errHandler->handle($e, $fd);
            return;
        }

        $this->defaultHandle('Close', $e);
    }

    /**
     * @param string    $typeName
     * @param Throwable $e
     * TODO use default error handler
     */
    private function defaultHandle(string $typeName, Throwable $e): void
    {
        Log::error($e->getMessage());
        CLog::error("Tcp %s Error(no handler, %s): %s\nAt File %s line %d\nTrace:\n%s", $typeName, get_class($e),
            $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    }
}
