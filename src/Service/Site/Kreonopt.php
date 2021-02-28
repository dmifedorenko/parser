<?php

namespace App\Service\Site;

use App\Service\Parser\Parser;
use App\Service\YandexDisk;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Kreonopt extends SiteParser
{
    private HttpClientInterface $httpClient;

    private const COOKIES = 'language=ru-ru; currency=RUB; default=4b08cb3ad0fc27ad144b3216f591e8e9; PHPSESSID=2f2e7d8a498c1426eb4fc441b92959a2';
    private const AUTH = [
        'email' => 'veleri1029@gmail.com',
        'password' => 'lerafe100sp',
    ];
    private string $collectionUrl;

    public function __construct(Parser $parser, YandexDisk $yandexDisk, HttpClientInterface $httpClient)
    {
        parent::__construct($parser, $yandexDisk);
        $this->httpClient = $httpClient;
    }

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $this->auth();

        $sections = $this->getSections();
        $this->writeln('Sections - ' . count($sections));

        foreach ($sections as $link => $sectionName) {
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
        $this->httpClient->request('POST', $this->parser->rootUrl . '/store/login', ['body' => self::AUTH, 'headers' => [
            'cookie: ' . self::COOKIES,
        ]]);

        $this->parser->addHeader('cookie: ' . self::COOKIES);
        $this->getUrl('/store/');

        assert($this->css('#top-links a')[5]['_'] ?? null === 'Выход');
    }

    private function processPage(string $pageUrl): void
    {
        $this->getUrl($pageUrl);

        $goods = $this->css('.product-layout .caption a');

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
            $price = (float)str_replace(',', '.', $tds[3]['_']);
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
}
