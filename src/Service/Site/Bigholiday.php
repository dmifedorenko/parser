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
    ];

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $goods = [];

        $this->parser->uniqArts = true;
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

                    $name = $this->parser->textFromCss('.name', $goodNodes);
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
        $this->parseGoods($goods);
    }

    private function getSections(): array
    {
        $sections = [];
        foreach ($this->sections as $item) {
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

        foreach ($goods as $c => $good) {
            if ($c && $c / 100 == 0) {
                $this->writeln($c);
            }
            $this->parser->getUrl($good);
        }

        $this->output->writeln('');
    }
}
