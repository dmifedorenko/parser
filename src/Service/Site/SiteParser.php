<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Service\Parser\Parser;
use App\Service\YandexDisk;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SiteParser implements SiteParserInterface
{
    protected Parser $parser;
    protected OutputInterface $output;
    protected YandexDisk $yandexDisk;

    public function __construct(Parser $parser, YandexDisk $yandexDisk)
    {
        $this->parser = $parser;
        $this->yandexDisk = $yandexDisk;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function parse(OutputInterface $output): void
    {
        $this->output = $output;

        $site = strtolower(array_slice(explode('\\', static::class), -1)[0]);

        $this->parser->init($site);
        $this->parser->setOutput($output);
        $this->yandexDisk->setAppName($this->parser->getName());
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
