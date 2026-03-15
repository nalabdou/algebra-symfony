<?php

declare(strict_types=1);

namespace App\Controller;

use Nalabdou\Algebra\Aggregate\AggregateRegistry;
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Collection\CollectionFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Demo: using algebra-php in a Symfony controller.
 *
 * The CollectionFactory and AggregateRegistry are injected by Symfony DI.
 * Algebra::from() also works directly — the bundle wires the singletons
 * on the first request via AlgebraBootstrapListener.
 */
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly CollectionFactory $algebraFactory,
        private readonly AggregateRegistry $aggregates,
    ) {
    }

    /**
     * GET /api/orders/dashboard.
     *
     * Returns aggregated order statistics — one pipeline per widget,
     * all built with algebra-php.
     */
    #[Route('/api/orders/dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        // Imagine $orders comes from Doctrine or an API
        $orders = $this->getOrders();

        $data = Algebra::parallel([
            'kpis' => Algebra::from($orders)
                ->where("item['status'] == 'paid'")
                ->aggregate([
                    'total' => 'sum(amount)',
                    'count' => 'count(*)',
                    'avg' => 'avg(amount)',
                    'median' => 'median(amount)',
                ]),

            'by_status' => Algebra::from($orders)->tally('status'),

            'by_region' => Algebra::from($orders)
                ->where("item['status'] == 'paid'")
                ->groupBy('region')
                ->aggregate(['revenue' => 'sum(amount)', 'orders' => 'count(*)'])
                ->orderBy('revenue', 'desc'),

            'top5' => Algebra::from($orders)
                ->where("item['status'] == 'paid'")
                ->topN(5, by: 'amount'),

            'trend' => Algebra::from($orders)
                ->where("item['status'] == 'paid'")
                ->groupBy('month')
                ->aggregate(['revenue' => 'sum(amount)'])
                ->orderBy('_group', 'asc')
                ->window('running_sum', field: 'revenue', as: 'ytd'),
        ]);

        return $this->json($data);
    }

    /**
     * GET /api/orders/partition.
     *
     * Split orders into high-value (>500) and standard in one pass.
     */
    #[Route('/api/orders/partition', methods: ['GET'])]
    public function partition(): JsonResponse
    {
        $result = Algebra::from($this->getOrders())
            ->where("item['status'] == 'paid'")
            ->partition("item['amount'] > 500");

        return $this->json([
            'high_value' => [
                'count' => $result->passCount(),
                'orders' => $result->pass(),
                'rate' => \round($result->passRate() * 100, 1).'%',
            ],
            'standard' => [
                'count' => $result->failCount(),
                'orders' => $result->fail(),
            ],
        ]);
    }

    /**
     * GET /api/orders/custom-aggregate.
     *
     * Example using the custom GeomeanAggregate registered via #[AsAggregate].
     */
    #[Route('/api/orders/custom-aggregate', methods: ['GET'])]
    public function customAggregate(): JsonResponse
    {
        $result = Algebra::from($this->getOrders())
            ->where("item['status'] == 'paid'")
            ->groupBy('region')
            ->aggregate([
                'avg_amount' => 'avg(amount)',
                'geomean_amount' => 'geomean(amount)',  // custom aggregate
            ])
            ->toArray();

        return $this->json($result);
    }

    private function getOrders(): array
    {
        return [
            ['id' => 1, 'status' => 'paid',    'amount' => 500, 'region' => 'Nord', 'month' => '2024-01'],
            ['id' => 2, 'status' => 'pending', 'amount' => 200, 'region' => 'Sud',  'month' => '2024-01'],
            ['id' => 3, 'status' => 'paid',    'amount' => 300, 'region' => 'Nord', 'month' => '2024-02'],
            ['id' => 4, 'status' => 'paid',    'amount' => 800, 'region' => 'Est',  'month' => '2024-02'],
            ['id' => 5, 'status' => 'paid',    'amount' => 150, 'region' => 'Sud',  'month' => '2024-03'],
        ];
    }
}
