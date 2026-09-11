<?php

declare(strict_types=1);

final class MailService
{
    public function __construct(
        private OrderRepository $orders,
        private MailTransportInterface $transport,
        private OrderConfirmationEmailBuilder $builder
    ) {
    }

    /** @return array{attempted:bool,sent:bool,status:string} */
    public function sendOrderConfirmation(int $orderId): array
    {
        if (!$this->orders->claimConfirmationEmail($orderId)) {
            $status = $this->orders->confirmationEmailStatus($orderId) ?? 'unavailable';
            return ['attempted' => false, 'sent' => $status === 'sent', 'status' => $status];
        }

        try {
            $order = $this->orders->findById($orderId);
            if ($order === null) {
                throw new RuntimeException('Order email source is unavailable.');
            }
            $this->transport->send($this->builder->build($order));
            $this->orders->markConfirmationEmailSent($orderId);
            return ['attempted' => true, 'sent' => true, 'status' => 'sent'];
        } catch (Throwable $exception) {
            try {
                $this->orders->markConfirmationEmailFailed($orderId);
            } catch (Throwable $trackingException) {
                error_log(sprintf(
                    'VANTA mail status update failed for order ID %d (%s).',
                    $orderId,
                    $trackingException::class
                ));
            }
            error_log(sprintf(
                'VANTA order confirmation delivery failed for order ID %d (%s).',
                $orderId,
                $exception::class
            ));
            return ['attempted' => true, 'sent' => false, 'status' => 'failed'];
        }
    }
}
