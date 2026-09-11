<?php

declare(strict_types=1);

interface MailTransportInterface
{
    /**
     * @param array{
     *   to_email:string,
     *   to_name:string,
     *   subject:string,
     *   html:string,
     *   text:string
     * } $message
     */
    public function send(array $message): void;
}
