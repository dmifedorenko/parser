<?php

declare(strict_types=1);

namespace App\Service\Site;

use App\Kernel;
use App\Service\SimpleImage;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Output\OutputInterface;

class Excel extends SiteParser
{
    private array $images;
    private string $drawingsCacheDir;

    public function parse(OutputInterface $output): void
    {
        parent::parse($output);

        $this->drawingsCacheDir = Kernel::get()->getCacheDir() . '/../drawings';
        if (!file_exists($this->drawingsCacheDir)) {
            mkdir($this->drawingsCacheDir);
        }

        $file = Kernel::get()->getProjectDir() . '/var/parser_in/excel.xlsx';
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $this->buildImages($worksheet);

        $prevAdd = [];
        $items = [];
        $allSizes = [];
        try {
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $cells = [];
                foreach ($cellIterator as $cell) {
                    $cells[] = $cell->getValue();
                }
                if ($cells[0] === 'Картинка' || empty($cells[5])) {
                    continue;
                }

                $add = [
                    'art' => $cells[1],
                    'name' => $this->formatText($cells[3]),
                    'description' => $this->formatText($cells[4]),
                    'price' => $cells[5],
                    'colors' => $cells[2],
                    'images' => $this->images['A' . $row->getRowIndex()] ?? [],
                ];

                $add['collection'] = explode(',', $add['name'])[0];

                preg_match('~размер\s([^\s]+)~u', $add['name'], $sizes);
                $add['size'] = str_replace(',', '.', trim($sizes[1], ','));

                if (!$add['size']) {
                    $add['size'] = '-';
                }

                // Наследую данные от прошлого прохода если есть объединение строк в таблице
                if (null === $add['art']) {
                    $add['art'] = $prevAdd['art'];
                    $add['colors'] = $prevAdd['colors'];

                    if (empty($add['collection'])) {
                        $add['collection'] = $prevAdd['collection'];
                    }
                    if (empty($add['images'])) {
                        $add['images'] = $prevAdd['images'];
                    }
                }
                $prevAdd = $add;

                $add['art'] = $add['art'] . '.' . preg_replace('~[\s,]+~', '.', $add['colors']);

                $allSizes[$add['art']][] = $add['size'];

                $items[$add['art']] = $add;

                //call_user_func_array([$this->parser, 'putRowDetails'], $add);

                if (count($items) >= 20) {
                    //break;
                }
            }

            foreach ($items as $art => $item) {
                if (empty($item['images'])) {
                    $this->output->writeln("<error>Not image, skip art {$art}</error>");
                    continue;
                }

                $this->parser->putRowDetails(
                    $item['collection'],
                    $art,
                    $item['name'],
                    $item['description'],
                    $item['price'],
                    '',
                    implode(',', $allSizes[$art]),
                    '',
                    0,
                    $item['images'],
                );

                //dump($art, $item, implode(',', $allSizes[$art]));
            }
        } catch (\Throwable $e) {
            dump($cells ?? [], $row->getRowIndex());
            throw $e;
        }
    }

    private function buildImages(Worksheet $worksheet): void
    {
        $cacheFile = $this->drawingsCacheDir . '/data.txt';
        if (file_exists($cacheFile)) {
            $this->images = unserialize(file_get_contents($cacheFile));

            return;
        }

        $this->images = [];
        foreach ($worksheet->getDrawingCollection() as $drawing) {
            $fileName = $this->drawingsCacheDir . '/' . $drawing->getCoordinates() . '.' . pathinfo($drawing->getName())['extension'];

            $zipReader = fopen($drawing->getPath(), 'rb');
            $imageContents = '';
            while (!feof($zipReader)) {
                $imageContents .= fread($zipReader, 1024);
            }
            fclose($zipReader);
            file_put_contents($fileName, $imageContents);

            $image = new SimpleImage();
            $image->load($fileName);
            $image->scale(2);
            $image->save($fileName, IMAGETYPE_JPEG, 95);

            $this->output->writeln($fileName);

            $src = $this->yandexDisk->upload(basename($fileName), $imageContents);

            $this->images[$drawing->getCoordinates()][] = $src;
        }

        file_put_contents($cacheFile, serialize($this->images));
        $this->writeln('<comment>' . $this->drawingsCacheDir . ' - ' . count($worksheet->getDrawingCollection()) . '</comment>');
    }

    private function formatText(string $text): string
    {
        $text = preg_replace('~([%,]+)([а-я]+)~ui', '$1 $2', $text);

        return $text;
    }
}
