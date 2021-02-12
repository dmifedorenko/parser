<?php
namespace App\Service;

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

    private string $defHeaders = 'accept: application/json, text/plain, */*
        accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,ca;q=0.6,ny;q=0.5,hu;q=0.4,es;q=0.3
        user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36';

    public string $lastPage = '';
    private array $headers = [];
    public int $index = -1;
    public int $cacheTTLDays = 5;

    private string $outPutFile;

    public string $rootUrl = '';
    private string $name;

    private CssSelectorConverter $converter;
    private OutputInterface $output;

    public function __construct(string $name)
    {
        $this->name = $name;
        $outName = $this->name . '_out.csv';
        $this->outPutFile = Kernel::get()->getProjectDir() . '/' . Kernel::get()->getContainer()->getParameter('parser')['output'] . '/' . $outName;

        $this->fHandle = fopen($this->outPutFile, 'wb');
        fputcsv($this->fHandle, $this->getHeaders());

        foreach (explode(PHP_EOL, $this->defHeaders) as $header) {
            $this->addHeader(trim($header));
        }

        $this->converter = new CssSelectorConverter();
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    private function getHeaders(): array
    {
        $images = [];
        for ($i = 1; $i <= 30; $i++) {
            if ($i == 1) {
                $images[] = 'Картинка';
            } else {
                $images[] = 'Картинка ' . $i;
            }
        }

        return explode(',', 'Коллекция,Артикул,Название,Подробнее,Цена,РРЦ,Размеры,Источник товара,Категория,' . join(',', $images));
    }

    public function addHeader(string $header): void
    {
        $this->headers[] = trim($header);
    }

    // Коллекция,Артикул,Название,Подробнее,Цена,РРЦ,Размеры,Источник товара,Категория
    public function putRow(array $data): void
    {
        fputcsv($this->fHandle, $data);
    }

    public function putRowDetails(
        string $collection,
        string $art,
        string $name,
        string $description,
        string $price,
        string $rrc,
        string $sizes,
        string $source,
        string $category,
        array $images = []
    ) {
        foreach ($images as &$image) {
            if ($this->rootUrl && $image[0] == '/') {
                $image = $this->rootUrl . $image;
            }
        }

        $this->putRow(array_merge(array_slice(func_get_args(), 0, -1), $images));
    }

    private function buildContext(string $method, array $content): ?array
    {
        $context = null;
        if ($this->headers) {
            $rawContent = $content['rawContent'] ?? null;
            unset($content['rawContent']);

            $context = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ),
                'http' => array(
                    'method' => $method,
                    'header' => join("\r\n", $this->headers),
                    'content' => $rawContent ? $rawContent : ($content ? http_build_query($content) : null),
                )
            );
        }

        return $context;
    }

    public function getUrl(string $url, string $method = 'GET', array $content = []): string
    {
        $url = trim($url);

        if ($this->rootUrl && stripos($url, $this->rootUrl) !== 0) {
            $url = $this->rootUrl . $url;
        }

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
            } catch (\ErrorException) {
                file_put_contents($file, '404', LOCK_EX);
                throw new InvalidArgumentException('404 - ' . $url);
            }
        }

        $all = file_get_contents($file);
        if ($all == '404') {
            throw new InvalidArgumentException('404 - ' . $url . PHP_EOL . $file);
        }

        $this->lastPage = $all;

        return $all;
    }

    public function setLastPage(string $content): void
    {
        $this->lastPage = $content;
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
    }

    public function check(): void
    {
        $fh = fopen($this->outPutFile, 'r');
        $c = 0;
        while ($data = fgetcsv($fh)) {
          $c++;
          if ($c > 50) {
              break;
          }

          Kernel::p0($data);
        }

        fclose($fh);
    }

    private function getDom(): DOMDocument
    {
        libxml_use_internal_errors(true);
        $dom = new DomDocument;
        $dom->loadHTML($this->lastPage);

        return $dom;
    }

    /**
     * @param string $xpath
     * @return DOMNodeList|false
     */
    public function getXpath(string $xpath)
    {
        static $cache = [];
        $key = crc32($this->lastPage);
        if (empty($cache[$key])) {
            $cache[$key] = new DOMXPath($this->getDom());
        }

        $domXPath = $cache[$key];
        return $domXPath->query($xpath);
    }

    /**
     * @return array|string
     */
    public function css(string $css, bool $allwaysArray = false)
    {
        return $this->getArrayFromXpath($this->converter->toXPath($css), $allwaysArray);
    }

    public function textFromCss(string $css): string
    {
        return $this->getTextFromXpath($this->converter->toXPath($css));
    }

    public function getBitrixGoodData(): array
    {
        $data = $this->css('.ns-bitrix.c-catalog-element')['@']['data-data'];
        try {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Kernel::p0('Json', $data);
            throw $e;
        }

        return $data;
    }

    /**
     * @return array|string
     */
    public function getArrayFromXpath(string $xpath, bool $allwaysArray = false)
    {
        $nodes = $this->getXpath($xpath);
        $ret = [];
        foreach ($nodes as $node) {
            $this->xmlToArrayItem = $node;
            $ret[] = $this->xml_to_array();
        }

        if (!$allwaysArray && count($ret) == 1) {
            return is_string($ret[0]) ? trim($ret[0]) : $ret[0];
        }

        return $ret;
    }

    public function getTextFromXpath(string $xpath): string
    {
        $nodes = $this->getXpath($xpath);
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

            $text[] = join(PHP_EOL, $items);
        }

        return trim(join(PHP_EOL, $text));
    }

    private $xmlToArrayItem;

    public function xml_to_array()
    {
        $result = array();

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
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $this->xmlToArrayItem = $child;
                    $data = $this->xml_to_array();
                    if ($data) {
                        $result[$child->nodeName] = $data;
                    } elseif (trim($child->nodeValue)) {
                        $result['_'] = trim($child->nodeValue);
                    }
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $this->xmlToArrayItem = $child;
                    $result[$child->nodeName][] = $this->xml_to_array();
                }
            }
        }

        return $result;
    }
}
