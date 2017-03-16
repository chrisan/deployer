<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer;

use Deployer\Console\Application;
use Deployer\Host\Configuration;
use Deployer\Host\Host;
use Deployer\Host\Localhost;
use Deployer\Task\Context;
use Deployer\Task\GroupTask;
use Deployer\Task\Task;
use Deployer\Type\Result;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FunctionsTest extends TestCase
{
    /**
     * @var Deployer
     */
    private $deployer;

    /**
     * @var Application
     */
    private $console;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Host
     */
    private $host;

    protected function setUp()
    {
        $this->console = new Application();

        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->host = $this->getMockBuilder(Host::class)->disableOriginalConstructor()->getMock();
        $this->host
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class));

        $this->deployer = new Deployer($this->console, $this->input, $this->output);
        Context::push(new Context($this->host, $this->input, $this->output));
    }

    protected function tearDown()
    {
        Context::pop();
        unset($this->deployer);
        $this->deployer = null;
    }

    public function testHost()
    {
        host('domain.com');
        $this->assertInstanceOf(Host::class, $this->deployer->hosts->get('domain.com'));

        host('a1.domain.com', 'a2.domain.com')->set('roles', 'app');
        $this->assertInstanceOf(Host::class, $this->deployer->hosts->get('a1.domain.com'));
        $this->assertInstanceOf(Host::class, $this->deployer->hosts->get('a2.domain.com'));
    }

    public function testLocalhost()
    {
        localhost('domain.com');

        $this->assertInstanceOf(Localhost::class, $this->deployer->hosts->get('domain.com'));
    }

    public function testInventory()
    {
        inventory(__DIR__ . '/../fixture/inventory.yml');

        foreach (['app.deployer.org', 'beta.deployer.org'] as $hostname) {
            $this->assertInstanceOf(Host::class, $this->deployer->hosts->get($hostname));
        }
    }

    public function testTask()
    {
        task('task', 'pwd');

        $task = $this->deployer->tasks->get('task');
        $this->assertInstanceOf(Task::class, $task);

        $task = task('task');
        $this->assertInstanceOf(Task::class, $task);

        task('group', ['task']);
        $task = $this->deployer->tasks->get('group');
        $this->assertInstanceOf(GroupTask::class, $task);
    }

    public function testBefore()
    {
        task('main', 'pwd');
        task('before', 'ls');
        before('main', 'before');

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['before', 'main'], $names);
    }

    public function testAfter()
    {
        task('main', 'pwd');
        task('after', 'ls');
        after('main', 'after');

        $names = $this->taskToNames($this->deployer->getScriptManager()->getTasks('main'));
        $this->assertEquals(['main', 'after'], $names);
    }

    public function testRunLocally()
    {
        $output = runLocally('echo "hello"');

        $this->assertInstanceOf(Result::class, $output);
        $this->assertEquals('hello', (string)$output);
    }

    private function taskToNames($tasks)
    {
        return array_map(function (Task $task) {
            return $task->getName();
        }, $tasks);
    }
}
