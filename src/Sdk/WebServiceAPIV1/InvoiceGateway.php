<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV1;

use VRPayment\PluginCore\Localization\LocalizedString;
use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\InvoiceMapperTrait;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Transaction\Invoice\Exception\InvoiceException;
use VRPayment\PluginCore\Transaction\Invoice\Invoice;
use VRPayment\PluginCore\Transaction\Invoice\InvoiceCollection;
use VRPayment\PluginCore\Transaction\Invoice\InvoiceGatewayInterface;
use VRPayment\PluginCore\Transaction\Invoice\InvoiceSearchCriteria;
use VRPayment\Sdk\ApiException;
use VRPayment\Sdk\Model\CriteriaOperator as SdkCriteriaOperator;
use VRPayment\Sdk\Model\EntityQuery as SdkEntityQuery;
use VRPayment\Sdk\Model\EntityQueryFilter as SdkEntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType as SdkEntityQueryFilterType;
use VRPayment\Sdk\Model\EntityQueryOrderBy as SdkEntityQueryOrderBy;
use VRPayment\Sdk\Model\EntityQueryOrderByType as SdkEntityQueryOrderByType;
use VRPayment\Sdk\Service\TransactionInvoiceService as SdkTransactionInvoiceService;

#[LogContext(domain: 'transaction', subdomain: 'invoice')]
class InvoiceGateway implements InvoiceGatewayInterface
{
    use DomainLoggerTrait;
    use InvoiceMapperTrait;

    private SdkTransactionInvoiceService $transactionInvoiceService;

    public function __construct(
        private readonly SdkProvider $sdkProvider,
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
        $this->transactionInvoiceService = $this->sdkProvider->getService(SdkTransactionInvoiceService::class);
    }

    public function find(int $spaceId, int $invoiceId): ?Invoice
    {
        try {
            $sdkInvoice = $this->transactionInvoiceService->read($spaceId, $invoiceId);
            return $this->mapToInvoice($sdkInvoice);
        } catch (\Throwable $e) {
            if ($e instanceof ApiException && $e->getCode() === 404) {
                $this->logger->debug('Gateway: Transaction invoice not found.', [
                    'invoiceId' => $invoiceId,
                    'spaceId' => $spaceId,
                ]);
                return null;
            }

            $this->logger->error('Gateway: Failed to find transaction invoice.', [
                'exception' => $e,
                'invoiceId' => $invoiceId,
                'spaceId' => $spaceId,
            ]);
            throw new InvoiceException(
                "Failed to find transaction invoice {$invoiceId}: " . $e->getMessage(),
                new LocalizedString('An error occurred while retrieving the transaction invoice.'),
                $e,
            );
        }
    }

    public function get(int $spaceId, int $invoiceId): Invoice
    {
        $this->logger->debug('Gateway: Reading transaction invoice.', [
            'invoiceId' => $invoiceId,
            'spaceId' => $spaceId,
        ]);

        try {
            $sdkInvoice = $this->transactionInvoiceService->read($spaceId, $invoiceId);
            return $this->mapToInvoice($sdkInvoice);
        } catch (\Throwable $e) {
            $this->logger->error('Gateway: Failed to read transaction invoice.', [
                'exception' => $e,
                'invoiceId' => $invoiceId,
                'spaceId' => $spaceId,
            ]);
            throw new InvoiceException(
                "Failed to read transaction invoice {$invoiceId}: " . $e->getMessage(),
                new LocalizedString('An error occurred while retrieving the transaction invoice.'),
                $e,
            );
        }
    }

    public function search(int $spaceId, InvoiceSearchCriteria $criteria): InvoiceCollection
    {
        $this->logger->debug('Gateway: Searching transaction invoices.', ['spaceId' => $spaceId]);

        $query = new SdkEntityQuery();

        if ($criteria->limit !== null) {
            $query->setNumberOfEntities($criteria->limit);
        }

        if ($criteria->sortField !== null) {
            $orderBy = new SdkEntityQueryOrderBy();
            $orderBy->setFieldName($criteria->sortField);
            $orderBy->setSorting(
                strtoupper($criteria->sortOrder ?? 'DESC') === 'ASC'
                    ? SdkEntityQueryOrderByType::ASC
                    : SdkEntityQueryOrderByType::DESC,
            );
            $query->setOrderBys([$orderBy]);
        }

        if (!empty($criteria->filters)) {
            $filters = [];
            foreach ($criteria->filters as $field => $value) {
                $leaf = new SdkEntityQueryFilter();
                $leaf->setFieldName($field);
                /** @var mixed $value */
                $leaf->setValue($value);
                $leaf->setOperator(SdkCriteriaOperator::EQUALS);
                $leaf->setType(SdkEntityQueryFilterType::LEAF);
                $filters[] = $leaf;
            }

            if (count($filters) === 1) {
                $query->setFilter($filters[0]);
            } else {
                $root = new SdkEntityQueryFilter();
                $root->setType(SdkEntityQueryFilterType::_AND);
                $root->setChildren($filters);
                $query->setFilter($root);
            }
        }

        try {
            $results = $this->transactionInvoiceService->search($spaceId, $query);
            return new InvoiceCollection(...array_map([$this, 'mapToInvoice'], $results));
        } catch (\Throwable $e) {
            $this->logger->error('Gateway: Failed to search transaction invoices.', [
                'exception' => $e,
                'spaceId' => $spaceId,
            ]);
            throw new InvoiceException(
                'Failed to search transaction invoices: ' . $e->getMessage(),
                new LocalizedString('An error occurred while searching transaction invoices.'),
                $e,
            );
        }
    }
}
