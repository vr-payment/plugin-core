<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Sdk\WebServiceAPIV1;

use VRPayment\PluginCore\Log\DomainLoggerTrait;
use VRPayment\PluginCore\Log\LogContext;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Sdk\DateTimeMapperTrait;
use VRPayment\PluginCore\Sdk\SdkProvider;
use VRPayment\PluginCore\Transaction\Exception\TransactionCommentException;
use VRPayment\PluginCore\Transaction\TransactionComment;
use VRPayment\PluginCore\Transaction\TransactionCommentCollection;
use VRPayment\PluginCore\Transaction\TransactionCommentGatewayInterface;
use VRPayment\Sdk\Model\TransactionComment as SdkTransactionComment;
use VRPayment\Sdk\Service\TransactionCommentService as SdkTransactionCommentService;

/**
 * Gateway for retrieving transaction comments.
 */
#[LogContext(domain: 'transaction')]
class TransactionCommentGateway implements TransactionCommentGatewayInterface
{
    use DateTimeMapperTrait;
    use DomainLoggerTrait;

    /**
     * @var SdkTransactionCommentService
     */
    private SdkTransactionCommentService $service;

    /**
     * TransactionCommentGateway constructor.
     *
     * @param SdkProvider $sdkProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SdkProvider $sdkProvider,
        LoggerInterface $logger,
    ) {
        $this->initializeLogger($logger);
        $this->service = $this->sdkProvider->getService(SdkTransactionCommentService::class);
    }

    /**
     * @inheritDoc
     */
    public function getComments(int $spaceId, int $transactionId): TransactionCommentCollection
    {
        try {
            $this->logger->debug(
                'Fetching transaction comments.',
                [
                    'spaceId' => $spaceId,
                    'transactionId' => $transactionId,
                ],
            );
            $sdkComments = $this->service->all($spaceId, $transactionId);

            return new TransactionCommentCollection(...array_map([$this, 'mapToTransactionComment'], $sdkComments));
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to fetch transaction comments.',
                [
                    'errorMessage' => $e->getMessage(),
                    'exception' => $e,
                    'spaceId' => $spaceId,
                    'transactionId' => $transactionId,
                ],
            );
            throw SdkProvider::wrapException(
                $e,
                TransactionCommentException::class,
                'search',
                ['spaceId' => $spaceId, 'transactionId' => $transactionId],
                'An error occurred while fetching transaction comments.',
            );
        }
    }

    /**
     * Maps SDK TransactionComment to Domain object.
     *
     * @param SdkTransactionComment $sdkComment
     * @return TransactionComment
     */
    private function mapToTransactionComment(SdkTransactionComment $sdkComment): TransactionComment
    {
        $comment = new TransactionComment();
        $comment->id = $sdkComment->getId();
        $comment->content = $sdkComment->getContent();
        $comment->createdOn = $this->toDateTimeImmutable($sdkComment->getCreatedOn());

        return $comment;
    }
}
