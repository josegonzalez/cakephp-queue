<?php
declare(strict_types=1);

namespace Cake\Queue\Test\TestCase\Job;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\Log\Log;
use Cake\Queue\Consumption\LimitAttemptsExtension;
use Cake\Queue\Consumption\LimitConsumedMessagesExtension;
use Cake\Queue\Queue\Processor as QueueProcessor;
use Cake\Queue\QueueManager;
use Cake\Queue\Test\TestCase\DebugLogTrait;
use Cake\TestSuite\TestCase;
use Enqueue\Consumption\ChainExtension;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use Psr\Log\NullLogger;
use TestApp\Job\MaxAttemptsIsThreeJob;
use TestApp\Job\RequeueJob;

class LimitAttemptsExtensionTest extends TestCase
{
    use DebugLogTrait;

    public function setUp(): void
    {
        parent::setUp();

        EventManager::instance()->setEventList(new EventList());
    }

    #[BeforeClass, After]
    public static function dropConfigs()
    {
        Log::drop('debug');
        QueueManager::drop('default');
    }

    public function testMessageShouldBeRequeuedIfMaxAttemptsIsNotSet()
    {
        $consume = $this->setupQueue();

        QueueManager::push(RequeueJob::class);

        $consume();

        $count = $this->debugLogCount('RequeueJob is requeueing');
        $this->assertGreaterThanOrEqual(10, $count);
    }

    public function testFailedEventIsFiredWhenMaxAttemptsIsExceeded()
    {
        $consume = $this->setupQueue();

        QueueManager::push(MaxAttemptsIsThreeJob::class, []);

        $consume();

        $this->assertEventFired('Consumption.LimitAttemptsExtension.failed');
    }

    public function testMessageShouldBeRequeuedUntilMaxAttemptsIsReached()
    {
        $consume = $this->setupQueue();

        QueueManager::push(MaxAttemptsIsThreeJob::class, []);

        $consume();

        $this->assertDebugLogContainsExactly('MaxAttemptsIsThreeJob is requeueing', 3);
    }

    public function testMessageShouldBeRequeuedIfGlobalMaxAttemptsIsNotSet()
    {
        $consume = $this->setupQueue();

        QueueManager::push(RequeueJob::class);

        $consume();

        $count = $this->debugLogCount('RequeueJob is requeueing');
        $this->assertGreaterThanOrEqual(10, $count);
    }

    public function testMessageShouldBeRequeuedUntilGlobalMaxAttemptsIsReached()
    {
        $consume = $this->setupQueue([3]);

        QueueManager::push(MaxAttemptsIsThreeJob::class, ['succeedAt' => 10]);

        $consume();

        $this->assertDebugLogContainsExactly('MaxAttemptsIsThreeJob is requeueing', 3);
    }

    protected function setupQueue($extensionArgs = [])
    {
        Log::setConfig('debug', [
            'className' => 'Array',
            'levels' => ['debug'],
        ]);

        QueueManager::setConfig('default', [
            'url' => 'file:///' . TMP . DS . uniqid('queue'),
            'receiveTimeout' => 100,
        ]);

        $client = QueueManager::engine('default');

        $processor = new QueueProcessor(new NullLogger());
        $client->bindTopic('default', $processor);

        $extension = new ChainExtension([
            new LimitConsumedMessagesExtension(1),
            new LimitAttemptsExtension(...$extensionArgs),
        ]);

        return function () use ($client, $extension) {
            $client->consume($extension);
        };
    }
}
