<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org/)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org/)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.1.0
 * @license       https://opensource.org/licenses/MIT MIT License
 */
namespace Queue\Test\TestCase\Command;

use Cake\Core\Configure;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Class WorkerCommandTest
 * @package Queue\Test\TestCase\Command
 */
class WorkerCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    /**
     * Test that command description prints out
     */
    public function testDescriptionOutput()
    {
        $this->exec('worker --help');
        $this->assertOutputContains('Runs a queue worker');
    }

    /**
     * Test that queue will run for one second
     */
    public function testQueueProcessesStart()
    {
        Configure::write(['Queue' => [
                'default' => [
                    'queue' => 'default',
                    'url' => 'null:',
                ]
            ]
        ]);
        $this->exec('worker --max-runtime=1');
        $this->assertEmpty($this->getActualOutput());
    }
}