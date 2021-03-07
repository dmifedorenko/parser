<?php

namespace App\Service\Site;

use App\Service\Parser\Parser;
use App\Service\YandexDisk;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Kreonopt extends SiteParser
{
    private HttpClientInterface $httpClient;

    private $COOKIES = 'language=ru-ru; currency=RUB; default=%s; PHPSESSID=%s';
    private $AUTH = [
        'email' => null,
        'password' => null,
    ];

    public function __construct(Parser $parser, YandexDisk $yandexDisk, HttpClientInterface $httpClient, array $settings)
    {
        parent::__construct($parser, $yandexDisk);
        $this->httpClient = $httpClient;

        $this->AUTH['email'] = $settings[0];
        $this->AUTH['password'] = $settings[1];

        $this->COOKIES = sprintf($this->COOKIES, $settings[2], $settings[3]);
    }

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $this->auth();

        $brands = $this->getGoodInBrands();
        $sections = $this->getSections();

        $this->writeln('Brands - ' . count($brands));
        $this->writeln('Sections - ' . count($sections));

        foreach (array_merge($sections, $brands) as $link => $sectionName) {
            try {
                $this->getUrl($link);
                $this->processPage($link);

                $pages = $this->css('.pagination a', true);
                foreach ($pages as $page) {
                    $this->processPage($page['@']['href']);
                }
            } catch (\Throwable $e) {
                dump($this->parser->getLocation());
                throw $e;
            }
        }
    }

    private function getSections(): array
    {
        $ret = [];
        foreach ($this->css('.megamenu a') as $link) {
            $href = $link['@']['href'];
            if (!preg_match('~(youtube\.com|instagram\.com|whatsapp\.com)~u', $href) && !in_array($href, ['brands', '#'], true)) {
                $ret[$href] = $link['span'] ?? $link['_'];
            }
        }

        return $ret;
    }

    private function auth(): void
    {
        $this->httpClient->request('POST', $this->parser->rootUrl . '/store/login', ['body' => $this->AUTH, 'headers' => [
            'cookie: ' . $this->COOKIES,
        ]]);

        $this->parser->addHeader('cookie: ' . $this->COOKIES);
        $this->getUrl('/store/');

        assert($this->css('#top-links a')[5]['_'] ?? null === 'Выход');
    }

    private function processPage(string $pageUrl): void
    {
        $this->getUrl($pageUrl);

        $goods = $this->css('.product-layout .caption a', true);

        $path = $this->css('.breadcrumb a');
        $collectionName = $path[1]['_'];

        $this->writeln($collectionName . '/' . ($path[2]['_'] ?? '-'));

        foreach ($goods as $good) {
            $this->processGood($collectionName, $good['@']['href']);
            $this->write('.');
        }
        $this->writeln();
    }

    private function processGood(string $collectionName, string $goodUrl): void
    {
        $this->getUrl($goodUrl);

        $tds = $this->css('.table.div_pc td');
        if ($tds[2] === '50-150 тыс. руб.') {
            $price = $tds[3]['_'];
        }

        $title = $this->parser->textFromCss('h1');

        $artData = $this->css('.list-unstyled.mm-top');
        $artData = is_string($artData['li']) ? $artData['li'] : $artData['li'][1];
        if (stripos($artData, 'Артикул ') === false) {
            dump($artData);
            throw new \LogicException('Cant find articul');
        }
        $art = trim(str_replace('Артикул ', '', $artData));

        $images = [];
        $slides = $this->css('.thumbnails.slider-for img', true);
        foreach ($slides as $slide) {
            $images[] = $slide['@']['src'];
        }

        static $doneArts = [];
        if (array_key_exists($art, $doneArts)) {
            $this->write('-');

            return;
        }
        $doneArts[$art] = 1;

        $this->parser->putRowDetails(
            $collectionName,
            $art,
            $title . ' ' . $art,
            $this->parser->textFromCss('#tab-description'),
            $price,
            '',
            '-',
            $goodUrl,
            4685,
            $images
        );
    }

    private function getGoodInBrands(): array
    {
        $this->parser->getUrl('/store/brands');

        $brands = [];
        foreach ($this->css('.manufacturer-grid a') as $link) {
            $brands[$link['@']['href']] = $this->css('h1');
        }

        return $brands;
    }
}
