<?php

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class Almaz extends SiteParser
{
    private string $options = '/index.php?route=product/category&path=65
        /index.php?route=product/category&path=72
        /index.php?route=product/category&path=93
        /index.php?route=product/category&path=66
        /index.php?route=product/category&path=68';

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $arts = [];

        $sections = $this->getSections();

        $this->output->writeln('Sections - ' . count($sections));

        foreach ($sections as $section) {
            $this->getParser()->getUrl($section . '&limit=1200');

            $items = $this->getParser()->css('div.product-layout h4 a');

            $title = $this->getParser()->css('title');

            $this->output->writeln($title);

            foreach ($items as $item) {
                try {
                    $link = $item['@']['href'];
                    $art = $item['span']['_'];

                    $this->getParser()->getUrl($link);

                    $count = 0;
                    foreach ($this->getParser()->css('.product_informationss ul.list-unstyled li') as $li) {
                        if ($li['div'][0]['span']['_'] === 'Доступно:') {
                            $count = $li['div'][1]['span']['_'];
                        }
                    }

                    $price = $this->getParser()->css('.update_price');

                    $images = [];
                    foreach ($this->getParser()->css('#owl-images a') as $image) {
                        $images[] = $image['@']['href'];
                    }

                    if (!$images) {
                        foreach ($this->getParser()->css('.main_img_box a', true) as $image) {
                            $images[] = $image['@']['href'];
                        }
                    }

                    if (isset($arts[$art])) {
                        continue;
                    }
                    $arts[$art] = 1;

                    $this->output->write('.');

                    $this->getParser()->putRowDetails(
                        $title,
                        $art,
                        $title . ' ' . $art,
                        $this->getParser()->textFromCss('#tab-description'),
                        (float)$price['_'],
                        '',
                        '-@' . $count,
                        $link,
                        107171,
                        $images
                    );
                } catch (\Throwable $e) {
                    dd($section, $link, $e, $item);
                }
            }
            $this->output->writeln('');
        }
    }

    private function getSections(): array
    {
        $sections = [];
        foreach (explode(PHP_EOL, $this->options) as $item) {
            $this->getParser()->getUrl($item);

            $links = $this->getParser()->css('#content .col-sm-3 a', true);

            foreach ($links as $link) {
                $sections[] = $link['@']['href'];
            }
        }

        return $sections;
    }
}
