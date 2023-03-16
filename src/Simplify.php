<?php
namespace bigDream\simplify;

/**
 * Simplify path by removing extra points with given tolerance
 * Port of simplify.js algorithm
 * http://github.com/andreychumak/simplify-php
 *
 * (c) 2013, Vladimir Agafonkin
 * Simplify.js, a high-performance JS polyline simplification library
 * http://mourner.github.io/simplify-js
*/

class Simplify
{
    public function __construct(protected string $xField = 'x', protected string $yField = 'y')
    {

    }

	/**
	 * @param array     $points
	 * @param float|int $tolerance
	 * @param bool      $highestQuality
	 * @return array
	 */
	public function run(array $points, float|int $tolerance = 1, bool $highestQuality = false): array
    {
		if (count($points) <= 1) return $points;

		$sqTolerance = $tolerance * $tolerance;

		$points = $highestQuality ? $points : $this->simplifyRadialDist($points, $sqTolerance);
        return $this->simplifyDouglasPeucker($points, $sqTolerance);
	}

	// basic distance-based simplification
	private function simplifyRadialDist(array $points, float|int $sqTolerance): array
    {

		$prevPoint = $points[0];
		$newPoints = array($prevPoint);
		$point = null;

		for ($i = 1, $len = count($points); $i < $len; $i++) {
			$point = $points[$i];

			if ($this->getSqDist($point, $prevPoint) > $sqTolerance) {
				$newPoints[] = $point;
				$prevPoint = $point;
			}
		}

		if ($prevPoint !== $point) $newPoints[] = $point;

		return $newPoints;
	}

	// square distance between 2 points
	private function getSqDist(array $p1, array $p2): float|int
    {
		$dx = $p1[$this->xField] - $p2[$this->xField];
		$dy = $p1[$this->yField] - $p2[$this->yField];

		return $dx * $dx + $dy * $dy;
	}

	// simplification using optimized Douglas-Peucker algorithm with recursion elimination
	private function simplifyDouglasPeucker($points, $sqTolerance): array
    {

		$len = count($points);
		$markers = array_fill(0, $len-1, null);
		$first = 0;
		$last = $len - 1;
		$stack = array();
		$newPoints = array();
		$index = null;

		$markers[$first] = $markers[$last] = 1;

		while ($last) {

			$maxSqDist = 0;

			for ($i = $first + 1; $i < $last; $i++) {
				$sqDist = $this->getSqSegDist($points[$i], $points[$first], $points[$last]);

				if ($sqDist > $maxSqDist) {
					$index = $i;
					$maxSqDist = $sqDist;
				}
			}

			if ($maxSqDist > $sqTolerance) {
				$markers[$index] = 1;
				array_push($stack, $first, $index, $index, $last);
			}

			$last = array_pop($stack);
			$first = array_pop($stack);
		}

		//var_dump($markers, $points, $i);
		for ($i = 0; $i < $len; $i++) {
			if ($markers[$i]) $newPoints[] = $points[$i];
		}

		return $newPoints;
	}

	// square distance from a point to a segment
	private function getSqSegDist(array $p, array $p1, array $p2): float|int
    {
		$x = $p1[$this->xField];
		$y = $p1[$this->yField];
		$dx = $p2[$this->xField] - $x;
		$dy = $p2[$this->yField] - $y;

		if (intval($dx) !== 0 || intval($dy) !== 0) {

			$t = (($p[$this->xField] - $x) * $dx + ($p[$this->yField] - $y) * $dy) / ($dx * $dx + $dy * $dy);

			if ($t > 1) {
				$x = $p2[$this->xField];
				$y = $p2[$this->yField];

			} else if ($t > 0) {
				$x += $dx * $t;
				$y += $dy * $t;
			}
		}

		$dx = $p[$this->xField] - $x;
		$dy = $p[$this->yField] - $y;

		return $dx * $dx + $dy * $dy;
	}

}
