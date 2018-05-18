<?php

namespace apps\daemon\commands;

use mix\console\Command;
use mix\console\ExitCode;
use mix\facades\Error;
use mix\facades\Input;
use mix\facades\Output;
use mix\swoole\Process;

/**
 * 这是一个单进程守护进程的范例
 * @author 刘健 <coder.liu@qq.com>
 */
class SingleCommand extends Command
{

    // 是否后台运行
    public $daemon = false;

    // PID 文件
    const PID_FILE = '/var/run/single.pid';

    // 进程名称
    protected $processName;

    // 选项配置
    public function options()
    {
        return ['daemon'];
    }

    // 选项别名配置
    public function optionAliases()
    {
        return ['d' => 'daemon'];
    }

    // 初始化事件
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 获取进程名称
        $this->processName = Input::getCommandName();
    }

    // 启动
    public function actionStart()
    {
        // 重复启动处理
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            Output::writeln("mix-daemon '{$this->processName}' is running, PID : {$pid}.");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        // 启动提示
        Output::writeln("mix-daemon '{$this->processName}' start successed.");
        // 蜕变为守护进程
        if ($this->daemon) {
            Process::daemon();
        }
        // 写入 PID 文件
        Process::writePid(self::PID_FILE);
        // 修改进程名称
        Process::setName("mix-daemon: {$this->processName}");
        // 开始工作
        $this->startWork();
        // 返回退出码
        return ExitCode::OK;
    }

    // 停止
    public function actionStop()
    {
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            Process::kill($pid);
            while (Process::isRunning($pid)) {
                // 等待进程退出
                usleep(100000);
            }
            Output::writeln("mix-daemon '{$this->processName}' stop completed.");
        } else {
            Output::writeln("mix-daemon '{$this->processName}' is not running.");
        }
        // 返回退出码
        return ExitCode::OK;
    }

    // 重启
    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
        // 返回退出码
        return ExitCode::OK;
    }

    // 查看状态
    public function actionStatus()
    {
        if ($pid = Process::getMasterPid(self::PID_FILE)) {
            Output::writeln("mix-daemon '{$this->processName}' is running, PID : {$pid}.");
        } else {
            Output::writeln("mix-daemon '{$this->processName}' is not running.");
        }
        // 返回退出码
        return ExitCode::OK;
    }

    // 开始工作
    public function startWork()
    {
        try {
            $this->work();
        } catch (\Exception $e) {
            // 记录异常
            Error::write($e);
            // 休息一会，避免 cpu 出现 100%
            sleep(10);
            // 重建流程
            $this->startWork();
        }
    }

    // 执行工作
    public function work()
    {
        // 模型内使用长连接版本的数据库组件，这样组件会自动帮你维护连接不断线
        $tableModel = new \apps\common\models\TableModel();
        // 循环执行任务
        while (true) {
            // 执行业务代码
            // ...
        }
    }

}
