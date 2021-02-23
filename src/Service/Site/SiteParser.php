<?php
declare(strict_types=1);

namespace App\Service\Site;

use App\Service\Parser;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SiteParser implements SiteParserInterface
{
    protected Parser $parser;
    protected OutputInterface $output;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function parse(OutputInterface $output): void
    {
        $this->output = $output;

        $site = strtolower(array_slice(explode('\\', get_class($this)), -1)[0]);

        $this->parser->init($site);
        $this->parser->setOutput($output);
    }

    protected function css(string $css, bool $allwaysArray = false): string|array
    {
        return $this->parser->css($css, $allwaysArray);
    }

    protected function getUrl(string $url, string $method = 'GET', array $content = []): string
    {
        return $this->parser->getUrl($url, $method, $content);
    }

    protected function write(string $messages, bool $newline = false, int $options = 0): void
    {
        $this->output->write($messages, $newline, $options);
    }

    protected function writeln(string $messages = '', int $options = 0): void
    {
        $this->output->writeln($messages, $options);
    }
}
