<?php

declare(strict_types=1);

namespace Scafera\File\Listener;

use Scafera\File\FileResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal Converts FileResponse into a Symfony BinaryFileResponse.
 *
 * Runs at priority 10 — before the kernel's ResponseListener at priority 0.
 */
final class FileResponseListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => ['onKernelView', 10]];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (!$result instanceof FileResponse) {
            return;
        }

        $response = new BinaryFileResponse($result->getPath());

        $disposition = $result->getDisposition() === 'attachment'
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response->setContentDisposition($disposition, $result->getFilename() ?? basename($result->getPath()));

        $event->setResponse($response);
    }
}
