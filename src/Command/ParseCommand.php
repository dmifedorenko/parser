<?php

namespace App\Command;

use App\Kernel;
use App\Service\Site\Kafema;
use App\Service\Site\SiteParser;
use App\Service\YandexDisk;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:parse';

    private Kafema $kafema;
    private Kernel $app;

    public function __construct(Kafema $kafema, Kernel $app)
    {
        $this->kafema = $kafema;
        $this->app = $app;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('site', InputArgument::REQUIRED, 'The site to parse');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteName = $input->getArgument('site');

        if ($siteName == 'yandex-disk-test') {
            $yandexDisk = $this->app->getContainer()->get(YandexDisk::class);
            $yandexDisk->setAppName($siteName);
            $yandexDisk->upload('your.txt', '123456');
        } else {
            /**
             * @var SiteParser $site
             */
            $site = $this->app->getContainer()->get('App\Service\Site\\' . ucfirst($siteName));

            $parserConfig = $this->app->getContainer()->getParameter('parser')['sitesConfig'][mb_strtolower($siteName)] ?? [];

            foreach ($parserConfig as $name => $value) {
                $site->getParser()->{$name} = $value;
            }

            $site->parse($output);
            $site->getParser()->done();
        }

        return Command::SUCCESS;
    }
}
