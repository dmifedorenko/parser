<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class Bigholiday extends SiteParser
{
    public const STOCK_OK = 'На складе';
    public const STOCK_NOT = 'Заканчивается';

    private array $sections = [
        '/upakovka/',
        '/naturalnye-materialy-dlya-dekora-i-floristiki/',
        '/morskie-suveniry/',
        '/predmety-dlya-interera-i-dekora/',
        '/svadebnaya-produkciya/',
        '/svechi-i-podsvechniki/',
        '/dekorativnye-ukrasheniya/',
        '/iskusstvennye-cvety/',
        '/novyj-god/',
    ];

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $goods = [];

        $this->parser->uniqArts = true;
        $this->parser->lowPriceLimit = 2;

        $sections = $this->getSections();

        $this->writeln('Sections - ' . count($sections));

        foreach ($sections as $section) {
            $this->getParser()->getUrl($section);

            $pages = [$section];
            foreach ($this->css('.pagination a') as $page) {
                $pages[] = $page['@']['href'];
            }

            foreach (array_unique($pages) as $page) {
                $this->getParser()->getUrl($page);

                foreach ($this->css('#content #res-products .product .product-about') as $index => $good) {
                    $goodNodes = $this->parser->getLastNodeList()[$index];

                    $stock = $this->parser->textFromCss('.oct-cat-stock', $goodNodes);
                    if ($stock == self::STOCK_OK) {
                        $goods[] = $this->parser->css('a', true, $goodNodes)[0]['@']['href'];
                    } elseif ($stock != self::STOCK_NOT) {
                        throw new \InvalidArgumentException();
                    }
                }
            }
        }

        $goods = array_unique($goods);
        //shuffle($goods);
        $this->parseGoods($goods);
    }

    private function getSections(): array
    {
        $sections = [];
        foreach ($this->sections as $item) {
//            if (count($this->sections) >= 5) {
//                break;
//            }

            $this->parser->getUrl($item);

            $links = $this->parser->css('.box-content .active a', true);

            foreach ($links as $link) {
                if (empty($link['@']['href'])) {
                    continue;
                }
                $href = $link['@']['href'];
                if ($this->getParser()->rootUrl . $item != $href) {
                    $sections[] = $href;
                }
            }
        }

        return $sections;
    }

    private function parseGoods(array $goods): void
    {
        $this->output->writeln('Total goods count - ' . count($goods));

        foreach ($goods as $c => $goodUrl) {
            if ($c && $c % 50 == 0) {
                $this->writeln('Done ' . $c . '/' . count($goods));
            }

            try {
                $this->parser->getUrl($goodUrl);
            } catch (\Throwable $e) {
                $this->writeln("<error>{$e->getMessage()} - {$goodUrl}</error>");
                continue;
            }

            $images = [];
            foreach ($this->parser->css('.all-carousel a', true) as $item) {
                $images[] = $item['@']['href'];
            }

            try {
                $collectionName = $this->parser->css('ul.breadcrumb li span', true)[1]['_'];
            } catch (\Throwable $e) {
                echo $goodUrl . PHP_EOL;
                echo $e;
                continue;
            }

            $count = $this->parser->css('input#stock_quantity')['@']['value'] ?? 0;
            if (!$count) {
                $this->writeln("Skipt count zero - {$goodUrl}");
                continue;
            }

            $minimumval = $this->parser->css('input#minimumval')['@']['value'];

            if ($minimumval != 1) {
                //$this->writeln("Skipt minimum {$minimumval} - {$goodUrl}");
                continue;
            }

            $price = (float)str_replace(' ', '', $this->parser->textFromCss('.price .price-new'));

            $this->parser->putRowDetails(
                $collectionName,
                $this->parser->textFromCss('.description span[itemprop="model"]'),
                $this->parser->textFromCss('h1'),
                $this->parser->textFromCss('div#tab-description'),
                $price,
                '',
                '-@' . $count,
                $goodUrl,
                0,
                $images
            );

//            if ($c >= 199) {
//                break;
//            }
        }

        $this->output->writeln('');
    }
}
