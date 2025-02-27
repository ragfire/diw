<?php declare(strict_types=1);

namespace DIW\Commands;


use Silly\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class DockerShellCommand implements CommandInterface
{
    public static function command(Application $app): void
    {
        $app->command('shell', function (InputInterface $input, OutputInterface $output) {
            $io = new SymfonyStyle($input, $output);
            if(!isDockerRunning($io)) {
                return;
            }

            $containerSuffix = $_ENV['config']['docker']['container']['suffix'];
            $containerUser = $_ENV['config']['docker']['container']['user'];

            $containerID = removeSpaces(execCommand(\sprintf('docker ps -aqf "name=%s$"', $containerSuffix)));

            if (!$containerID) {
                $io->error('No docker container found for the container suffix: ' . $containerSuffix);
                return;
            }

            if ($containerUser) {
                $command = \sprintf('docker exec -it -u %s %s bash', $containerUser, $containerID);
            } else {
                $command = \sprintf('docker exec -it %s bash', $containerID);
            }

            passthruCommand('echo ' . $command . '| pbcopy');
//            passthruCommand($command); # disabled due to stdin shell output format bug

            $io->success('Command has been copied to your clipboard. Insert and execute it');

        })->descriptions('Connects to the *__shop container');
    }
}
