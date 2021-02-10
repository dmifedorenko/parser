<?php


namespace App\Service;

/*
ID: 5132ac55beb64f02b9d09364cb8490d4
Пароль: 6b01cfaccc1543ff9dcdc09ac93b33f8
Callback URL: https://www.100sp.ru/
Время жизни токена: Не менее, чем 1 год
Дата создания: 08.02.2021

https://yandex.ru/dev/disk/poligon/
*/

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YandexDisk
{
    private string $token;
    private string $nameSpace;
    private string $appName;
    private HttpClientInterface $client;

    public function __construct(string $token, string $nameSpace, HttpClientInterface $client)
    {
        $this->token = $token;
        $this->nameSpace = $nameSpace;
        $this->client = $client;
    }

    public function setAppName(string $appName): self
    {
        $this->appName = $appName;

        return $this;
    }

    private function request(string $method, string $command, array $query): array
    {
        $response = $this->client->request($method,
            'https://cloud-api.yandex.net/v1/disk/' . $command,
            [
                'headers' => [
                    'Authorization: OAuth ' . $this->token,
                ],
                'query' => $query
            ]
        )->toArray();

        if (!empty($response['error'])) {
            throw new BadRequestException($method . ' ' . $command . ' ' . print_r($response['error'], true));
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

    public function connect()
    {
        $response = $this->request('GET', 'resources', ['path' => '/']);

        if (!$this->findResponseItemByName($response, $this->nameSpace)) {
            $this->request('PUT', 'resources', ['path' => $this->nameSpace]);
        }

        $response = $this->request('GET', 'resources', ['path' => '/' . $this->nameSpace]);
        $appPath = $this->nameSpace . '/' . $this->appName;
        if (!$this->findResponseItemByName($response, $this->appName)) {
            $this->request('PUT', 'resources', ['path' => $appPath]);
        }


        $response = $this->request('GET', 'resources/upload', ['path' => $appPath . '/test.txt', 'overwrite' => true]);
        /*
        $this->request('PUT', 'resources/upload', [
            'path' => $appPath . '/test.txt',
            'url'
            'overwrite' => true,
        ]);
        */

        print_r($response);
        exit;
    }
}