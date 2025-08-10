<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('filament.admin.resources.invoices.view', ['record' => $this->invoice->id]);

        return (new MailMessage)
            ->subject('Invoice Pending Approval')
            ->greeting('Hello!')
            ->line('A new invoice has been generated and requires your approval.')
            ->line('Invoice Reference: ' . $this->invoice->reference_number)
            ->line('Total Amount: GMD ' . number_format($this->invoice->total_amount, 2))
            ->action('Review Invoice', $url)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'reference_number' => $this->invoice->reference_number,
            'total_amount' => $this->invoice->total_amount,
            'message' => 'A new invoice requires your approval',
            'action_url' => route('filament.admin.resources.invoices.view', ['record' => $this->invoice->id]),
        ];
    }
}