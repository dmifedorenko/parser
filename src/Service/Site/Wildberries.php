<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class Wildberries extends SiteParser
{
    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $file = __DIR__ . '/../../../goods_source';
        $items = unserialize(file_get_contents($file));

        $c = 0;
        $ret = [];
        foreach ($items as $gid => $source) {
            try {
                $source = trim($source, '"');
                $this->parser->getUrl($source);
            } catch (\Throwable $e) {
                echo $e->getMessage() . PHP_EOL;
                $ret[$gid] = [];
                continue;
            }

            $images = [];
            foreach ($this->parser->cssArray('.swiper-wrapper source') as $image) {
                $images[] = $image['@']['srcset'];
            }

            $ret[$gid] = $images;

            ++$c;
            if ($c && $c % 5 == 0) {
                $this->writeln('Done ' . $c . '/' . count($items));
            }

//            if ($c > 10) {
//                break;
//            }
        }
        echo PHP_EOL . PHP_EOL;

        file_put_contents($file . '.done', serialize($ret));
    }
}
