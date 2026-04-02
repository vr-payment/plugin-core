<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Webhook;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\DeliveryIndication\State as PluginCoreDeliveryIndicationState;
use VRPayment\PluginCore\ManualTask\State as PluginCoreManualTaskState;
use VRPayment\PluginCore\Refund\State as PluginCoreRefundState;
use VRPayment\PluginCore\Token\Version\State as PluginCoreTokenVersionState;
use VRPayment\PluginCore\Transaction\Completion\State as PluginCoreTransactionCompletionState;
use VRPayment\PluginCore\Transaction\Invoice\State as PluginCoreTransactionInvoiceState;
use VRPayment\PluginCore\Transaction\State as PluginCoreTransactionState;
use VRPayment\PluginCore\Transaction\Void\State as PluginCoreTransactionVoidState;
use VRPayment\Sdk\Model\DeliveryIndicationState as SdkDeliveryIndicationState;
use VRPayment\Sdk\Model\ManualTaskState as SdkManualTaskState;
use VRPayment\Sdk\Model\RefundState as SdkRefundState;
use VRPayment\Sdk\Model\TokenVersionState as SdkTokenVersionState;
use VRPayment\Sdk\Model\TransactionCompletionState as SdkTransactionCompletionState;
use VRPayment\Sdk\Model\TransactionInvoiceState as SdkTransactionInvoiceState;
use VRPayment\Sdk\Model\TransactionState as SdkTransactionState;
use VRPayment\Sdk\Model\TransactionVoidState as SdkTransactionVoidState;

class StateSynchronizationTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function stateMappingProvider(): array
    {
        return [
            'Delivery Indication States' => [
                SdkDeliveryIndicationState::class,
                PluginCoreDeliveryIndicationState::class,
            ],
            'Refund States' => [
                SdkRefundState::class,
                PluginCoreRefundState::class,
            ],
            'Manual Task States' => [
                SdkManualTaskState::class,
                PluginCoreManualTaskState::class,
            ],
            'Token Version States' => [
                SdkTokenVersionState::class,
                PluginCoreTokenVersionState::class,
            ],
            'Transaction States' => [
                SdkTransactionState::class,
                PluginCoreTransactionState::class,
            ],
            'Transaction Completion States' => [
                SdkTransactionCompletionState::class,
                PluginCoreTransactionCompletionState::class,
            ],
            'Transaction Invoice States' => [
                SdkTransactionInvoiceState::class,
                PluginCoreTransactionInvoiceState::class,
            ],
            'Transaction Void States' => [
                SdkTransactionVoidState::class,
                PluginCoreTransactionVoidState::class,
            ],
        ];
    }

    #[DataProvider('stateMappingProvider')]
    public function testInternalEnumCoversAllSdkStates(string $sdkStateClass, string $internalEnumClass): void
    {
        $sdkStates = $sdkStateClass::getAllowableEnumValues();
        $internalEnumValues = array_map(fn ($case) => $case->value, $internalEnumClass::cases());

        foreach ($sdkStates as $sdkState) {
            $this->assertContains(
                $sdkState,
                $internalEnumValues,
                "SDK state '{$sdkState}' is missing from internal enum {$internalEnumClass}",
            );
        }
    }
}
