<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Document\WarehouseSharedInfo;

use function count;

readonly class GeoUtil
{
    /**
     * @param array{latitude: float, longitude: float} $from
     * @param array{latitude: float, longitude: float} $to
     * @return int distance in meters
     */
    public static function distance(WarehouseSharedInfo $warehouse, array $from, array $to): int
    {
        $dx = ($from['latitude'] - $to['latitude']) * $warehouse->latitudeFactor;
        $dy = ($from['longitude'] - $to['longitude']) * $warehouse->longitudeFactor;

        return (int) ceil(sqrt($dx * $dx + $dy * $dy));
    }

    /**
     * @param float[] $point
     * @param float[][] $polygon
     */
    public static function pointInPolygon(array $point, array $polygon): bool
    {
        $c = false;
        $points = count($polygon);
        $j = 1;
        for ($i = 0; $j < $points; ++$i) {
            $vix = $polygon[$i][0];
            $viy = $polygon[$i][1];
            $vjx = $polygon[$j][0];
            $vjy = $polygon[$j][1];
            if (
                (($viy > $point[0]) !== ($vjy > $point[0]))
                && ($point[1] < (($vjx - $vix) * ($point[0] - $viy)) / ($vjy - $viy) + $vix)
            ) {
                $c = !$c;
            }

            ++$j;
        }

        return $c;
    }
}
