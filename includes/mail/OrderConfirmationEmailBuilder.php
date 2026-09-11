<?php

declare(strict_types=1);

final class OrderConfirmationEmailBuilder
{
    /**
     * @param array<string, mixed> $order
     * @return array{to_email:string,to_name:string,subject:string,html:string,text:string}
     */
    public function build(array $order): array
    {
        $email = normalize_email((string) ($order['customer_email'] ?? ''));
        $name = trim((string) ($order['customer_first_name'] ?? '') . ' ' . (string) ($order['customer_last_name'] ?? ''));
        $number = trim((string) ($order['order_number'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || $number === '' || empty($order['items'])) {
            throw new InvalidArgumentException('The order cannot be rendered as an email.');
        }

        $context = [
            'order' => $order,
            'customerName' => $name,
            'orderDate' => date('F j, Y', strtotime((string) $order['placed_at'])),
            'formatMoney' => static fn (mixed $value): string => 'PKR ' . number_format((float) $value, 0),
            'formatStatus' => static fn (string $value): string => ucwords(str_replace('_', ' ', $value)),
            'escape' => static fn (mixed $value): string => htmlspecialchars(
                (string) $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
        ];

        return [
            'to_email' => $email,
            'to_name' => $name,
            'subject' => 'Order ' . $number . ' confirmed — VANTA',
            'html' => $this->render(__DIR__ . '/templates/order-confirmation.php', $context),
            'text' => $this->render(__DIR__ . '/templates/order-confirmation-text.php', $context),
        ];
    }

    /** @param array<string, mixed> $context */
    private function render(string $template, array $context): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            extract($context, EXTR_SKIP);
            require $template;
            return trim((string) ob_get_contents());
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }
}
