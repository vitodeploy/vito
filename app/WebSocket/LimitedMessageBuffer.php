<?php

namespace App\WebSocket;

use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\FrameInterface;
use Ratchet\RFC6455\Messaging\MessageBuffer;

/**
 * Ratchet's payload limits don't count frames, so a client could send endless
 * empty fragments that are all kept in memory. This caps fragments per message.
 */
class LimitedMessageBuffer extends MessageBuffer
{
    public const MAX_FRAGMENTS = 128;

    protected int $fragments = 0;

    public function frameCheck(FrameInterface $frame): FrameInterface
    {
        $frame = parent::frameCheck($frame);

        if ($frame->getOpcode() > Frame::OP_BINARY) {
            return $frame;
        }

        if (++$this->fragments > self::MAX_FRAGMENTS) {
            return $this->newCloseFrame(Frame::CLOSE_TOO_BIG, 'Too many fragments');
        }

        if ($frame->isFinal()) {
            $this->fragments = 0;
        }

        return $frame;
    }
}
