<?php
namespace App\Entity;

class PaymentRow{
    private string $id;
    private int $amount;
    private string $status;

    public function __construct(string $id, int $amount, string $status)
    {
        $this->id = $id;
        $this->amount = $amount;
        $this->status = $status;
    }
    public function getId(): string
    {
        return $this->id;
    }
    public function getAmount(): int
    {
        return $this->amount;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}