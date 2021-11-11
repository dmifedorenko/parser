<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class ImagesBySource extends SiteParser
{
    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $file = realpath(__DIR__ . '/../../../../dev.100sp/goods_source');
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
            foreach (json_decode($this->parser->css('product-slider')['@'][':items'], true, JSON_THROW_ON_ERROR) as $image) {
                $images[] = $image['src'];
            }

            if ($images) {
                $ret[$gid] = $images;
            }

            ++$c;
            if ($c && $c % 5 == 0) {
                $this->writeln('Done ' . $c . '/' . count($items));
            }

//            if ($c > 10) {
//                break;
//            }

            file_put_contents($file . '.done', serialize($ret));
        }
        echo PHP_EOL . PHP_EOL;

        dd($ret);
    }
}
