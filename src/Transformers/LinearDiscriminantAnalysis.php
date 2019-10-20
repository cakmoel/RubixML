<?php

namespace Rubix\ML\Transformers;

use Rubix\Tensor\Matrix;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Dataset;
use Rubix\ML\Other\Helpers\DataType;
use Rubix\ML\Other\Specifications\DatasetIsCompatibleWithTransformer;
use InvalidArgumentException;
use RuntimeException;

use const Rubix\ML\EPSILON;

/**
 * Linear Discriminant Analyis
 *
 * A supervised dimensionality reduction technique that selects the most discriminating
 * features based on their class labels. In other words, LDA finds a linear combination
 * of features that characterizes or best separates two or more classes.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class LinearDiscriminantAnalysis implements Transformer, Stateful
{
    /**
     * The target number of dimensions to project onto.
     *
     * @var int
     */
    protected $dimensions;

    /**
     * The matrix of eigenvectors computed at fitting.
     *
     * @var \Rubix\Tensor\Matrix|null
     */
    protected $eigenvectors;

    /**
     * The amount of variance that is preserved by the transformation.
     *
     * @var float|null
     */
    protected $explainedVar;

    /**
     * The amount of variance lost by discarding the noise components.
     *
     * @var float|null
     */
    protected $noiseVar;

    /**
     * The percentage of information lost due to the transformation.
     *
     * @var float|null
     */
    protected $lossiness;

    /**
     * @param int $dimensions
     * @throws \InvalidArgumentException
     */
    public function __construct(int $dimensions)
    {
        if ($dimensions < 1) {
            throw new InvalidArgumentException('Cannot project onto less than'
                . ' 1 dimension.');
        }

        $this->dimensions = $dimensions;
    }

    /**
     * Return the data types that this transformer is compatible with.
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
     * Is the transformer fitted?
     *
     * @return bool
     */
    public function fitted() : bool
    {
        return isset($this->eigenvectors);
    }

    /**
     * Return the amount of variance that has been preserved by the
     * transformation.
     *
     * @return float|null
     */
    public function explainedVar() : ?float
    {
        return $this->explainedVar;
    }

    /**
     * Return the amount of variance lost by discarding the noise components.
     *
     * @return float|null
     */
    public function noiseVar() : ?float
    {
        return $this->noiseVar;
    }

    /**
     * Return the percentage of information lost due to the transformation.
     *
     * @return float|null
     */
    public function lossiness() : ?float
    {
        return $this->lossiness;
    }

    /**
     * Fit the transformer to the dataset.
     *
     * @param \Rubix\ML\Datasets\Dataset $dataset
     * @throws \InvalidArgumentException
     */
    public function fit(Dataset $dataset) : void
    {
        if (!$dataset instanceof Labeled) {
            throw new InvalidArgumentException('This estimator requires a'
                . ' labeled training set.');
        }

        DatasetIsCompatibleWithTransformer::check($dataset, $this);

        if ($dataset->labelType() !== DataType::CATEGORICAL) {
            throw new InvalidArgumentException('This transformer only works'
                . ' with categorical labels.');
        }

        [$m, $n] = $dataset->shape();

        $sW = Matrix::zeros($n, $n);

        foreach ($dataset->stratify() as $stratum) {
            $sW = Matrix::build($stratum->samples())
                ->transpose()
                ->covariance()
                ->multiply($stratum->numRows() / $m)
                ->add($sW);
        }

        $sB = Matrix::quick($dataset->samples())
            ->transpose()
            ->covariance()
            ->subtract($sW);

        [$eigenvalues, $eigenvectors] = $sB->eig(true);

        $totalVar = array_sum($eigenvalues);

        $eigenvectors = $eigenvectors->asArray();
        
        array_multisort($eigenvalues, SORT_DESC, $eigenvectors);

        $eigenvalues = array_slice($eigenvalues, 0, $this->dimensions);
        $eigenvectors = array_slice($eigenvectors, 0, $this->dimensions);

        $eigenvectors = Matrix::quick($eigenvectors)->transpose();

        $explainedVar = (float) array_sum($eigenvalues);
        $noiseVar = $totalVar - $explainedVar;

        $this->explainedVar = $explainedVar;
        $this->noiseVar = $noiseVar;
        $this->lossiness = $noiseVar / ($totalVar ?: EPSILON);

        $this->eigenvectors = $eigenvectors;
    }

    /**
     * Transform the dataset in place.
     *
     * @param array $samples
     * @throws \RuntimeException
     */
    public function transform(array &$samples) : void
    {
        if (!$this->eigenvectors) {
            throw new RuntimeException('Transformer has not been fitted.');
        }

        $samples = Matrix::build($samples)
            ->matmul($this->eigenvectors)
            ->asArray();
    }
}
