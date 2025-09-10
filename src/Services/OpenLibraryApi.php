<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenLibraryApi
{
    public const API_URL = 'https://openlibrary.org/api/books?jscmd=data&format=json&bibkeys=ISBN:';

    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getBookData(string $isbn): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . $isbn);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();

            // Check if the response is empty or the ISBN key doesn't exist
            if (empty($data)) {
                return [];
            }

            $isbnKey = 'ISBN:' . $isbn;
            if (!isset($data[$isbnKey])) {
                return [];
            }

            $bookData = $data[$isbnKey];

            // Extract title and publish_date
            $result = [];

            if (isset($bookData['title'])) {
                $result['title'] = $bookData['title'];
            }

            if (isset($bookData['publish_date'])) {
                $result['publish_date'] = $bookData['publish_date'];
            }

            return $result;

        } catch (\Exception $e) {
            // Return empty array if any error occurs
            return [];
        }
    }
}
