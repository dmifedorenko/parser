<?php

namespace App\Service;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YandexDisk
{
    private string $token;
    private string $nameSpace;
    private string $appName;
    private HttpClientInterface $client;
    private bool $connected = false;
    private string $cacheDir;

    public function __construct(string $token, string $nameSpace, string $cacheDir, HttpClientInterface $client)
    {
        $this->token = $token;
        $this->nameSpace = $nameSpace;
        $this->client = $client;
        $this->cacheDir = $cacheDir;
    }

    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    private function request(string $method, string $url, array $query, array $extraOptions = []): array
    {
        $url = stripos($url, '://') === false ? 'https://cloud-api.yandex.net/v1/disk/' . $url : $url;

        $response = $this->client->request(
            $method,
            $url,
            array_merge_recursive([
                'headers' => [
                    'Authorization: OAuth ' . $this->token,
                ],
                'query' => $query,
            ], $extraOptions)
        )->toArray();

        if (!empty($response['error'])) {
            throw new BadRequestException($method . ' ' . $url . ' ' . print_r($response['error'], true));
        }

        return $response;
    }

    private function findResponseItemByName(array $response, string $name): ?array
    {
        foreach ($response['_embedded']['items'] as $item) {
            if ($item['name'] == $name) {
                return $item;
            }
        }

        return null;
    }

    private function connect(): void
    {
        if ($this->connected) {
            return;
        }
        $this->connected = true;

        $response = $this->request('GET', 'resources', ['path' => '/']);

        if (!$this->findResponseItemByName($response, $this->nameSpace)) {
            $this->request('PUT', 'resources', ['path' => $this->nameSpace]);
        }
        $this->request('PUT', 'resources/publish', ['path' => $this->nameSpace]);

        $response = $this->request('GET', 'resources', ['path' => '/' . $this->nameSpace]);
        $appPath = $this->nameSpace . '/' . $this->appName;
        if (!$this->findResponseItemByName($response, $this->appName)) {
            $this->request('PUT', 'resources', ['path' => $appPath]);
        }

        $this->request('PUT', 'resources/publish', ['path' => $appPath]);
    }

    private function makeUnploadFile(string $file, string $content): array
    {
        file_put_contents($file, $content);

        $headers = [
            'Content-Length' => filesize($file),
        ];
        $finfo = finfo_open(FILEINFO_MIME);
        $mime = finfo_file($finfo, $file);
        $parts = explode(';', $mime);

        $headers['Content-Type'] = $parts[0];
        $headers['Etag'] = md5_file($file);
        $headers['Sha256'] = hash_file('sha256', $file);

        return $headers;
    }

    public function upload(string $name, string $content): string
    {
        try {
            $path = '/' . $this->nameSpace . '/' . $this->appName . '/' . $name;
            $this->connect();

            $file = $this->cacheDir . '/yandex_tmp';

            echo 'Upload to yandex ' . $path . PHP_EOL;

            $response = $this->request('GET', 'resources/upload', [
                'path' => $path,
                'overwrite' => true,
            ]);

            try {
                $this->request('PUT', $response['href'], [
                    'path' => $path,
                ], [
                    'body' => $content,
                    'headers' => $this->makeUnploadFile($file, $content),
                ]);
            } catch (JsonException $e) {
                // PUT всегда пустой ответ дает, это нормально
                if ($e->getMessage() != 'Response body is empty.') {
                    throw $e;
                }
            }
            unlink($file);

            // Беслпатный аккаунт Яндекса не дает быстро заливать файлы
            sleep(3);

            $this->request('PUT', 'resources/publish', ['path' => $path]);

            $response = $this->request('GET', 'resources', ['path' => $path]);

            $response = $this->request('GET', 'resources/download', [
                'public_key' => $response['public_key'],
                'path' => $response['path'],
            ]);

            echo 'Yandex file upload - ' . $response['href'] . PHP_EOL;

            return $response['href'];
        } catch (\Throwable $e) {
            echo $e;
            exit;
        }
    }
}
