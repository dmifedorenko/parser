<?php

namespace App\Command;

use App\Kernel;
use App\Service\Site\Kafema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:parse';

    private Kafema $kafema;

    public function __construct(Kafema $kafema)
    {
        $this->kafema = $kafema;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('site', InputArgument::REQUIRED, 'The site to parse');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $site = $input->getArgument('site');
        $parser = Kernel::get()->getContainer()->get('App\Service\Site\\' . ucfirst($site));

        $parser->parse($output);
        return Command::SUCCESS;
    }
}