<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Tests\GlobalData;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use VRPayment\PluginCore\GlobalData\Currency\Currency;
use VRPayment\PluginCore\GlobalData\Currency\CurrencyCollection;
use VRPayment\PluginCore\GlobalData\Exception\GlobalDataException;
use VRPayment\PluginCore\GlobalData\GlobalDataGatewayInterface;
use VRPayment\PluginCore\GlobalData\GlobalDataService;
use VRPayment\PluginCore\GlobalData\LabelDescriptor\LabelDescriptor;
use VRPayment\PluginCore\GlobalData\LabelDescriptor\LabelDescriptorCollection;
use VRPayment\PluginCore\GlobalData\LabelDescriptorGroup\LabelDescriptorGroup;
use VRPayment\PluginCore\GlobalData\LabelDescriptorGroup\LabelDescriptorGroupCollection;
use VRPayment\PluginCore\GlobalData\Language\Language;
use VRPayment\PluginCore\GlobalData\Language\LanguageCollection;
use VRPayment\PluginCore\GlobalData\PaymentConnector\PaymentConnector;
use VRPayment\PluginCore\GlobalData\PaymentConnector\PaymentConnectorCollection;
use VRPayment\PluginCore\Localization\LocalizedString;
use VRPayment\PluginCore\Log\LoggerInterface;

class GlobalDataServiceTest extends TestCase
{
    private MockObject|GlobalDataGatewayInterface $gateway;
    private MockObject|LoggerInterface $logger;
    private GlobalDataService $service;

    /**
     * The gateway already produces a domain exception; the service must not re-wrap it.
     *
     * @return list<array{0: string}>
     */
    public static function methodProvider(): array
    {
        return [
            ['getCurrencies'],
            ['getLanguages'],
            ['getPaymentConnectors'],
            ['getLabelDescriptors'],
            ['getLabelDescriptorGroups'],
        ];
    }

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(GlobalDataGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new GlobalDataService($this->gateway, $this->logger);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('methodProvider')]
    public function testEveryMethodPassesTheGatewayFailureThrough(string $method): void
    {
        $gatewayException = new GlobalDataException(
            sprintf('Global data operation %s failed: boom', $method),
        );

        $this->gateway->expects($this->once())
            ->method($method)
            ->willThrowException($gatewayException);

        try {
            $this->service->{$method}();
            $this->fail('Expected GlobalDataException to be thrown.');
        } catch (GlobalDataException $e) {
            $this->assertSame($gatewayException, $e);
        }
    }

    public function testGetCurrenciesDelegatesToTheGateway(): void
    {
        $collection = new CurrencyCollection(new Currency('CHF', 2, 'Swiss Franc', 756));

        $this->gateway->expects($this->once())
            ->method('getCurrencies')
            ->willReturn($collection);

        $this->assertSame($collection, $this->service->getCurrencies());
    }

    public function testGetLabelDescriptorGroupsDelegatesToTheGateway(): void
    {
        $collection = new LabelDescriptorGroupCollection(
            new LabelDescriptorGroup(4, new LocalizedString('Card'), 10),
        );

        $this->gateway->expects($this->once())
            ->method('getLabelDescriptorGroups')
            ->willReturn($collection);

        $this->assertSame($collection, $this->service->getLabelDescriptorGroups());
    }

    public function testGetLabelDescriptorsDelegatesToTheGateway(): void
    {
        $collection = new LabelDescriptorCollection(new LabelDescriptor(1001, new LocalizedString('Card Brand')));

        $this->gateway->expects($this->once())
            ->method('getLabelDescriptors')
            ->willReturn($collection);

        $this->assertSame($collection, $this->service->getLabelDescriptors());
    }

    public function testGetLanguagesDelegatesToTheGateway(): void
    {
        $collection = new LanguageCollection(new Language('en', 'en-US', 'eng', 'English'));

        $this->gateway->expects($this->once())
            ->method('getLanguages')
            ->willReturn($collection);

        $this->assertSame($collection, $this->service->getLanguages());
    }

    public function testGetPaymentConnectorsDelegatesToTheGateway(): void
    {
        $collection = new PaymentConnectorCollection(new PaymentConnector(31, new LocalizedString('Visa Acquiring')));

        $this->gateway->expects($this->once())
            ->method('getPaymentConnectors')
            ->willReturn($collection);

        $this->assertSame($collection, $this->service->getPaymentConnectors());
    }
}
