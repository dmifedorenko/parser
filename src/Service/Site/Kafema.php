<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class Kafema extends SiteParser
{
    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        foreach ($this->getCollections() as $collection) {
            $urls = $this->getGoodsUrls($collection);

            $cname = $collection['name'];
            $output->writeln($cname);

            foreach ($urls as $url) {
                try {
                    $this->parser->getUrl($url);
                } catch (\Throwable $e) {
                    $this->output->writeln('<error>' . $url . ' - ' . $e->getMessage() . '</error>');
                    continue;
                }

                foreach ($this->getGoodDetails($url, in_array($collection['name'], ['Свежеобжаренный кофе Kafema', 'Чай Teejur'], true), $cname) as $good) {
                    $this->parser->putRowDetails(
                        $good['collection'],
                        $good['articul'],
                        $good['name'],
                        $good['description'],
                        $good['price'],
                        '',
                        $good['size'],
                        $good['source'],
                        0,
                        $good['images']
                    );
                }

                echo '.';
            }
            $output->writeln('');
        }
    }

    public function getGoodsUrls(array $collection): array
    {
        $this->parser->getUrl($collection['href'] . '?view=list');

        $ret = [];
        foreach ($this->parser->css('.catalog-section-item-wrapper .catalog-section-item-name-wrapper') as $item) {
            $ret[] = $item['@']['href'];
        }

        return $ret;
    }

    private function getImages(string $css): array
    {
        $images = [];
        foreach ($this->parser->css($css) as $item) {
            try {
                if (empty($item['@']['href']) && empty($item['href'])) {
                    continue;
                }

                $src = $item['@']['href'] ?? $item['href'];
                if (stripos($src, '/upload/') === 0) {
                    $src = $this->parser->rootUrl . $src;
                } else {
                    [, $data] = explode(';', $src);
                    [, $data] = explode(',', $data);
                    $content = base64_decode($data);
                    $src = $content; //$this->yandexDisk->upload(md5($src), $content);
                }
                $images[] = $src;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        return $images;
    }

    public function getGoodDetails(string $url, bool $withSizes, string $cname): array
    {
        $this->parser->getUrl($url);

        $data = $this->parser->getBitrixGoodData();

        $ret = [];

        $type = array_slice($this->parser->css('.breadcrumb-wrapper a'), -1)[0]['span']['_'];

        $emptyOffer = false;
        try {
            if (empty($data['offers'])) {
                $emptyOffer = true;
                $data['offers'][] = $data;
            }

            if (!$data['offers']) {
                dump('No offers', $data);
            }

            foreach ($data['offers'] as $offer) {
                if ($offer['available']) {
                    $images = $this->getImages('.catalog-element-gallery[data-offer="' . $offer['id'] . '"] .catalog-element-gallery-pictures a');
                    if (!$images && $emptyOffer) {
                        $images = $this->getImages('.catalog-element-gallery[data-offer="false"] .catalog-element-gallery-pictures a');
                    }

                    $name = $type . '. ' . $this->parser->textFromCss('h1');

                    if ($withSizes) {
                        try {
                            if (empty($offer['sort']) && count($data['offers']) == 1) {
                                $oSize = '-';
                            } else {
                                $oSize = $offer['sort']['P_WEIGHT'] ?? $offer['sort']['P_COLOR'];
                            }
                        } catch (\Throwable $e) {
                            dump('No size ' . $url, $e->getMessage(), $data);
                        }
                        $name .= '. ' . $oSize . 'г';
                        $size = 'для кофемашины,для гейзерной кофеварки,для турки,для фильтра,для френч-пресса,для чашки,для эспрессо';
                    } else {
                        $size = '-';
                    }

                    $ret[] = [
                        'collection' => $withSizes ? $cname . '. ' . $oSize . 'г' : $cname,
                        'articul' => $offer['id'],
                        'name' => $name,
                        'size' => $size,
                        'price' => $offer['prices'][0]['base']['value'],
                        'description' => $this->parser->textFromCss('.catalog-element-description'),
                        'source' => $this->parser->rootUrl . $url,
                        'images' => $images,
                    ];

                    if (!$images) {
                        dump('No images', $url, $images, $offer);
                    }
                }
            }
        } catch (\Throwable $e) {
            dump($url, $e->getMessage(), $data);
        }

        //dd($url, $ret, $data);
        return $ret;
    }

    public function getCollections(): array
    {
        $this->parser->getUrl('/catalog/');

        $ret = [];
        $items = $this->parser->css('.catalog-section-list-item');
        foreach ($items as $item) {
            $ret[] = [
                'name' => trim($item['div']['div']['div']['a']['span'][0]['_']),
                'href' => $item['div']['div']['a']['@']['href'],
            ];
        }

        return $ret;
    }
}
