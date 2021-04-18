<?php

namespace App\Service\Parser;

use App\Kernel;
use DOMDocument;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;

class Parser
{
    /**
     * @var false|resource
     */
    private $fHandle;

    public string $lastPage = '';

    private array $headers = [];

    private string $outPutFile;

    public string $rootUrl = '';

    private string $name;

    public int $cacheTTLDays = 5;

    public bool $uniqArts = false;

    public int $lowPriceLimit = 30;

    private string $location;
    private array $stat = ['rows' => 0, 'collections' => []];
    private DOMNodeList $lastNodes;

    private CssSelectorConverter $converter;
    private OutputInterface $output;

    public function __construct(array $headers)
    {
        $this->converter = new CssSelectorConverter();

        foreach ($headers as $header) {
            $this->addHeader(trim($header));
        }
    }

    public function init(string $siteName): void
    {
        $this->name = $siteName;
        $outName = $this->name . '_out.csv';
        $this->outPutFile = Kernel::get()->getProjectDir() . '/' . Kernel::get()->getContainer()->getParameter('parser')['output'] . '/' . $outName;

        $this->fHandle = fopen($this->outPutFile, 'wb');
        fputcsv($this->fHandle, $this->getHeaders());
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    private function getHeaders(): array
    {
        $images = [];
        for ($i = 1; $i <= 30; ++$i) {
            if ($i == 1) {
                $images[] = 'Картинка';
            } else {
                $images[] = 'Картинка ' . $i;
            }
        }

        return explode(',', 'Коллекция,Артикул,Название,Подробнее,Цена,РРЦ,Размеры,Источник товара,Категория,' . implode(',', $images));
    }

    public function addHeader(string $header): void
    {
        $this->headers[] = trim($header);
    }

    // Коллекция,Артикул,Название,Подробнее,Цена,РРЦ,Размеры,Источник товара,Категория
    private function putRow(array $data): void
    {
        ++$this->stat['rows'];
        fputcsv($this->fHandle, $data);
    }

    public function putRowDetails(
        string $collection,
        string|int $art,
        string $name,
        string $description,
        string|float $price,
        string $rrc,
        string $sizes,
        string $source,
        int $category,
        array $images = []
    ): void {
        if (is_string($price)) {
            $price = str_replace([',', ' '], ['.', ''], $price);
            $price = (float)$price;
        }

        $this->stat['collections'][$collection] = 1;

        foreach ($images as &$image) {
            if ($this->rootUrl && $image[0] == '/') {
                $image = $this->rootUrl . $image;
            }
        }
        $images = array_unique($images);

        if (!$category) {
            $category = '';
        }

        if ($price < $this->lowPriceLimit) {
            $this->output->writeln("<error>Low price - {$art}/{$price}</error>");
        }

        static $doneArts = [];
        if (array_key_exists($art, $doneArts)) {
            $this->output->writeln('Skip non uniq art -' . $art);

            return;
        }
        $doneArts[$art] = 1;

        $this->putRow(array_merge([
            $collection,
            $art,
            $name,
            $description,
            $price,
            $rrc,
            $sizes,
            $source,
            $category,
        ], $images));
    }

    private function buildContext(string $method, array $content): ?array
    {
        $context = null;
        if ($this->headers) {
            $rawContent = $content['rawContent'] ?? null;
            unset($content['rawContent']);

            $context = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $this->headers),
                    'content' => $rawContent ?: ($content ? http_build_query($content) : null),
                ],
            ];
        }

        return $context;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getUrl(string $url, string $method = 'GET', array $content = []): string
    {
        $url = trim($url);

        if ($this->rootUrl && stripos($url, $this->rootUrl) !== 0) {
            $url = $this->rootUrl . $url;
        }
        $this->location = $url;

        $context = $this->buildContext($method, $content);

        $id = md5($url);
        if ($context) {
            $id .= '_' . md5(serialize($context));
        }

        $tmpDir = Kernel::get()->getContainer()->getParameter('parser')['tmp'];
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir);
        }
        $file = $tmpDir . '/' . $this->name . '_' . $id;

        if (!file_exists($file) || ($this->cacheTTLDays && (time() - filemtime($file)) / 60 / 60 / 24 > $this->cacheTTLDays)) {
            try {
                $this->output->writeln('Downloading ' . $url);
                file_put_contents($file, file_get_contents($url, false, $context ? stream_context_create($context) : null), LOCK_EX);
            } catch (\ErrorException $e) {
                throw new InvalidArgumentException('404 - ' . $url, 0, $e);
            }
        }

        $this->lastPage = file_get_contents($file);

        return $this->lastPage;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function done(): void
    {
        if ($this->fHandle) {
            fclose($this->fHandle);
        }

        $this->output->writeln('');
        $this->output->writeln('<comment>Collections - ' . count($this->stat['collections']) . '</comment>');
        $this->output->writeln('<comment>Rows - ' . $this->stat['rows'] . '</comment>');
        $this->output->writeln('<comment>' . dirname($this->outPutFile) . '</comment>');
        $this->output->writeln('');
    }

    private function getDom(): DOMDocument
    {
        libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->loadHTML($this->lastPage);

        return $dom;
    }

    public function getLastNodeList(): DOMNodeList
    {
        return $this->lastNodes;
    }

    public function getXpath(string $xpath, \DOMElement $element = null): DOMNodeList|bool
    {
        static $cache = [];
        $key = crc32($this->lastPage);
        if (empty($cache[$key])) {
            $cache[$key] = new DOMXPath($this->getDom());
        }

        $domXPath = $cache[$key];

        if ($element) {
            $xpath = $element->getNodePath() . '/' . $xpath;
        }

        $nodes = $domXPath->query($xpath);
        if (!$element) {
            $this->lastNodes = $nodes;
        }

        return $nodes;
    }

    public function css(string $css, bool $allwaysArray = false, \DOMElement $root = null): array|string
    {
        $nodes = $this->getXpath($this->converter->toXPath($css), $root);

        return $this->getArrayFromXpath($nodes, $allwaysArray);
    }

    public function textFromCss(string $css, \DOMElement $root = null): string
    {
        $nodes = $this->getXpath($this->converter->toXPath($css), $root);

        return $this->getTextFromXpath($nodes);
    }

    public function getBitrixGoodData(): array
    {
        $data = $this->css('.ns-bitrix.c-catalog-element')['@']['data-data'];
        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            dump('Json', $data);
            throw $e;
        }

        return $data;
    }

    public function getArrayFromXpath(DOMNodeList $nodes, bool $allwaysArray = false): array|string
    {
        $ret = [];
        foreach ($nodes as $node) {
            $this->xmlToArrayItem = $node;
            $ret[] = $this->xmlToArray();
        }

        if (!$allwaysArray && count($ret) == 1) {
            return is_string($ret[0]) ? trim($ret[0]) : $ret[0];
        }

        return $ret;
    }

    public function getTextFromXpath(DOMNodeList $nodes): string
    {
        $text = [];
        foreach ($nodes as $node) {
            /**
             * @var \DOMElement $node
             */
            $t = $node->ownerDocument->saveHTML($node);
            $t = preg_replace('~<br[^>]*>~i', PHP_EOL, $t);

            $items = explode(PHP_EOL, strip_tags($t));
            $items = array_map('trim', $items);
            //$items = array_filter($items);

            $text[] = implode(PHP_EOL, $items);
        }

        return trim(implode(PHP_EOL, $text));
    }

    private $xmlToArrayItem;

    public function xmlToArray(): array|string
    {
        $result = [];

        if ($this->xmlToArrayItem->hasAttributes()) {
            $attrs = $this->xmlToArrayItem->attributes;
            foreach ($attrs as $attr) {
                $result['@'][$attr->name] = $attr->value;
            }
        }

        if ($this->xmlToArrayItem->hasChildNodes()) {
            $children = $this->xmlToArrayItem->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_'] = $child->nodeValue;

                    return count($result) == 1
                        ? $result['_']
                        : $result;
                }
            }
            $groups = [];
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $this->xmlToArrayItem = $child;
                    $data = $this->xmlToArray();
                    if ($data) {
                        $result[$child->nodeName] = $data;
                    } elseif (trim($child->nodeValue)) {
                        $result['_'] = trim($child->nodeValue);
                    }
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                        $groups[$child->nodeName] = 1;
                    }
                    $this->xmlToArrayItem = $child;
                    $result[$child->nodeName][] = $this->xmlToArray();
                }
            }
        }

        return $result;
    }
}
