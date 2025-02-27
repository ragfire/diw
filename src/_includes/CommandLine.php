<?php declare(strict_types=1);


use Symfony\Component\Process\Process;


/**
 * Simple global function to run commands.
 *
 * @param string $command
 *
 * @return void
 */
function quietly(string $command): void
{
    runCommand($command . ' > /dev/null 2>&1');
}

function execCommand(string $command)
{
    return shell_exec($command);
}

/**
 * Simple global function to run commands.
 *
 * @param string $command
 *
 * @return void
 */
function quietlyAsUser(string $command): void
{
    quietly('sudo -u ' . user() . ' ' . $command . ' > /dev/null 2>&1');
}

/**
 * Pass the command to the command line and display the output.
 *
 * @param string $command
 *
 * @return void
 */
function passthruCommand(string $command): void
{
    passthru($command);
}

/**
 * Run the given command.
 *
 * @param string $command
 * @param callable|null $onError
 *
 * @return string
 */
function runAsUser(string $command, callable $onError = null): string
{
    return runCommand('sudo -u ' . user() . ' ' . $command, $onError);
}

/**
 * Run the given command as the non-root user.
 *
 * @param string $command
 * @param callable|null $onError
 *
 * @return string
 */
function run(string $command, callable $onError = null): string
{
    return runCommand($command, $onError);
}

/**
 * Run the given command.
 *
 * @param string $command
 * @param callable|null $onError
 *
 * @return string
 */
function runCommand(string $command, callable $onError = null): string
{
    $onError = $onError ?: static function() {};

    $process = new Process([$command]);

    $processOutput = '';
    $process->setTimeout(null)->run(function($type, $line) use (&$processOutput) {
        $processOutput .= $line;
    });

    if ($process->getExitCode() > 0) {
        $onError($process->getExitCode(), $processOutput);
    }

    return $processOutput;
}
