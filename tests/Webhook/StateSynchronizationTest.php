<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\Webhook;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use VRPayment\PluginCore\Transaction\State as PluginCoreTransactionState;
use VRPayment\Sdk\Model\TransactionState as SdkTransactionState;
use VRPayment\PluginCore\Refund\State as PluginCoreRefundState;
use VRPayment\Sdk\Model\RefundState as SdkRefundState;
use VRPayment\PluginCore\Token\Version\State as PluginCoreTokenVersionState;
use VRPayment\Sdk\Model\TokenVersionState as SdkTokenVersionState;
use VRPayment\PluginCore\DeliveryIndication\State as PluginCoreDeliveryIndicationState;
use VRPayment\Sdk\Model\DeliveryIndicationState as SdkDeliveryIndicationState;
use VRPayment\PluginCore\ManualTask\State as PluginCoreManualTaskState;
use VRPayment\Sdk\Model\ManualTaskState as SdkManualTaskState;
use VRPayment\PluginCore\Transaction\Completion\State as PluginCoreTransactionCompletionState;
use VRPayment\Sdk\Model\TransactionCompletionState as SdkTransactionCompletionState;
use VRPayment\PluginCore\Transaction\Invoice\State as PluginCoreTransactionInvoiceState;
use VRPayment\Sdk\Model\TransactionInvoiceState as SdkTransactionInvoiceState;
use VRPayment\Sdk\Model\TransactionVoidState as SdkTransactionVoidState;
use VRPayment\PluginCore\Transaction\Void\State as PluginCoreTransactionVoidState;

class StateSynchronizationTest extends TestCase
{
    public static function stateMappingProvider(): array
    {
        return [
            'Delivery Indication States' => [
                SdkDeliveryIndicationState::class,
                PluginCoreDeliveryIndicationState::class
            ],
            'Refund States' => [
                SdkRefundState::class,
                PluginCoreRefundState::class
            ],
            'Manual Task States' => [
                SdkManualTaskState::class,
                PluginCoreManualTaskState::class
            ],
            'Token Version States' => [
                SdkTokenVersionState::class,
                PluginCoreTokenVersionState::class
            ],
            'Transaction States' => [
                SdkTransactionState::class,
                PluginCoreTransactionState::class
            ],
            'Transaction Completion States' => [
                SdkTransactionCompletionState::class,
                PluginCoreTransactionCompletionState::class
            ],
            'Transaction Invoice States' => [
                SdkTransactionInvoiceState::class,
                PluginCoreTransactionInvoiceState::class
            ],
            'Transaction Void States' => [
                SdkTransactionVoidState::class,
                PluginCoreTransactionVoidState::class
            ],
        ];
    }

    #[DataProvider('stateMappingProvider')]
    public function testInternalEnumCoversAllSdkStates(string $sdkStateClass, string $internalEnumClass): void
    {
        $sdkStates = $sdkStateClass::getAllowableEnumValues();
        $internalEnumValues = array_map(fn($case) => $case->value, $internalEnumClass::cases());

        foreach ($sdkStates as $sdkState) {
            $this->assertContains(
                $sdkState,
                $internalEnumValues,
                "SDK state '{$sdkState}' is missing from internal enum {$internalEnumClass}"
            );
        }
    }
}
