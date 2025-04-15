<?php
namespace App\Service;

    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class GeoApiService
    {
        private HttpClientInterface $httpClient;

        public function __construct(HttpClientInterface $httpClient)
        {
            $this->httpClient = $httpClient;
        }

        public function getRegions(): array
        {
            $response = $this->httpClient->request('GET', 'https://geo.api.gouv.fr/regions');
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            $regions = json_decode( $response->getContent(),true);

            $choices = [];
            foreach ($regions as $region) {
                $choices[$region['nom']] = $region['code'];
            }

            return $choices;
        }

        public function getDepartementsByRegion(string $regionCode): array
        {
            $url = 'https://geo.api.gouv.fr/regions/' . $regionCode . '/departements';
            $response = $this->httpClient->request('GET', $url);

            return $response->getStatusCode() === 200 ? $response->toArray() : [];
        }

        public function getVillesByDepartement(string $departementCode): array
        {
            $url = 'https://geo.api.gouv.fr/departements/' . $departementCode . '/communes';
            $response = $this->httpClient->request('GET', $url);

            return $response->getStatusCode() === 200 ? $response->toArray() : [];
        }
    }