<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class China extends SiteParser
{
    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $items = [];
        $dir = __DIR__ . '/../../..';
        if (($handle = fopen(realpath($dir . '/china_shit.csv'), 'rb')) !== false) {
            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                [$gid, $source] = $data;

                $items[$gid]['source'] = $source;
                $items[$gid]['gid'] = $gid;
            }
            fclose($handle);
        }
        shuffle($items);

        foreach ($items as $gid => &$item) {
            $source = $item['source'];

            try {
                $this->parser->getUrl($source);
                $hostInfo = parse_url($source);
                $host = $hostInfo['scheme'] . '://' . $hostInfo['host'];

                $images = $this->parser->css('.swiper-wrapper img', true);
                foreach ($images as $image) {
                    $item['images'][] = $host . $image['@']['src'];
                }

                file_put_contents($dir . '/china_shit.php', '<?php return ' . var_export(array_filter($items, function ($item) {
                    return $item['images'] ?? null;
                }), true) . ';');
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), '404 ') !== false) {
                    continue;
                }
                dd($item, $e->getMessage(), $e);
            }
            dump($this->parser->css('h1')['_'] ?? '', $source);
        }

        dd($items);

        return;

        $file = realpath(__DIR__ . '/../../../');
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
