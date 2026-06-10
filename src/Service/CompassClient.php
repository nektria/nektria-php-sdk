<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Infrastructure\SharedInvalidCoordinatesCache;
use Nektria\Infrastructure\SharedUserV2Cache;

use function count;

/**
 * @phpstan-type CompassPing array{
 *     response: string
 * }
 *
 * @phpstan-type CompassAddress array{
 *     addressLine1: string,
 *     addressLine2: string,
 *     elevator: bool,
 *     postalCode: string,
 *     city: string,
 *     countryCode: string,
 *     latitude: float,
 *     longitude: float,
 * }
 *
 * @phpstan-type CompassCoordinate array{
 *     latitude: float,
 *     longitude: float,
 * }
 *
 * @phpstan-type CompassWaypoint array{
 *      destination: string,
 *      latitude: float,
 *      longitude: float,
 *  }
 *
 * @phpstan-type CompassCoordinateWithHash array{
 *     hash: string,
 *     latitude: float,
 *     longitude: float,
 * }
 *
 * @phpstan-type CompassLegacyDistance array{
 *      distance: int,
 *      travelTime: int,
 *      originLatitude: float,
 *      originLongitude: float,
 *      destinationLatitude: float,
 *      destinationLongitude: float,
 * }
 *
 * @phpstan-type CompassDistance array{
 *      destination: string,
 *      distance: int,
 *      travelTime: int,
 * }
 *
 * @phpstan-type CompassDistanceResult array<string, array{
 *      distance: int,
 *      travelTime: int,
 * }>
 *
 * @phpstan-type CompassGeoPolygon array{
 *      distance: int,
 *      coordinates: CompassCoordinate[],
 * }
 */
readonly class CompassClient extends AbstractService
{
    public function __construct(
        protected SharedUserV2Cache $sharedUserCache,
        private SharedInvalidCoordinatesCache $sharedInvalidCoordinatesCache,
        private string $compassHost
    ) {
        parent::__construct();
    }

    /**
     * @param CompassAddress $address
     * @return CompassCoordinate
     */
    public function fixCoordinates(array $address): array
    {
        $isInvalid = $this->sharedInvalidCoordinatesCache->isInvalid($address['latitude'], $address['longitude']);
        // $this->sharedInvalidCoordinatesCache->delete($address['latitude'], $address['longitude']);
        if ($isInvalid === false) {
            $this->sharedInvalidCoordinatesCache->save($address['latitude'], $address['longitude'], false);

            return [
                'latitude' => $address['latitude'],
                'longitude' => $address['longitude'],
            ];
        }

        if (
            $address['addressLine1'] === ''
            || $address['postalCode'] === '08999'
            || $this->contextService()->isTest()
        ) {
            return [
                'latitude' => $address['latitude'],
                'longitude' => $address['longitude'],
            ];
        }

        $coordinates = $this->requestClient()->patch(
            "{$this->compassHost}/api/admin/addresses/fix-address",
            data: $address,
            headers: $this->getHeaders(),
        )->json();

        if ($coordinates['latitude'] !== 0.0 && $coordinates['longitude'] !== 0.0) {
            $this->sharedInvalidCoordinatesCache->save($coordinates['latitude'], $coordinates['longitude'], false);
        }

        return $coordinates;
    }

    /**
     * @param CompassAddress $address
     * @return CompassCoordinate
     */
    public function getCoordinates(array $address): array
    {
        if (
            $address['addressLine1'] === ''
            || $address['postalCode'] === '08999'
            || $this->contextService()->isTest()
        ) {
            return [
                'latitude' => $address['latitude'],
                'longitude' => $address['longitude'],
            ];
        }

        return $this->requestClient()->get(
            "{$this->compassHost}/api/admin/addresses/coordinates",
            data: $address,
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @param array<int, CompassCoordinateWithHash> $coordinates
     * @return CompassDistanceResult
     */
    public function getDistanceMatrix(string $travelMode, array $coordinates): array
    {
        $list = [];
        /** @var array<string, string> $hashMap */
        $hashMap = [];
        $totalWaypoints = count($coordinates);

        $list[] = "{$coordinates[0]['latitude']},{$coordinates[0]['longitude']}";

        for ($i = 1; $i < $totalWaypoints; ++$i) {
            $coordinate = $coordinates[$i];
            $prevCoordinate = $coordinates[$i - 1];
            $c1 = "{$prevCoordinate['latitude']},{$prevCoordinate['longitude']}";
            $c2 = "{$coordinate['latitude']},{$coordinate['longitude']}";
            $list[] = "{$coordinate['latitude']},{$coordinate['longitude']}";
            $hashMap["{$c1},{$c2}"] = $coordinate['hash'];
        }

        /** @var CompassLegacyDistance[] $data */
        $data = $this->requestClient()->get(
            "{$this->compassHost}/api/admin/distances",
            data: [
                'wayPoints' => implode('|', $list),
                'travelMode' => $travelMode,
            ],
            headers: $this->getHeaders(),
        )->json();

        $result = [];

        for ($i = 1; $i < $totalWaypoints; ++$i) {
            $cell = $data[$i - 1];

            $c1 = "{$cell['originLatitude']},{$cell['originLongitude']}";
            $c2 = "{$cell['destinationLatitude']},{$cell['destinationLongitude']}";
            $hash = $hashMap["{$c1},{$c2}"];

            $result[$hash] = [
                'distance' => $cell['distance'],
                'travelTime' => $cell['travelTime'],
            ];
        }

        return $result;
    }

    /**
     * @param CompassWaypoint[] $waypoints
     * @return CompassDistance[]
     */
    public function getDistances(string $travelMode, array $waypoints): array
    {
        return $this->requestClient()->patch(
            "{$this->compassHost}/api/admin/distances",
            data: [
                'waypoints' => $waypoints,
                'travelMode' => $travelMode,
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @param CompassCoordinate $center
     * @param int[] $distances
     * @return CompassGeoPolygon[]
     */
    public function getGeoPolygons(array $center, string $travelMode, array $distances, string $type): array
    {
        return $this->requestClient()->get(
            "{$this->compassHost}/api/admin/geo-polygons",
            data: [
                'center' => "{$center['latitude']},{$center['longitude']}",
                'distances' => implode(',', $distances),
                'travelMode' => $travelMode,
                'type' => $type,
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @param CompassCoordinate[] $coordinates
     * @return CompassLegacyDistance[]
     * @deprecated Use getDistanceMatrix instead
     */
    public function getLegacyDistances(string $travelMode, array $coordinates): array
    {
        $list = [];
        foreach ($coordinates as $coordinate) {
            $list[] = "{$coordinate['latitude']},{$coordinate['longitude']}";
        }

        return $this->requestClient()->get(
            "{$this->compassHost}/api/admin/distances",
            data: [
                'wayPoints' => implode('|', $list),
                'travelMode' => $travelMode,
            ],
            headers: $this->getHeaders(),
        )->json();
    }

    /**
     * @return CompassPing
     */
    public function ping(): array
    {
        return $this->requestClient()->get("{$this->compassHost}/ping")->json();
    }

    /**
     * @param CompassAddress $address
     */
    public function saveAddress(array $address): void
    {
        if (
            $address['addressLine1'] === ''
            || $address['postalCode'] === '08999'
            || $this->contextService()->isTest()
        ) {
            return;
        }

        $this->requestClient()->put(
            "{$this->compassHost}/api/admin/addresses",
            data: $address,
            headers: $this->getHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        $tenantId = $this->contextService()->getExtra('tenantId') ?? 'none';
        $apiKey =
            $this->sharedUserCache->read("SYSTEM_{$tenantId}")->apiKey ??
            $this->sharedUserCache->read("ADMIN_{$tenantId}")->apiKey ??
            'none';

        return [
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
            'X-Api-Id' => $apiKey,
            'X-Nektria-App' => 'compass',
            'X-Trace' => $this->contextService()->traceId(),
            'X-Origin' => $this->contextService()->project(),
        ];
    }
}
