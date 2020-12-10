<?php declare(strict_types=1);

namespace DIW\Commands;


use Httpful\Request;
use Silly\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class UpdateCommand implements CommandInterface
{
    private const GITHUB_LATEST_RELEASE_URL = 'https://api.github.com/repos/MarcoFaul/diw/releases/latest';
    private const DEV_VERSION = 'dev-9999';

    public static function command(Application $app): void
    {
        $app->command('self-update', function (InputInterface $input, OutputInterface $output) {
            $io = new SymfonyStyle($input, $output);

            $currentVersion = version()->getVersion();
            $isDevVersion = $currentVersion === UpdateCommand::DEV_VERSION;
            $response = Request::get(UpdateCommand::GITHUB_LATEST_RELEASE_URL)->send();

            $isLatestVersion = \version_compare($currentVersion, $response->body->tag_name, '>=');

            if (!$isDevVersion && $isLatestVersion === true) {
                $io->writeln('You are on the latest version: ' . $response->body->tag_name);
                return;
            }

            $helper = $this->getHelperSet()->get('question'); // @phpstan-ignore-line
            $question = new ConfirmationQuestion('Wonna update?', false, '/^(y|j|yes|yeah|jo|yes|jupp)/i');

            if ($helper->ask($input, $output, $question)) {
                $io->writeln('Updating...');

                #check if there is an override.config.xml
                $filesystem = new Filesystem();

                $io->writeln('Opening git tower');
                $overrideConfigFilePath = __DIR__ . '/../_config/override.config.yaml';
                if ($filesystem->exists($overrideConfigFilePath)) {
                    $filesystem->copy($overrideConfigFilePath, '/tmp');
                }

                quietly('brew upgrade ' . \strtolower(APP_NAME));

                if ($filesystem->exists('/tmp/override.config.yaml')) {
                    $filesystem->copy('/tmp/override.config.yaml', __DIR__ . '/../_config/');
                }

                $io->success(\sprintf('Upgrade diw from "%s" to latest version "%s"', $currentVersion, version()->getVersion()));
            }

        })->descriptions(\sprintf('Update %s Utility', \strtolower(APP_NAME)));
    }
}
