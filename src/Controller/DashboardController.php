<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PaymentRow;

class DashboardController extends AbstractController
{
    #[Route('/dashboard/customers', name: 'app_dashboard_all_customers')]
    public function GetAllCustomers(): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        $table = $stripe->customers->all(['limit' => 20]);

        dd($table);
        return $this->render('dashboard/all_customers.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    #[Route('/dashboard/payments', name: 'app_dashboard_all_payments')]
    public function GetAllPayments(): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        $paymentIntents = $stripe->paymentIntents->all(['limit' => 20]);
        $paymentRows = [];
        foreach ($paymentIntents->data as $row) {
            $paymentrow = new PaymentRow($row->id, $row->amount, $row->status);
            $paymentrow->setStatus($row->status);
            if($row->status == "succeeded"){$paymentrow->setStatus("Succès");}
            else if($row->status == "requires_capture"){$paymentrow->setStatus("Non capturé");}
            $paymentRows[] = $paymentrow;
        }

        $params = [];
        $params['paymentrows'] = $paymentRows;
        return $this->render('dashboard/all_payments.html.twig', $params);
    }

    #[Route('/dashboard/payments/{id}', name: 'app_dashboard_payments_details')]
    public function GetPaymentsDetails(string $id): Response
    {
        $stripe = new \Stripe\StripeClient($_ENV["STRIPE_SECRET"]);
        if(isset($_POST['action_return'])){
            $paymentIntentsBeforeUpdate = $stripe->paymentIntents->retrieve($id, ['expand' => ['payment_method']]);
            // $paymentIntents = $stripe->paymentIntents->update($id, ['amount' => $paymentIntentsBeforeUpdate->]);
            
        }



        $paymentIntents = $stripe->paymentIntents->retrieve($id, ['expand' => ['payment_method']]);

        $params = [];
        $params['id'] = $id;
        $params['status'] = $paymentIntents->status;
        $params['amount'] = $paymentIntents->amount;
        $params['amount_received'] = $paymentIntents->amount_received;
        $params['last4'] = $paymentIntents->payment_method->card->last4;

        return $this->render('dashboard/payment_details.html.twig', $params);
    }
}