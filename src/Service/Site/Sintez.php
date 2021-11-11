<?php

declare(strict_types=1);

namespace App\Service\Site;

use Symfony\Component\Console\Output\OutputInterface;

class Sintez extends SiteParser
{
    private string $onlyCategories = 'https://sintezf.com/shop/catalog/miski-tarelki-tazy/152
        https://sintezf.com/shop/catalog/konditerskiy-inventar/4
        https://sintezf.com/shop/catalog/kastryuli-kotly/116
        https://sintezf.com/shop/catalog/bokaly-dlya-kofe/515
        https://sintezf.com/shop/catalog/farfor-black-star-p-l/778
        https://sintezf.com/shop/catalog/farfor-taiga/579
        https://sintezf.com/shop/catalog/farfor-rak-oae/566
        https://sintezf.com/shop/catalog/farfor-lubiana-polsha/242
        https://sintezf.com/shop/catalog/kamennaya-keramika-stockholm/580
        https://sintezf.com/shop/catalog/uzbekskaya-keramika/791
        https://sintezf.com/shop/catalog/farfor-premium-knr-belyy-ottenok/586
        https://sintezf.com/shop/catalog/farfor-texture-p-l/772
        https://sintezf.com/shop/catalog/borisovskaya-keramika/793
        https://sintezf.com/shop/catalog/posuda-iz-stekla/191
        https://sintezf.com/shop/catalog/posuda-dlya-detskih-sadov/573
        https://sintezf.com/shop/catalog/akva/797
        https://sintezf.com/shop/catalog/farfor-cvetnoy-organica-p-l/246
        https://sintezf.com/shop/catalog/farfor-cvetnoy-fusion-p-l/771
        https://sintezf.com/shop/catalog/farfor-premium-knr-molochnyy-ottenok/587
        https://sintezf.com/shop/catalog/farfor-i-fayans-ekonom/245
        https://sintezf.com/shop/catalog/steklokeramika/226
        https://sintezf.com/shop/catalog/astra/271
        https://sintezf.com/shop/catalog/stolovye-pribory-otdelnymi-predmetami/726
        https://sintezf.com/shop/catalog/tango/782
        https://sintezf.com/shop/catalog/tokio/795
        https://sintezf.com/shop/catalog/asha-novinka/79
        https://sintezf.com/shop/catalog/boston/395
        https://sintezf.com/shop/catalog/marselles/147
        https://sintezf.com/shop/catalog/mondial/157
        https://sintezf.com/shop/catalog/m-3/144
        https://sintezf.com/shop/catalog/prima/195
        https://sintezf.com/shop/catalog/sonet/223
        https://sintezf.com/shop/catalog/signum/214
        https://sintezf.com/shop/catalog/antoshka-detskaya/76
        https://sintezf.com/shop/catalog/bali/83
        https://sintezf.com/shop/catalog/davinchi/385
        https://sintezf.com/shop/catalog/oslo/176
        https://sintezf.com/shop/catalog/nozhi-arcos/164
        https://sintezf.com/shop/catalog/nozhi-isel/166
        https://sintezf.com/shop/catalog/nozhi-luxstahl/167
        https://sintezf.com/shop/catalog/nozhi-borner/165
        https://sintezf.com/shop/catalog/nozhi-victorinox/168
        https://sintezf.com/shop/catalog/posuda-antiprigarnaya/34
        https://sintezf.com/shop/catalog/kazany/768
        https://sintezf.com/shop/catalog/skovorody-dlya-podachi/770
        https://sintezf.com/shop/catalog/skovorody-zharovni/384
        https://sintezf.com/shop/catalog/skovorody-gril-pressy/769
        https://sintezf.com/shop/catalog/marshmellou/794
        https://sintezf.com/shop/catalog/siropy-don-dolche/47
        https://sintezf.com/shop/catalog/siropy-monin/48
        https://sintezf.com/shop/catalog/siropy-monin-keddi/49
        https://sintezf.com/shop/catalog/smesi-dlya-morozhenogo-i-kokteyley/700
        https://sintezf.com/shop/catalog/toppingi-i-napitki/57
        https://sintezf.com/shop/catalog/bonna/796
        https://sintezf.com/shop/catalog/banki-dlya-sypuchih-produktov/275
        https://sintezf.com/shop/catalog/banka-emkost-s-kranom-podstavki/519
        https://sintezf.com/shop/catalog/banki-dlya-kokteyley/555
        https://sintezf.com/shop/catalog/banki-dlya-podachi/528
        https://sintezf.com/shop/catalog/kremanki/525
        https://sintezf.com/shop/catalog/grafiny-shtofy/522
        https://sintezf.com/shop/catalog/butylki/277
        https://sintezf.com/shop/catalog/kruzhki-dlya-piva/128
        https://sintezf.com/shop/catalog/bokaly-dlya-piva/527
        https://sintezf.com/shop/catalog/kruzhki/523
        https://sintezf.com/shop/catalog/kuvshiny/132
        https://sintezf.com/shop/catalog/stakany-haybol-vysokie/518
        https://sintezf.com/shop/catalog/nabory-dlya-speciy-melnicy-dlya-perca-soli-salfetnicy/161
        https://sintezf.com/shop/catalog/sousniki-kokotnicy-molochniki/402
        https://sintezf.com/shop/catalog/vedra-korzinki-podstavki-etazherki-dlya-podachi/398
        https://sintezf.com/shop/catalog/french-pressy-chayniki-zavarochnye/403
        https://sintezf.com/shop/catalog/posuda-dlya-keyteringa-i-furshetov/189
        https://sintezf.com/shop/catalog/konteynery-pakety-bumaga-dlya-podachi/399
        https://sintezf.com/shop/catalog/blyuda-dlya-podachi-slanec-baranchiki-kryshki-dlya-tarelok-tortovnic-hlebnic/131
        https://sintezf.com/shop/catalog/doski-razdelochnye-plastikovye-polipropilenovye/105
        https://sintezf.com/shop/catalog/doski-razdelochnye-dlya-podachi-derevyannye/106
        https://sintezf.com/shop/catalog/salfetki-skaterti-bumazhnye/42
        https://sintezf.com/shop/catalog/salfetki-skaterti-pvh/43
        https://sintezf.com/shop/catalog/fartuki-podtyazhki/60 
        https://sintezf.com/shop/catalog/kostyum-graciya-zhenskiy/814 
        https://sintezf.com/shop/catalog/kostyum-lyuks-zhenskiy/815 
        https://sintezf.com/shop/catalog/kostyum-nastenka-zhenskiy/816 
        https://sintezf.com/shop/catalog/kostyum-tehnolog-muzhskoy/818 
        https://sintezf.com/shop/catalog/kostyum-pekarya/819 
        https://sintezf.com/shop/catalog/perchatki/30 
        https://sintezf.com/shop/catalog/kitel-povarskoy-rikon-zhenskiy/821 
        https://sintezf.com/shop/catalog/kitel-povarskoy-rikon-muzhskoy/822 
        https://sintezf.com/shop/catalog/kurtka-lyuks/823 
        https://sintezf.com/shop/catalog/kurtka-lyuks-chernaya/834 
        https://sintezf.com/shop/catalog/kurtka-lyuks-layt-belaya-s-korotkim-rukavom/825 
        https://sintezf.com/shop/catalog/kurtka-lyuks-layt-chernaya/824 
        https://sintezf.com/shop/catalog/bandany-kolpaki-shapochki/10 
        https://sintezf.com/shop/catalog/kipyatilniki-i-vodonagrevateli/118 
        https://sintezf.com/shop/catalog/plastmassovaya-posuda/32 
        https://sintezf.com/shop/catalog/tara/53 
        https://sintezf.com/shop/catalog/termosy-termokonteynenry-termosumki/56 
        https://sintezf.com/shop/catalog/ocinkovannaya-hromirovannaya-posuda/29 
        https://sintezf.com/shop/catalog/armonia/837 
        https://sintezf.com/shop/catalog/infinity/846 
        https://sintezf.com/shop/catalog/cowry-yellow/843 
        https://sintezf.com/shop/catalog/elegance/838 
        https://sintezf.com/shop/catalog/kolezium/845 
        https://sintezf.com/shop/catalog/legna/839 
        https://sintezf.com/shop/catalog/supreme/840 
        https://sintezf.com/shop/catalog/tinta-spazio/844
        https://sintezf.com/shop/catalog/emalirovannaya-posuda/70 
        https://sintezf.com/shop/catalog/vintage/841 
        https://sintezf.com/shop/catalog/farfor-rak-oae/566 
        https://sintezf.com/shop/catalog/farfor-bonna-turciya/796 
        https://sintezf.com/shop/catalog/farfor-cvetnoy-knr/590 
        https://sintezf.com/shop/catalog/posuda-plastikovaya-melamin/190';

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $this->parser->lowPriceLimit = 2;

        $output->writeln('Make good links');
        $uniqGoods = array_keys($this->getUniqGoodsUrl());
        shuffle($uniqGoods);

        $this->writeln('<comment>Uniq goods - ' . count($uniqGoods) . '</comment>');

        $c = 0;
        foreach ($uniqGoods as $goodUrl) {
            ++$c;
            if ($c % 50 == 0) {
                $this->writeln('Done ' . $c . '/' . count($uniqGoods));
            }

            try {
                $this->parser->getUrl($goodUrl);

                $title = trim($this->parser->css('h1.panel-title')['_']);

                $breads = $this->parser->css('.breadcrumb li a');

                $items = $this->parser->css('.list-group-item .list-group-item-text');

                $skipGood = false;
                foreach ($items as $item) {
                    if (($item['strong'] ?? '') == 'Наличие:') {
                        if ($item['span'] == 'Под заказ') {
                            $skipGood = true;
                        }
                    }
                }

                if ($skipGood) {
                    $this->writeln('Skip ' . $goodUrl);
                    continue;
                }

                try {
                    $price = preg_replace('~\s+~u', '', trim($items[0]['span']['b'], '₽'));

                    if ($price == '1') {
                        dd($price, $items);
                    }
                } catch (\Throwable) {
                    dump('No price ' . $goodUrl);
                }
                unset($items[0]);

                if ($items[1]['strong'] != 'Код:') {
                    dd($title, $items[1]['strong']);
                }

                $art = $items[1]['span'];
                unset($items[1]);

                if ($goodUrl == '/shop/product/kastryulya-nerzhaveyushchaya-2-1l-so-steklyannoy-kryshkoy/6') {
                    continue;
                }

                //$description = $this->parser->css('li.list-group-item', true);
                $description = trim($this->parser->getTextFromXpath($this->parser->getXpath('(//li[@class="list-group-item"])[2]')));

                $images = [];
                foreach ($this->parser->css('.ubislider-inner img', true) as $item) {
                    $images[] = $this->parser->rootUrl . $item['@']['src'];
                }

                $this->parser->putRowDetails(
                    trim($breads[count($breads) - 2]['span']['_']),
                    $art,
                    $title,
                    $description,
                    $price,
                    '',
                    '@',
                    $this->parser->rootUrl . $goodUrl,
                    0,
                    $images
                );
            } catch (\Throwable $e) {
                dump($goodUrl, $e);
                continue;
            }
        }
    }

    private function getUniqGoodsUrl(): array
    {
        $this->parser->getUrl('/shop/catalog/katalog');

        $uniqGoods = [];
        foreach (array_unique(explode(PHP_EOL, $this->onlyCategories)) as $url) {
            $this->proccessPage($url, $uniqGoods);
        }

        return $uniqGoods;
    }

    private function proccessPage(string $url, array &$ret): void
    {
        $this->parser->getUrl($url);
        $title = trim(str_replace(PHP_EOL, ' ', $this->parser->css('title')));
        if (stripos($title, 'Каталог') === 0) {
            $this->writeln('<error>Skip catalog url - ' . trim($url) . '</error>');

            return;
        }

        if ($this->parser->css('select#availability')) {
            $this->parser->getUrl($url . '?availability=a_in_stock');
        }

        $categories = $this->parser->css('.category-cell a');

        foreach ($categories as $category) {
            $this->proccessPage($category['@']['href'], $ret);
        }

        foreach ($this->parser->css('#product-table tr.mosaic-view-mode h4 a', true) as $goodLink) {
            $ret[$goodLink['@']['href']] = 1;
        }

        if (stripos($url, '?page=') === false && stripos($url, '&page=') === false) {
            $pages = $this->parser->css('ul.pagination li a');
            foreach ($pages ?? [] as $pageUrl) {
                if ($pageUrl['@']['href'] != $url) {
                    $this->proccessPage($pageUrl['@']['href'], $ret);
                }
            }
        }
    }
}
