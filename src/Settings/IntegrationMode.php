<?php

declare(strict_types=1);

namespace VRPayment\PluginCore\Settings;

enum IntegrationMode: string
{
    case PAYMENT_PAGE = 'payment_page';
    case IFRAME = 'iframe';
    case LIGHTBOX = 'lightbox';
}
