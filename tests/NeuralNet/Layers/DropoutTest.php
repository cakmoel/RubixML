<?php

namespace Rubix\ML\Tests\NeuralNet\Layers;

use Rubix\Tensor\Matrix;
use Rubix\ML\NeuralNet\Layers\Layer;
use Rubix\ML\NeuralNet\Layers\Hidden;
use Rubix\ML\NeuralNet\Layers\Dropout;
use Rubix\ML\NeuralNet\Layers\Nonparametric;
use Rubix\ML\NeuralNet\Optimizers\Stochastic;
use PHPUnit\Framework\TestCase;

class DropoutTest extends TestCase
{
    protected const RANDOM_SEED = 0;

    protected $fanIn;

    protected $input;

    protected $prevGrad;

    protected $optimizer;

    protected $layer;

    public function setUp()
    {
        $this->fanIn = 3;

        $this->input = Matrix::quick([
            [1., 2.5, -0.1],
            [0.1, 0., 3.],
            [0.002, -6., -0.5],
        ]);

        $this->prevGrad = function () {
            return Matrix::quick([
                [0.25, 0.7, 0.1],
                [0.50, 0.2, 0.01],
                [0.25, 0.1, 0.89],
            ]);
        };

        $this->optimizer = new Stochastic();

        $this->layer = new Dropout(0.5);

        srand(self::RANDOM_SEED);
    }

    public function test_build_layer()
    {
        $this->assertInstanceOf(Dropout::class, $this->layer);
        $this->assertInstanceOf(Layer::class, $this->layer);
        $this->assertInstanceOf(Hidden::class, $this->layer);
        $this->assertInstanceOf(Nonparametric::class, $this->layer);

        $this->layer->initialize($this->fanIn);

        $this->assertEquals($this->fanIn, $this->layer->width());
    }

    public function test_forward_back_infer()
    {
        $this->layer->initialize($this->fanIn);

        $expected = [
            [0., 0., -0.],
            [0., 0., 0.],
            [0.002, -6.0, -0.5],
        ];

        $forward = $this->layer->forward($this->input);

        $this->assertInstanceOf(Matrix::class, $forward);
        $this->assertEquals($expected, $forward->asArray());

        $back = $this->layer->back($this->prevGrad, $this->optimizer);

        $this->assertInternalType('callable', $back);

        $expected = [
            [0., 0., 0.],
            [0., 0.2, 0.],
            [0.25, 0.1, 0.89],
        ];

        $back = $back();

        $this->assertInstanceOf(Matrix::class, $back);
        $this->assertEquals($expected, $back->asArray());

        $expected = [
            [1., 2.5, -0.1],
            [0.1, 0., 3.],
            [0.002, -6., -0.5],
        ];

        $infer = $this->layer->infer($this->input);

        $this->assertInstanceOf(Matrix::class, $infer);
        $this->assertEquals($expected, $infer->asArray());
    }
}
