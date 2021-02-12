<?php

namespace App\Service\Site;

use App\Service\Parser;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SiteParser implements SiteParserInterface
{
    protected Parser $parser;
    protected OutputInterface $output;

    public function __construct()
    {
        $site = strtolower(array_slice(explode('\\', get_class($this)), -1)[0]);

        $this->parser = new Parser($site);
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function parse(OutputInterface $output): void
    {
        $this->output = $output;
        $this->parser->setOutput($output);

//        $this->parser = new Parser($site, $output);
//        $this->parser->rootUrl = $this->rootUrl;
    }
}
