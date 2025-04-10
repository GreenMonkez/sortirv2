<?php

namespace App\EventListener;

use App\Service\SortieNotifier;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    public function __construct(private SortieNotifier $sortieNotifier) {}

    public function onLogin(InteractiveLoginEvent $event): void
    {
        // Appeler le service de notification
        $this->sortieNotifier->notifyLogSorties();
    }
}