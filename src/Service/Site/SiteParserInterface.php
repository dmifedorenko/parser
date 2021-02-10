<?php


namespace App\Service\Site;


use Symfony\Component\Console\Output\OutputInterface;

interface SiteParserInterface
{
    public function parse(OutputInterface $output): void;
}