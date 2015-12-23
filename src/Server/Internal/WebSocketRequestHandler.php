<?php
namespace Icicle\WebSocket\Server\Internal;

use Icicle\Http\Message\Request;
use Icicle\Http\Server\RequestHandler;
use Icicle\Socket\Socket;
use Icicle\WebSocket\Application;
use Icicle\WebSocket\Protocol\ProtocolMatcher;

class WebSocketRequestHandler implements RequestHandler
{
    /**
     * @var \Icicle\Http\Server\RequestHandler
     */
    private $handler;

    /**
     * @var \Icicle\WebSocket\Protocol\ProtocolMatcher
     */
    private $matcher;

    /**
     * @param \Icicle\WebSocket\Protocol\ProtocolMatcher $matcher
     * @param \Icicle\Http\Server\RequestHandler $handler
     */
    public function __construct(ProtocolMatcher $matcher, RequestHandler $handler)
    {
        $this->matcher = $matcher;
        $this->handler = $handler;
    }

    /**
     * @coroutine
     *
     * @param \Icicle\Http\Message\Request $request
     * @param \Icicle\Socket\Socket $socket
     *
     * @return \Generator
     *
     * @resolve \Icicle\Http\Message\Response $response
     */
    public function onRequest(Request $request, Socket $socket)
    {
        $application = (yield $this->handler->onRequest($request, $socket));

        if (!$application instanceof Application) {
            yield $application; // Other response returned, let HTTP server handle it.
            return;
        }

        $response = (yield $this->matcher->createResponse($application, $request, $socket));

        yield $application->createResponse($response);
    }

    /**
     * @coroutine
     *
     * @param int $code
     * @param \Icicle\Socket\Socket $socket
     *
     * @return \Generator
     *
     * @resolve \Icicle\Http\Message\Response
     */
    public function onError($code, Socket $socket)
    {
        return $this->handler->onError($code, $socket);
    }
}
