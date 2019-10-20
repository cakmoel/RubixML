<?php

namespace Rubix\ML\Kernels\Distance;

use Rubix\ML\Other\Helpers\DataType;

/**
 * Euclidean
 *
 * Standard straight line (*bee* line) distance between two points. The Euclidean
 * distance has the nice property of being invariant under any rotation.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Euclidean implements Distance
{
    /**
     * Return the data types that this kernel is compatible with.
     *
     * @return int[]
     */
    public function compatibility() : array
    {
        return [
            DataType::CONTINUOUS,
        ];
    }

    /**
     * Compute the distance between two vectors.
     *
     * @param array $a
     * @param array $b
     * @return float
     */
    public function compute(array $a, array $b) : float
    {
        $distance = 0.;

        foreach ($a as $i => $value) {
            $distance += ($value - $b[$i]) ** 2;
        }

        return sqrt($distance);
    }
}
